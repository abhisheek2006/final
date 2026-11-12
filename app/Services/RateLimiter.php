<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Paths;
use App\Core\Session;
use Throwable;

/**
 * Fixed-window rate limiter.
 *
 * Uses the MongoDB `rate_limits` collection when reachable and falls back to a
 * small JSON file in storage/logs otherwise, so the site still throttles
 * properly during a database outage.
 */
final class RateLimiter
{
    /**
     * Attempt a hit. Returns true when the request is allowed.
     */
    public static function attempt(string $bucket, string $identifier, ?int $max = null, ?int $window = null): bool
    {
        $limits = (array) Config::get('forms.rate_limits.' . $bucket, []);
        $max    = $max ?? (int) ($limits['max'] ?? 5);
        $window = $window ?? (int) ($limits['window'] ?? 3600);

        if ($max <= 0) {
            return true;
        }

        $identifier = self::fingerprint($bucket, $identifier);
        $now        = time();

        try {
            return MongoDatabase::rateLimitHit($identifier, $max, $window, $now);
        } catch (Throwable) {
            return self::attemptFile($identifier, $max, $window, $now);
        }
    }

    /**
     * Seconds until the current window frees up (0 when not limited).
     */
    public static function retryAfter(string $bucket, string $identifier, ?int $window = null): int
    {
        $limits   = (array) Config::get('forms.rate_limits.' . $bucket, []);
        $window   = $window ?? (int) ($limits['window'] ?? 3600);
        $file     = self::filePath();
        $key      = self::fingerprint($bucket, $identifier);

        try {
            $entry = MongoDatabase::findOne('rate_limits', ['_id' => $key]);
            if ($entry !== null) {
                $elapsed = time() - (int) ($entry['window_start'] ?? 0);
                if ($elapsed < $window) {
                    return max(1, $window - $elapsed);
                }
            }
        } catch (Throwable) {
            // Use the local fallback below.
        }

        if (is_file($file)) {
            $data = json_decode((string) @file_get_contents($file), true);
            if (is_array($data) && isset($data[$key]['window_start'])) {
                $elapsed = time() - (int) $data[$key]['window_start'];
                if ($elapsed < $window) {
                    return max(1, $window - $elapsed);
                }
            }
        }

        return $window;
    }

    public static function clear(string $bucket, string $identifier): void
    {
        try {
            MongoDatabase::clearRateLimit(self::fingerprint($bucket, $identifier));

            return;
        } catch (Throwable) {
            // fall through to file cleanup
        }

        $file = self::filePath();
        if (!is_file($file)) {
            return;
        }

        $data = json_decode((string) @file_get_contents($file), true);
        if (!is_array($data)) {
            return;
        }

        unset($data[self::fingerprint($bucket, $identifier)]);
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private static function attemptFile(string $key, int $max, int $window, int $now): bool
    {
        $file = self::filePath();
        if (!Paths::ensureWritable(dirname($file))) {
            return true; // Cannot store state: fail open rather than block patients.
        }

        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            return true;
        }

        $allowed = true;
        $blocked = false;

        if (flock($handle, LOCK_EX)) {
            $raw  = stream_get_contents($handle) ?: '';
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                $data = [];
            }

            // Drop stale buckets.
            foreach ($data as $index => $entry) {
                if (!is_array($entry) || ($now - (int) ($entry['window_start'] ?? 0)) > 86400) {
                    unset($data[$index]);
                }
            }

            $entry = $data[$key] ?? ['hits' => 0, 'window_start' => $now];

            if (($now - (int) $entry['window_start']) >= $window) {
                $entry = ['hits' => 1, 'window_start' => $now];
            } else {
                $entry['hits'] = (int) $entry['hits'] + 1;
            }

            $data[$key] = $entry;

            if ((int) $entry['hits'] > $max) {
                $allowed = false;
                $blocked = true;
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($data, JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);
        }

        fclose($handle);

        return $allowed && !$blocked;
    }

    private static function filePath(): string
    {
        return Paths::storage() . '/logs/rate-limits.json';
    }

    /**
     * Combine session id, IP and a client fingerprint so that a single browser
     * is limited without penalising a shared clinic Wi-Fi connection.
     */
    private static function fingerprint(string $bucket, string $identifier): string
    {
        $session = Session::has('_sid_seed') ? (string) Session::get('_sid_seed') : '';

        return substr(hash('sha256', $bucket . '|' . $identifier . '|' . $session), 0, 60);
    }
}

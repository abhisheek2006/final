<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Paths;

/**
 * Append-only JSONL writer used as a durable fallback whenever MongoDB is
 * unavailable. Files live in storage/submissions and are grouped by kind and
 * month, e.g. appointments-2026-10.jsonl.
 */
final class SubmissionStore
{
    /**
     * @param array<string,mixed> $record
     * @return string Absolute file path, or an empty string on failure.
     */
    public static function append(string $kind, array $record): string
    {
        $dir = Paths::storage() . '/submissions';
        if (!Paths::ensureWritable($dir)) {
            Logger::error('fallback.unwritable', 'Cannot write fallback submission file', ['kind' => $kind]);

            return '';
        }

        $file = sprintf('%s/%s-%s.jsonl', $dir, self::safeKind($kind), date('Y-m'));

        $record = array_merge(['_stored_at' => date('c')], $record);
        $line   = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($line === false) {
            Logger::error('fallback.encode', 'Could not encode fallback record', ['kind' => $kind]);

            return '';
        }

        if (@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            Logger::error('fallback.write', 'Could not write fallback record', ['kind' => $kind, 'file' => $file]);

            return '';
        }

        @chmod($file, 0640);

        return $file;
    }

    /**
     * Read the most recent records for a kind (newest last).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function read(string $kind, int $limit = 50, ?string $month = null): array
    {
        $dir  = Paths::storage() . '/submissions';
        $kind = self::safeKind($kind);
        if ($month !== null) {
            $files = [];
            foreach ([$month, date('Y-m')] as $candidate) {
                $file = sprintf('%s/%s-%s.jsonl', $dir, $kind, $candidate);
                if (is_file($file) && !in_array($file, $files, true)) {
                    $files[] = $file;
                }
            }
        } else {
            $files = glob($dir . '/' . $kind . '-????-??.jsonl') ?: [];
            sort($files, SORT_STRING);
        }

        $rows = [];
        foreach ($files as $file) {
            $handle = @fopen($file, 'rb');
            if ($handle === false) {
                continue;
            }
            while (($line = fgets($handle)) !== false) {
                $decoded = json_decode(trim($line), true);
                if (is_array($decoded)) {
                    $rows[] = $decoded;
                }
            }
            fclose($handle);
        }

        return $limit > 0 ? array_slice($rows, -$limit) : $rows;
    }

    /**
     * @return array<int,array{file:string,records:int,size:int,modified:int}>
     */
    public static function inventory(): array
    {
        $dir      = Paths::storage() . '/submissions';
        $inventory = [];

        if (!is_dir($dir)) {
            return $inventory;
        }

        $files = glob($dir . '/*.jsonl') ?: [];
        sort($files);

        foreach ($files as $file) {
            $lines   = 0;
            $handle  = @fopen($file, 'rb');
            if ($handle !== false) {
                while (fgets($handle) !== false) {
                    $lines++;
                }
                fclose($handle);
            }

            $inventory[] = [
                'file'     => basename($file),
                'records'  => $lines,
                'size'     => (int) @filesize($file),
                'modified' => (int) @filemtime($file),
            ];
        }

        return $inventory;
    }

    public static function countRecords(string $kind): int
    {
        return count(self::read($kind, 0));
    }

    private static function safeKind(string $kind): string
    {
        $kind = strtolower(preg_replace('/[^a-z0-9_\-]/', '', $kind) ?? '');

        return $kind === '' ? 'misc' : $kind;
    }
}

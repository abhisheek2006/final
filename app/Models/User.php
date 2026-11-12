<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Logger;
use App\Core\Session;
use App\Services\MongoDatabase;
use Throwable;

/**
 * Admin authentication. Passwords are verified with password_verify() against
 * a hash from .env (ADMIN_PASSWORD_HASH) or the `users` table. There is no
 * plain-text password path anywhere in the application.
 */
final class User
{
    public const SESSION_KEY = '_admin_user';

    public static function attempt(string $email, string $password): array
    {
        $email    = mb_strtolower(trim($email));
        $password = (string) $password;

        if ($email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Enter both your email address and password.'];
        }

        $record = self::findByEmail($email);

        if ($record === null) {
            // Equalise timing against the hash comparison below.
            password_verify($password, '$2y$10$usesomesillystringforsalt0123456789uAdN3J.r3xJq1wQ3wQ.');

            return ['ok' => false, 'error' => 'Those credentials were not recognised.'];
        }

        if ((int) ($record['is_active'] ?? 1) !== 1) {
            return ['ok' => false, 'error' => 'This account has been deactivated. Contact the clinic administrator.'];
        }

        if (!password_verify($password, (string) $record['password_hash'])) {
            self::recordFailedLogin($email);

            return ['ok' => false, 'error' => 'Those credentials were not recognised.'];
        }

        // Transparently upgrade the hash when PHP's default algorithm changes.
        if (password_needs_rehash((string) $record['password_hash'], PASSWORD_DEFAULT)) {
            try {
                MongoDatabase::updateOne('users', ['id' => (int) $record['id']], [
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            } catch (Throwable) {
                // Non-fatal.
            }
        }

        self::login([
            'id'    => (int) $record['id'],
            'name'  => (string) $record['name'],
            'email' => (string) $record['email'],
            'role'  => (string) ($record['role'] ?? 'admin'),
        ]);

        return ['ok' => true, 'error' => ''];
    }

    /** @return array<string,mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        $email = mb_strtolower(trim($email));

        // 1. Explicit .env credentials take precedence (handy during recovery).
        $envHash = Config::string('admin.password_hash', '');
        if ($envHash !== '') {
            $envEmail = Config::string('admin.email', '');
            if ($envEmail === '' || mb_strtolower($envEmail) === $email) {
                return [
                    'id'            => 0,
                    'name'          => Config::string('admin.name', 'Clinic Administrator'),
                    'email'         => $envEmail !== '' ? $envEmail : $email,
                    'role'          => 'admin',
                    'password_hash' => $envHash,
                    'is_active'     => 1,
                    'source'        => 'env',
                ];
            }
        }

        // 2. The seeded database user.
        try {
            return MongoDatabase::findOne('users', ['email' => $email]);
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string,mixed> $user */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::rotateCsrf();

        Session::set(self::SESSION_KEY, $user);
        Session::set('_admin_login_at', time());
        Session::set('_admin_last_seen', time());
        Session::forget('_admin_attempts_' . self::attemptKey());
        Session::forget('_admin_attempt_start_' . self::attemptKey());

        try {
            if (($user['id'] ?? 0) > 0) {
                MongoDatabase::updateOne('users', ['id' => (int) $user['id']], ['last_login_at' => date('Y-m-d H:i:s')]);
            }
        } catch (Throwable) {
            // Login must succeed even if the audit write fails.
        }

        Logger::info('auth.login', 'Administrator signed in', ['email' => $user['email'] ?? '']);
    }

    public static function logout(): void
    {
        Logger::info('auth.logout', 'Administrator signed out', ['email' => self::user()['email'] ?? '']);

        Session::forget(self::SESSION_KEY);
        Session::forget('_admin_login_at');
        Session::forget('_admin_last_seen');
        Session::regenerate();
    }

    public static function check(): bool
    {
        $user = self::user();

        if ($user === null) {
            return false;
        }

        $timeout = (int) Config::get('security.admin_idle_timeout', 3600);
        if ($timeout > 0) {
            $last = (int) Session::get('_admin_last_seen', 0);
            if ($last > 0 && (time() - $last) > $timeout) {
                self::logout();

                return false;
            }
        }
        Session::set('_admin_last_seen', time());

        return true;
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        $user = Session::get(self::SESSION_KEY);

        return is_array($user) ? $user : null;
    }

    public static function name(): string
    {
        $user = self::user();

        return (string) ($user['name'] ?? 'Administrator');
    }

    /* ------------------------- brute force control ----------------------- */

    public static function recordFailedLogin(string $email): void
    {
        $key = self::attemptKey();

        try {
            MongoDatabase::rateLimitHit($key, PHP_INT_MAX, 900, time());
        } catch (Throwable) {
            $sessionKey = '_admin_attempts_' . $key;
            $attempts   = (int) Session::get($sessionKey, 0);
            Session::set($sessionKey, $attempts + 1);
            Session::set('_admin_attempt_start_' . $key, (int) Session::get('_admin_attempt_start_' . $key, time()));
        }

        Logger::warning('auth.failed', 'Failed administrator sign-in', ['email' => $email]);
    }

    public static function attempts(): int
    {
        try {
            $row = MongoDatabase::findOne('rate_limits', ['_id' => self::attemptKey()]);

            return $row === null ? (int) Session::get('_admin_attempts_' . self::attemptKey(), 0) : (int) ($row['hits'] ?? 0);
        } catch (Throwable) {
            return (int) Session::get('_admin_attempts_' . self::attemptKey(), 0);
        }
    }

    public static function lockedOut(): bool
    {
        $max    = (int) Config::get('admin.max_attempts', 6);
        $window = (int) Config::get('admin.lockout_seconds', 900);

        return self::attempts() >= $max && (time() - (int) self::lastAttemptAt()) < $window;
    }

    private static function lastAttemptAt(): int
    {
        try {
            $row = MongoDatabase::findOne('rate_limits', ['_id' => self::attemptKey()]);

            if ($row !== null) {
                return (int) ($row['window_start'] ?? 0);
            }
        } catch (Throwable) {
            // ignore
        }

        return (int) Session::get('_admin_attempt_start_' . self::attemptKey(), 0);
    }

    private static function attemptKey(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return substr(hash('sha256', 'admin-login|' . $ip), 0, 60);
    }

    /**
     * Create a new password hash. Used by the CLI helper and the deployment
     * documentation. Never echoes the plain-text password.
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

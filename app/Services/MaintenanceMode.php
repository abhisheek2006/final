<?php
declare(strict_types=1);

namespace App\Services;

use Throwable;

/** Persistent maintenance-mode setting stored in MongoDB's settings collection. */
final class MaintenanceMode
{
    private const KEY = 'maintenance_mode';

    public const DEFAULT_MESSAGE = 'We are making a few updates to our website. Please check back soon.';

    /** @return array{enabled:bool,message:string,updated_at:string} */
    public static function settings(): array
    {
        $record = MongoDatabase::findOne('settings', ['key' => self::KEY]);

        return [
            'enabled'    => (bool) ($record['enabled'] ?? false),
            'message'    => trim((string) ($record['message'] ?? self::DEFAULT_MESSAGE)) ?: self::DEFAULT_MESSAGE,
            'updated_at' => (string) ($record['updated_at'] ?? ''),
        ];
    }

    public static function enabled(): bool
    {
        try {
            return self::settings()['enabled'];
        } catch (Throwable $error) {
            \App\Core\Logger::warning('maintenance.read', 'Maintenance mode could not be read.', [
                'exception' => $error::class,
            ]);

            return false;
        }
    }

    /** @return array{enabled:bool,message:string,available:bool} */
    public static function publicStatus(): array
    {
        try {
            $settings = self::settings();

            return [
                'enabled'   => $settings['enabled'],
                'message'   => $settings['message'],
                'available' => true,
            ];
        } catch (Throwable) {
            return [
                'enabled'   => false,
                'message'   => self::DEFAULT_MESSAGE,
                'available' => false,
            ];
        }
    }

    public static function save(bool $enabled, string $message): void
    {
        $record = MongoDatabase::findOne('settings', ['key' => self::KEY]);
        $changes = [
            'key'        => self::KEY,
            'enabled'    => $enabled,
            'message'    => trim($message) !== '' ? trim($message) : self::DEFAULT_MESSAGE,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($record !== null) {
            if (!MongoDatabase::updateOne('settings', ['id' => (int) ($record['id'] ?? 0)], $changes)) {
                throw new \RuntimeException('The maintenance setting could not be updated.');
            }

            return;
        }

        MongoDatabase::insert('settings', $changes);
    }
}

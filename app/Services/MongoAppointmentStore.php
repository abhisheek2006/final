<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use Throwable;

/** Optional MongoDB mirror/failover for appointment requests. */
final class MongoAppointmentStore
{
    private static mixed $manager = null;
    private static bool $attempted = false;

    public static function configured(): bool
    {
        return trim(Config::string('mongodb.uri', '')) !== '';
    }

    public static function driverAvailable(): bool
    {
        return class_exists(\MongoDB\Driver\Manager::class);
    }

    public static function available(): bool
    {
        $manager = self::manager();
        if ($manager === null) {
            return false;
        }

        try {
            $manager->executeCommand(self::database(), new \MongoDB\Driver\Command(['ping' => 1]));

            return true;
        } catch (Throwable $e) {
            Logger::warning('mongo.ping', 'MongoDB connection check failed', ['exception' => $e::class]);

            return false;
        }
    }

    /** Store the appointment in the MongoDB mirror. */
    public static function create(array $row, string $role = 'mirror'): bool
    {
        $manager = self::manager();
        if ($manager === null) {
            return false;
        }

        try {
            $row['_storage_role'] = $role === 'failover' ? 'failover' : 'mirror';
            $bulk = new \MongoDB\Driver\BulkWrite();
            $bulk->insert($row);
            $result = $manager->executeBulkWrite(self::database() . '.appointments', $bulk);

            return $result->getInsertedCount() === 1;
        } catch (Throwable $e) {
            // Never log the URI or its credentials.
            Logger::warning('mongo.write', 'MongoDB appointment write failed', ['exception' => $e::class]);

            return false;
        }
    }

    /**
     * Read MongoDB appointments when MySQL is unavailable.
     *
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function list(
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        bool $includeMirrors = false
    ): array
    {
        $manager = self::manager();
        if ($manager === null) {
            return [];
        }

        try {
            $filter = $includeMirrors
                ? []
                : ['_storage_role' => 'failover'];
            $query = new \MongoDB\Driver\Query($filter, ['sort' => ['created_at' => -1], 'limit' => 1000]);
            $cursor = $manager->executeQuery(self::database() . '.appointments', $query);
            $rows = [];

            foreach ($cursor as $document) {
                $row = (array) $document;
                unset($row['_id']);
                unset($row['_storage_role']);
                $row['id'] = null;
                $row['status'] = (string) ($row['status'] ?? 'new');
                $row['storage_source'] = 'mongo';
                $rows[] = $row;
            }

            $rows = array_values(array_filter($rows, static function (array $row) use ($filters): bool {
                foreach (['status', 'type'] as $key) {
                    if (!empty($filters[$key]) && (string) ($row[$key] ?? '') !== (string) $filters[$key]) {
                        return false;
                    }
                }
                if (!empty($filters['q'])) {
                    $needle = mb_strtolower((string) $filters['q']);
                    $haystack = mb_strtolower(implode(' ', [
                        (string) ($row['full_name'] ?? ''),
                        (string) ($row['phone'] ?? ''),
                        (string) ($row['email'] ?? ''),
                        (string) ($row['reference_code'] ?? ''),
                    ]));
                    if (!str_contains($haystack, $needle)) {
                        return false;
                    }
                }
                $created = substr((string) ($row['created_at'] ?? ''), 0, 10);
                if (!empty($filters['from']) && $created < (string) $filters['from']) {
                    return false;
                }
                if (!empty($filters['to']) && $created > (string) $filters['to']) {
                    return false;
                }

                return true;
            }));

            return $limit > 0 ? array_slice($rows, max(0, $offset), $limit) : array_slice($rows, max(0, $offset));
        } catch (Throwable $e) {
            Logger::warning('mongo.read', 'MongoDB appointment read failed', ['exception' => $e::class]);

            return [];
        }
    }

    private static function manager(): mixed
    {
        if (self::$attempted) {
            return self::$manager;
        }
        self::$attempted = true;

        $uri = trim(Config::string('mongodb.uri', ''));
        if ($uri === '' || !self::driverAvailable()) {
            return null;
        }

        try {
            self::$manager = new \MongoDB\Driver\Manager($uri);
        } catch (Throwable $e) {
            Logger::warning('mongo.connect', 'MongoDB manager could not be created', ['exception' => $e::class]);
        }

        return self::$manager;
    }

    private static function database(): string
    {
        $database = Config::string('mongodb.database', 'swasti_homeo');
        $database = preg_replace('/[^A-Za-z0-9_-]/', '', $database) ?? '';

        return $database !== '' ? $database : 'swasti_homeo';
    }
}

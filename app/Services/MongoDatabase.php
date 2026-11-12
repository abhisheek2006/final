<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use RuntimeException;
use Throwable;

/** Small native-driver MongoDB store used by the app's persistent models. */
final class MongoDatabase
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

    public static function isAvailable(): bool
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

    /** @param array<string,mixed> $document */
    public static function insert(string $collection, array $document): int
    {
        $id = self::nextId($collection);
        $document['id'] = $id;
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->insert($document);
        $result = self::managerOrFail()->executeBulkWrite(self::database() . '.' . self::safeCollection($collection), $bulk);

        if ($result->getInsertedCount() !== 1) {
            throw new RuntimeException('MongoDB did not save the record.');
        }

        return $id;
    }

    /** @param array<string,mixed> $document */
    public static function insertImported(string $collection, array $document): bool
    {
        $collection = self::safeCollection($collection);
        $sourceId = (int) ($document['id'] ?? 0);
        if ($sourceId < 1) {
            return false;
        }

        $existing = self::findOne($collection, ['id' => $sourceId]);
        if ($existing !== null && self::sameNaturalRecord($collection, $existing, $document)) {
            $id = (int) $existing['id'];
            unset($document['id']);
            self::updateOne($collection, ['id' => $id], $document);
            self::advanceCounter($collection, $id);

            return false;
        }

        $naturalFilter = self::naturalFilter($collection, $document);
        if ($naturalFilter !== []) {
            $naturalMatch = self::findOne($collection, $naturalFilter);
            if ($naturalMatch !== null && self::sameNaturalRecord($collection, $naturalMatch, $document)) {
                $id = (int) $naturalMatch['id'];
                unset($document['id']);
                self::updateOne($collection, ['id' => $id], $document);
                self::advanceCounter($collection, $id);

                return false;
            }
        }

        $id = $sourceId;
        if ($existing !== null) {
            $id = self::nextId($collection);
            $document['legacy_id'] = $sourceId;
        }
        $document['id'] = $id;

        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->insert($document);
        $result = self::managerOrFail()->executeBulkWrite(self::database() . '.' . $collection, $bulk);
        if ($result->getInsertedCount() !== 1) {
            return false;
        }

        self::advanceCounter($collection, $id);

        return true;
    }

    /** @param array<string,mixed> $left @param array<string,mixed> $right */
    private static function sameNaturalRecord(string $collection, array $left, array $right): bool
    {
        $keys = match ($collection) {
            'appointments' => ['reference_code'],
            'users' => ['email'],
            'faqs' => ['question'],
            'messages' => ['name', 'phone', 'email', 'subject', 'message', 'created_at'],
            default => [],
        };
        if ($keys === []) {
            return false;
        }
        foreach ($keys as $key) {
            if ((string) ($left[$key] ?? '') !== (string) ($right[$key] ?? '')) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string,mixed> $document @return array<string,mixed> */
    private static function naturalFilter(string $collection, array $document): array
    {
        $keys = match ($collection) {
            'appointments' => ['reference_code'],
            'users' => ['email'],
            'faqs' => ['question'],
            'messages' => ['name', 'phone', 'email', 'subject', 'message', 'created_at'],
            default => [],
        };
        $filter = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $document)) {
                return [];
            }
            $filter[$key] = $document[$key];
        }

        return $filter;
    }

    private static function advanceCounter(string $collection, int $id): void
    {
        $counter = new \MongoDB\Driver\BulkWrite();
        $counter->update(['_id' => $collection], ['$max' => ['seq' => $id]], ['upsert' => true]);
        self::managerOrFail()->executeBulkWrite(self::database() . '._counters', $counter);
    }

    /** @param array<string,mixed> $document */
    public static function insertImportedRateLimit(array $document): bool
    {
        $key = (string) ($document['bucket_key'] ?? '');
        if ($key === '' || self::findOne('rate_limits', ['_id' => $key]) !== null) {
            return false;
        }
        $document['_id'] = $key;
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->insert($document);
        return self::managerOrFail()->executeBulkWrite(self::database() . '.rate_limits', $bulk)->getInsertedCount() === 1;
    }

    /** @param array<string,mixed> $document */
    public static function importSubmission(string $collection, array $document): bool
    {
        $collection = self::safeCollection($collection);
        unset($document['_stored_at'], $document['_error'], $document['storage_source']);
        if (!empty($document['reference_code'])) {
            $existing = self::findOne($collection, ['reference_code' => (string) $document['reference_code']]);
            if ($existing !== null) {
                return false;
            }
        } elseif ($collection === 'messages') {
            $naturalFilter = self::naturalFilter($collection, $document);
            if ($naturalFilter !== [] && self::findOne($collection, $naturalFilter) !== null) {
                return false;
            }
        }

        if ((int) ($document['id'] ?? 0) < 1) {
            unset($document['id']);
        }

        return self::insert($collection, $document) > 0;
    }

    /** @param array<string,mixed> $filter @param array<string,int> $sort @return array<int,array<string,mixed>> */
    public static function list(string $collection, array $filter = [], array $sort = ['created_at' => -1], int $limit = 0, int $offset = 0): array
    {
        $options = [];
        if ($sort !== []) {
            $options['sort'] = $sort;
        }
        if ($limit > 0) {
            $options['limit'] = $limit;
        }
        if ($offset > 0) {
            $options['skip'] = $offset;
        }

        $cursor = self::managerOrFail()->executeQuery(
            self::database() . '.' . self::safeCollection($collection),
            new \MongoDB\Driver\Query($filter, $options)
        );

        return array_map(static fn (mixed $document): array => self::normaliseDocument($document), iterator_to_array($cursor, false));
    }

    /** @param array<string,mixed> $filter @return array<string,mixed>|null */
    public static function findOne(string $collection, array $filter): ?array
    {
        $rows = self::list($collection, $filter, [], 1);

        return $rows[0] ?? null;
    }

    /** @return array<string,mixed>|null */
    public static function findById(string $collection, int $id): ?array
    {
        return self::findOne($collection, ['id' => $id]);
    }

    /** @param array<string,mixed> $filter @param array<string,mixed> $changes */
    public static function updateOne(string $collection, array $filter, array $changes): bool
    {
        if ($changes === []) {
            return false;
        }

        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->update($filter, ['$set' => $changes], ['multi' => false]);
        $result = self::managerOrFail()->executeBulkWrite(self::database() . '.' . self::safeCollection($collection), $bulk);

        return $result->getMatchedCount() > 0;
    }

    /** @param array<string,mixed> $filter */
    public static function deleteOne(string $collection, array $filter): bool
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->delete($filter, ['limit' => 1]);
        $result = self::managerOrFail()->executeBulkWrite(self::database() . '.' . self::safeCollection($collection), $bulk);

        return $result->getDeletedCount() > 0;
    }

    /** @param array<string,mixed> $filter */
    public static function count(string $collection, array $filter = []): int
    {
        $command = new \MongoDB\Driver\Command([
            'count' => self::safeCollection($collection),
            'query' => $filter,
        ]);
        $response = self::managerOrFail()->executeCommand(self::database(), $command)->toArray()[0] ?? null;

        return is_object($response) ? (int) ($response->n ?? 0) : 0;
    }

    /** Atomically apply a fixed-window hit counter. */
    public static function rateLimitHit(string $key, int $max, int $window, int $now): bool
    {
        $collection = self::safeCollection('rate_limits');
        $manager = self::managerOrFail();
        $ns = self::database() . '.' . $collection;
        $current = self::findOne('rate_limits', ['_id' => $key]);

        if ($current === null || ($now - (int) ($current['window_start'] ?? 0)) >= $window) {
            $filter = $current === null
                ? ['_id' => $key]
                : ['_id' => $key, 'window_start' => (int) $current['window_start']];
            $update = $current === null
                ? ['$setOnInsert' => ['window_start' => $now], '$set' => ['hits' => 1, 'updated_at' => date('Y-m-d H:i:s', $now)]]
                : ['$set' => ['hits' => 1, 'window_start' => $now, 'updated_at' => date('Y-m-d H:i:s', $now)]];
            $bulk = new \MongoDB\Driver\BulkWrite();
            $bulk->update($filter, $update, ['upsert' => $current === null, 'multi' => false]);
            try {
                $manager->executeBulkWrite($ns, $bulk);
            } catch (Throwable $e) {
                // Concurrent first requests can race on MongoDB's unique _id; retry as an increment.
                if ($current !== null) {
                    throw $e;
                }
                return self::incrementRateLimit($manager, $ns, $key, $max, $now);
            }

            return true;
        }

        if ((int) ($current['hits'] ?? 0) >= $max) {
            return false;
        }

        return self::incrementRateLimit($manager, $ns, $key, $max, $now);
    }

    public static function clearRateLimit(string $key): void
    {
        self::deleteOne('rate_limits', ['_id' => $key]);
    }

    private static function incrementRateLimit(mixed $manager, string $namespace, string $key, int $max, int $now): bool
    {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->update(['_id' => $key], [
            '$inc' => ['hits' => 1],
            '$set' => ['updated_at' => date('Y-m-d H:i:s', $now)],
            '$setOnInsert' => ['window_start' => $now],
        ], ['upsert' => true, 'multi' => false]);
        $manager->executeBulkWrite($namespace, $bulk);
        $record = self::findOne('rate_limits', ['_id' => $key]);

        return (int) ($record['hits'] ?? 0) <= $max;
    }

    private static function nextId(string $collection): int
    {
        $command = new \MongoDB\Driver\Command([
            'findAndModify' => '_counters',
            'query' => ['_id' => self::safeCollection($collection)],
            'update' => ['$inc' => ['seq' => 1]],
            'upsert' => true,
            'new' => true,
        ]);
        $response = self::managerOrFail()->executeCommand(self::database(), $command)->toArray()[0] ?? null;
        $value = is_object($response) ? ($response->value ?? null) : null;
        $sequence = is_object($value) ? (int) ($value->seq ?? 0) : 0;

        if ($sequence < 1) {
            throw new RuntimeException('MongoDB could not allocate a record id.');
        }

        return $sequence;
    }

    /** @return array<string,mixed> */
    private static function normaliseDocument(mixed $document): array
    {
        $row = (array) $document;
        unset($row['_id']);

        return $row;
    }

    private static function managerOrFail(): mixed
    {
        $manager = self::manager();
        if ($manager === null) {
            throw new RuntimeException(self::driverAvailable()
                ? 'MongoDB is not configured or could not connect.'
                : 'Enable the PHP mongodb extension to use MongoDB.');
        }

        return $manager;
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
        $database = preg_replace('/[^A-Za-z0-9_-]/', '', Config::string('mongodb.database', 'swasti_homeo')) ?? '';

        return $database !== '' ? $database : 'swasti_homeo';
    }

    private static function safeCollection(string $collection): string
    {
        $collection = preg_replace('/[^A-Za-z0-9_]/', '', $collection) ?? '';
        if ($collection === '') {
            throw new RuntimeException('Invalid MongoDB collection name.');
        }

        return $collection;
    }
}

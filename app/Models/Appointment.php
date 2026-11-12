<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\MongoDatabase;
use App\Services\SubmissionStore;
use Throwable;

/**
 * Appointment persistence. Falls back to storage/submissions JSONL whenever
 * MongoDB is unavailable, so a patient request is never lost.
 */
final class Appointment
{
    public const STATUSES = ['new', 'confirmed', 'completed', 'cancelled', 'no_show'];

    public const STATUS_LABELS = [
        'new'       => 'New',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show'   => 'No show',
    ];

    /**
     * @param array<string,mixed> $data
     * @return array{id:int|null,reference:string,stored:'mongo'|'file',error:string}
     */
    public static function create(array $data): array
    {
        $data['status']    = $data['status'] ?? 'new';
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? $data['created_at'];
        $data['reference_code'] = $data['reference_code'] ?? self::generateReference((string) ($data['type'] ?? 'consultation'));

        $row = [
            'reference_code' => $data['reference_code'],
            'type'           => $data['type'],
            'full_name'      => $data['full_name'],
            'phone'          => $data['phone'],
            'email'          => $data['email'],
            'age'            => $data['age'],
            'preferred_date' => $data['preferred_date'],
            'preferred_time' => $data['preferred_time'],
            'reason'         => $data['reason'],
            'message'        => $data['message'],
            'consent'        => (int) ($data['consent'] ?? 0),
            'status'         => $data['status'],
            'admin_note'     => $data['admin_note'] ?? null,
            'spam_score'     => (int) ($data['spam_score'] ?? 0),
            'ip_address'     => $data['ip_address'] ?? null,
            'user_agent'     => $data['user_agent'] ?? null,
            'source'         => $data['source'] ?? 'website',
            'created_at'     => $data['created_at'],
            'updated_at'     => $data['updated_at'],
        ];

        try {
            $id = MongoDatabase::insert('appointments', $row);

            return ['id' => $id, 'reference' => (string) $row['reference_code'], 'stored' => 'mongo', 'error' => ''];
        } catch (Throwable $e) {
            $storedFile = SubmissionStore::append('appointments', $row + ['_error' => $e::class]);
            if ($storedFile === '') {
                throw new \RuntimeException('Appointment could not be saved to any configured store.', 0, $e);
            }

            return [
                'id'        => null,
                'reference' => (string) $row['reference_code'],
                'stored'    => 'file',
                'error'     => $e::class,
            ];
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $rows = MongoDatabase::list('appointments', self::mongoFilters($filters));
        } catch (Throwable) {
            $rows = [];
        }

        $rows = self::mergeFallbackRows($rows, $filters);
        return array_slice($rows, max(0, $offset), max(1, min(500, $limit)));
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        try {
            return MongoDatabase::findById('appointments', $id);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string,mixed>|null */
    public static function findByReference(string $reference): ?array
    {
        try {
            return MongoDatabase::findOne('appointments', ['reference_code' => $reference]);
        } catch (Throwable) {
            return null;
        }
    }

    public static function updateStatus(int $id, string $status, ?string $note = null): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        try {
            $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            if ($note !== null && trim($note) !== '') {
                $data['admin_note'] = mb_substr(trim($note), 0, 1000);
            }

            return MongoDatabase::updateOne('appointments', ['id' => $id], $data);
        } catch (Throwable) {
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            return MongoDatabase::deleteOne('appointments', ['id' => $id]);
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string,int> */
    public static function statusCounts(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        try {
            $rows = MongoDatabase::list('appointments', [], ['created_at' => -1]);
        } catch (Throwable) {
            $rows = [];
        }
        foreach (self::mergeFallbackRows($rows, []) as $row) {
            $status = (string) ($row['status'] ?? 'new');
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /** @param array<string,mixed> $filters */
    public static function count(array $filters = []): int
    {
        try {
            $rows = MongoDatabase::list('appointments', self::mongoFilters($filters));
        } catch (Throwable) {
            $rows = [];
        }
        return count(self::mergeFallbackRows($rows, $filters));
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    private static function mongoFilters(array $filters): array
    {
        $mongo = [];
        foreach (['status', 'type'] as $key) {
            if (!empty($filters[$key])) {
                $mongo[$key] = (string) $filters[$key];
            }
        }
        $date = [];
        if (!empty($filters['from'])) {
            $date['$gte'] = (string) $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $date['$lte'] = (string) $filters['to'] . ' 23:59:59';
        }
        if ($date !== []) {
            $mongo['created_at'] = $date;
        }
        if (!empty($filters['q'])) {
            $regex = ['$regex' => preg_quote((string) $filters['q'], '/'), '$options' => 'i'];
            $mongo['$or'] = array_map(static fn (string $field): array => [$field => $regex], [
                'full_name', 'phone', 'email', 'reference_code',
            ]);
        }

        return $mongo;
    }

    /** @param array<int,array<string,mixed>> $rows @param array<string,mixed> $filters @return array<int,array<string,mixed>> */
    private static function mergeFallbackRows(array $rows, array $filters): array
    {
        try {
            $fallback = SubmissionStore::read('appointments', 0);
        } catch (Throwable) {
            $fallback = [];
        }
        $known = [];
        foreach ($rows as $row) {
            if (isset($row['reference_code'])) {
                $known[(string) $row['reference_code']] = true;
            }
        }
        foreach ($fallback as $row) {
            $reference = (string) ($row['reference_code'] ?? '');
            if ($reference !== '' && isset($known[$reference])) {
                continue;
            }
            if ($reference !== '') {
                $known[$reference] = true;
            }
            unset($row['_stored_at'], $row['_error']);
            $row['id'] = null;
            $row['status'] = (string) ($row['status'] ?? 'new');
            $row['storage_source'] = 'file';
            if (self::matchesFilters($row, $filters)) {
                $rows[] = $row;
            }
        }
        foreach ($rows as &$row) {
            $row['storage_source'] = (string) ($row['storage_source'] ?? 'mongo');
        }
        unset($row);
        usort($rows, static fn (array $a, array $b): int => strcmp(
            (string) ($b['created_at'] ?? ''),
            (string) ($a['created_at'] ?? '')
        ));

        return $rows;
    }

    /** @param array<string,mixed> $row @param array<string,mixed> $filters */
    private static function matchesFilters(array $row, array $filters): bool
    {
        foreach (['status', 'type'] as $key) {
            if (!empty($filters[$key]) && (string) ($row[$key] ?? '') !== (string) $filters[$key]) {
                return false;
            }
        }
        if (!empty($filters['q'])) {
            $haystack = mb_strtolower(implode(' ', array_map(static fn (string $key): string => (string) ($row[$key] ?? ''), [
                'full_name', 'phone', 'email', 'reference_code',
            ])));
            if (!str_contains($haystack, mb_strtolower((string) $filters['q']))) {
                return false;
            }
        }
        $created = substr((string) ($row['created_at'] ?? ''), 0, 10);

        return (empty($filters['from']) || $created >= (string) $filters['from'])
            && (empty($filters['to']) || $created <= (string) $filters['to']);
    }

    public static function generateReference(string $type): string
    {
        $prefix  = $type === 'follow_up' ? 'SHF' : 'SHC';
        $day     = date('ymd');
        $random  = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        return sprintf('%s-%s-%s', $prefix, $day, $random);
    }
}

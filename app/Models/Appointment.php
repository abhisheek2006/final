<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\MongoAppointmentStore;
use App\Services\SubmissionStore;
use Throwable;

/**
 * Appointment persistence. Falls back to storage/submissions JSONL whenever
 * MySQL is unavailable, so a patient request is never lost.
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
     * @return array{id:int|null,reference:string,stored:'db'|'mongo'|'file',error:string}
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
            $id = Database::instance()->insert('appointments', $row);

            if (MongoAppointmentStore::configured() && !MongoAppointmentStore::create($row, 'mirror')) {
                // MySQL remains the primary store; an unavailable mirror must not lose a booking.
                \App\Core\Logger::warning('appointment.mongo_mirror', 'Appointment saved to MySQL but not mirrored to MongoDB');
            }

            return ['id' => $id, 'reference' => (string) $row['reference_code'], 'stored' => 'db', 'error' => ''];
        } catch (Throwable $e) {
            if (MongoAppointmentStore::create($row, 'failover')) {
                return [
                    'id'        => null,
                    'reference' => (string) $row['reference_code'],
                    'stored'    => 'mongo',
                    'error'     => '',
                ];
            }

            $storedFile = SubmissionStore::append('appointments', $row);
            if ($storedFile === '') {
                throw new \RuntimeException('Appointment could not be saved to any configured store.', 0, $e);
            }

            return [
                'id'        => null,
                'reference' => (string) $row['reference_code'],
                'stored'    => 'file',
                'error'     => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = self::buildFilters($filters);

        try {
            $sql = 'SELECT * FROM appointments' . ($where === '' ? '' : ' WHERE ' . $where)
                . ' ORDER BY created_at DESC LIMIT :lim OFFSET :off';

            $dbRows = Database::instance()->select($sql, array_merge($params, [
                'lim' => 500,
                'off' => 0,
            ]));

            // Requests accepted during a database outage remain visible after MySQL recovers.
            $fallback = self::fallbackRows($filters, 0, 0, false);
            $known = array_fill_keys(array_map(
                static fn (array $row): string => (string) ($row['reference_code'] ?? ''),
                $dbRows
            ), true);
            foreach ($fallback as $row) {
                $reference = (string) ($row['reference_code'] ?? '');
                if ($reference === '' || !isset($known[$reference])) {
                    $dbRows[] = $row;
                    if ($reference !== '') {
                        $known[$reference] = true;
                    }
                }
            }
            usort($dbRows, static fn (array $a, array $b): int => strcmp(
                (string) ($b['created_at'] ?? ''),
                (string) ($a['created_at'] ?? '')
            ));

            return array_slice($dbRows, max(0, $offset), max(1, min(500, $limit)));
        } catch (Throwable) {
            return self::fallbackRows($filters, $limit, $offset);
        }
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        try {
            return Database::instance()->first('SELECT * FROM appointments WHERE id = :id', ['id' => $id]);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string,mixed>|null */
    public static function findByReference(string $reference): ?array
    {
        try {
            return Database::instance()->first(
                'SELECT * FROM appointments WHERE reference_code = :r LIMIT 1',
                ['r' => $reference]
            );
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

            return Database::instance()->update('appointments', $data, 'id = :id', ['id' => $id]) >= 0;
        } catch (Throwable) {
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            return Database::instance()->delete('appointments', 'id = :id', ['id' => $id]) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array<string,int> */
    public static function statusCounts(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        try {
            $rows = Database::instance()->select('SELECT status, COUNT(*) AS total FROM appointments GROUP BY status');
            foreach ($rows as $row) {
                $status = (string) $row['status'];
                if (isset($counts[$status])) {
                    $counts[$status] = (int) $row['total'];
                }
            }

            $seen = [];
            $db = Database::instance();
            foreach (self::fallbackRows([], 0, 0, false) as $row) {
                $reference = (string) ($row['reference_code'] ?? '');
                if ($reference !== '' && isset($seen[$reference])) {
                    continue;
                }
                if ($reference !== '' && $db->value(
                    'SELECT 1 FROM appointments WHERE reference_code = :reference LIMIT 1',
                    ['reference' => $reference]
                ) !== null) {
                    $seen[$reference] = true;
                    continue;
                }

                $status = (string) ($row['status'] ?? 'new');
                if (isset($counts[$status])) {
                    $counts[$status]++;
                }
                if ($reference !== '') {
                    $seen[$reference] = true;
                }
            }
        } catch (Throwable) {
            foreach (self::fallbackRows([], 0, 0, true) as $row) {
                $status = (string) ($row['status'] ?? 'new');
                if (isset($counts[$status])) {
                    $counts[$status]++;
                }
            }
        }

        return $counts;
    }

    /** @param array<string,mixed> $filters */
    public static function count(array $filters = []): int
    {
        [$where, $params] = self::buildFilters($filters);

        try {
            $sql = 'SELECT COUNT(*) FROM appointments' . ($where === '' ? '' : ' WHERE ' . $where);

            $dbCount = (int) Database::instance()->value($sql, $params);
            $fallback = self::fallbackRows($filters, 0, 0, false);
            $seen = [];
            foreach ($fallback as $row) {
                $reference = (string) ($row['reference_code'] ?? '');
                if ($reference === '' || isset($seen[$reference])) {
                    continue;
                }
                $exists = Database::instance()->value(
                    'SELECT 1 FROM appointments WHERE reference_code = :reference LIMIT 1',
                    ['reference' => $reference]
                );
                if ($exists === null) {
                    $dbCount++;
                }
                $seen[$reference] = true;
            }

            return $dbCount;
        } catch (Throwable) {
            return count(self::fallbackRows($filters, 0, 0));
        }
    }

    /**
     * Read requests saved to JSONL while MySQL was unavailable.
     * These are visible in the admin list, but have no database ID for status actions.
     *
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    private static function fallbackRows(
        array $filters,
        int $limit,
        int $offset,
        bool $includeMirrors = true
    ): array
    {
        $rows = array_merge(
            MongoAppointmentStore::list($filters, 0, 0, $includeMirrors),
            SubmissionStore::read('appointments', 0)
        );
        $rows = array_map(static function (array $row): array {
            unset($row['_stored_at'], $row['_error']);
            $row['id'] = null;
            $row['status'] = (string) ($row['status'] ?? 'new');
            $row['created_at'] = (string) ($row['created_at'] ?? '');
            $row['storage_source'] = (string) ($row['storage_source'] ?? 'file');

            return $row;
        }, $rows);

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

        usort($rows, static fn (array $a, array $b): int => strcmp(
            (string) ($b['created_at'] ?? ''),
            (string) ($a['created_at'] ?? '')
        ));

        return $limit > 0 ? array_slice($rows, max(0, $offset), $limit) : array_slice($rows, max(0, $offset));
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private static function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $clauses[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['type']) && in_array($filters['type'], ['consultation', 'follow_up'], true)) {
            $clauses[] = 'type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['q'])) {
            $clauses[] = '(full_name LIKE :q OR phone LIKE :q OR email LIKE :q OR reference_code LIKE :q)';
            $params['q'] = '%' . str_replace(['%', '_'], ['\%', '\_'], (string) $filters['q']) . '%';
        }
        if (!empty($filters['from'])) {
            $clauses[] = 'created_at >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $clauses[] = 'created_at <= :to';
            $params['to'] = $filters['to'] . ' 23:59:59';
        }

        return [implode(' AND ', $clauses), $params];
    }

    public static function generateReference(string $type): string
    {
        $prefix  = $type === 'follow_up' ? 'SHF' : 'SHC';
        $day     = date('ymd');
        $random  = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        return sprintf('%s-%s-%s', $prefix, $day, $random);
    }
}

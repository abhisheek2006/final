<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\MongoDatabase;
use App\Services\SubmissionStore;
use Throwable;

/**
 * Website contact-form messages, with the same database/file fallback
 * behaviour as appointments.
 */
final class Message
{
    /**
     * @param array<string,mixed> $data
     * @return array{id:int|null,stored:'mongo'|'file',error:string}
     */
    public static function create(array $data): array
    {
        $row = [
            'name'       => $data['name'],
            'phone'      => $data['phone'],
            'email'      => $data['email'],
            'subject'    => $data['subject'] ?? 'General enquiry',
            'message'    => $data['message'],
            'status'     => 'unread',
            'spam_score' => (int) ($data['spam_score'] ?? 0),
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $data['updated_at'] ?? $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        try {
            $id = MongoDatabase::insert('messages', $row);

            return ['id' => $id, 'stored' => 'mongo', 'error' => ''];
        } catch (Throwable $e) {
            SubmissionStore::append('messages', $row + ['_error' => $e::class]);

            return ['id' => null, 'stored' => 'file', 'error' => $e::class];
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $rows = MongoDatabase::list('messages', self::mongoFilters($filters), ['created_at' => -1]);
        } catch (Throwable) {
            $rows = [];
        }
        $known = [];
        foreach ($rows as $row) {
            $known[self::identity($row)] = true;
        }
        foreach (SubmissionStore::read('messages', 0) as $row) {
            unset($row['_stored_at'], $row['_error']);
            $row['id'] = null;
            $row['storage_source'] = 'file';
            $identity = self::identity($row);
            if (!isset($known[$identity]) && self::matchesFilters($row, $filters)) {
                $rows[] = $row;
                $known[$identity] = true;
            }
        }
        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($rows, max(0, $offset), max(1, min(500, $limit)));
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        try {
            return MongoDatabase::findById('messages', $id);
        } catch (Throwable) {
            return null;
        }
    }

    public static function markRead(int $id): bool
    {
        try {
            return MongoDatabase::updateOne('messages', ['id' => $id], ['status' => 'read', 'updated_at' => date('Y-m-d H:i:s')]);
        } catch (Throwable) {
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        try {
            return MongoDatabase::deleteOne('messages', ['id' => $id]);
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{unread:int,total:int} */
    public static function counts(): array
    {
        try {
            $rows = MongoDatabase::list('messages', []);
        } catch (Throwable) {
            $rows = [];
        }
        $known = [];
        foreach ($rows as $row) {
            $known[self::identity($row)] = true;
        }
        foreach (SubmissionStore::read('messages', 0) as $row) {
            $identity = self::identity($row);
            if (!isset($known[$identity])) {
                $rows[] = $row;
                $known[$identity] = true;
            }
        }
        $total = count($rows);
        $unread = count(array_filter($rows, static fn (array $row): bool => (string) ($row['status'] ?? 'unread') === 'unread'));

        return ['unread' => $unread, 'total' => $total];
    }

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    private static function mongoFilters(array $filters): array
    {
        if (!empty($filters['status']) && in_array($filters['status'], ['unread', 'read', 'archived'], true)) {
            $query['status'] = $filters['status'];
        }
        if (!empty($filters['q'])) {
            $regex = ['$regex' => preg_quote((string) $filters['q'], '/'), '$options' => 'i'];
            $query['$or'] = array_map(static fn (string $field): array => [$field => $regex], ['name', 'email', 'phone', 'subject', 'message']);
        }

        return $query ?? [];
    }

    /** @param array<string,mixed> $row @param array<string,mixed> $filters */
    private static function matchesFilters(array $row, array $filters): bool
    {
        if (!empty($filters['status']) && (string) ($row['status'] ?? 'unread') !== (string) $filters['status']) {
            return false;
        }
        if (!empty($filters['q'])) {
            $haystack = mb_strtolower(implode(' ', array_map(static fn (string $field): string => (string) ($row[$field] ?? ''), ['name', 'email', 'phone', 'subject', 'message'])));

            return str_contains($haystack, mb_strtolower((string) $filters['q']));
        }

        return true;
    }

    /** @param array<string,mixed> $row */
    private static function identity(array $row): string
    {
        return hash('sha256', implode("\0", array_map(
            static fn (string $field): string => (string) ($row[$field] ?? ''),
            ['name', 'phone', 'email', 'subject', 'message', 'created_at']
        )));
    }
}

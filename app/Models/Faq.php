<?php
declare(strict_types=1);

namespace App\Models;

use App\Content\FaqData;
use App\Services\MongoDatabase;
use Throwable;

/**
 * FAQ rows. Reads fall back to the bundled content when the database is not
 * available, so the FAQ page and its JSON-LD always work.
 */
final class Faq
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public static function published(?string $category = null): array
    {
        $rows = array_values(array_filter(self::all(), static fn (array $row): bool => (int) ($row['is_published'] ?? 1) === 1));
        if ($category !== null && $category !== '') {
            $rows = array_values(array_filter($rows, static fn (array $r): bool => $r['category'] === $category));
        }

        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => strcasecmp(trim((string) ($row['question'] ?? '')), 'Do you accept insurance?') !== 0
        ));
    }

    /**
     * Grouped by category for the accordion UI.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::published() as $row) {
            $grouped[(string) ($row['category'] ?? 'General')][] = $row;
        }

        if ($grouped === []) {
            $grouped = FaqData::groups();
        }

        return $grouped;
    }

    /**
     * @return array<int,array{question:string,answer:string}>
     */
    public static function forSchema(): array
    {
        $out = [];

        foreach (self::published() as $row) {
            $out[] = [
                'question' => (string) $row['question'],
                'answer'   => (string) $row['answer'],
            ];
        }

        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        try {
            $rows = MongoDatabase::list('faqs', [], ['category' => 1, 'sort_order' => 1, 'id' => 1]);
            if ($rows !== []) {
                return $rows;
            }

            if (MongoDatabase::findOne('settings', ['key' => 'faqs_seeded']) === null) {
                foreach (FaqData::all() as $row) {
                    self::create($row);
                }
                MongoDatabase::insert('settings', ['key' => 'faqs_seeded', 'created_at' => date('Y-m-d H:i:s')]);

                return MongoDatabase::list('faqs', [], ['category' => 1, 'sort_order' => 1, 'id' => 1]);
            }

            return [];
        } catch (Throwable) {
            return FaqData::all();
        }
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        try {
            return MongoDatabase::findById('faqs', $id);
        } catch (Throwable) {
            return null;
        }
    }

    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        return MongoDatabase::insert('faqs', [
            'question'     => $data['question'],
            'answer'       => $data['answer'],
            'category'     => $data['category'] ?? 'General',
            'sort_order'   => (int) ($data['sort_order'] ?? 0),
            'is_published' => (int) ($data['is_published'] ?? 1),
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string,mixed> $data */
    public static function update(int $id, array $data): int
    {
        return MongoDatabase::updateOne('faqs', ['id' => $id], [
            'question'     => $data['question'],
            'answer'       => $data['answer'],
            'category'     => $data['category'] ?? 'General',
            'sort_order'   => (int) ($data['sort_order'] ?? 0),
            'is_published' => (int) ($data['is_published'] ?? 1),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]) ? 1 : 0;
    }

    public static function delete(int $id): int
    {
        return MongoDatabase::deleteOne('faqs', ['id' => $id]) ? 1 : 0;
    }

    public static function countAll(): int
    {
        try {
            return MongoDatabase::count('faqs');
        } catch (Throwable) {
            return count(FaqData::all());
        }
    }

    /** @return string[] */
    public static function categories(): array
    {
        $categories = [];

        foreach (self::all() as $row) {
            $category = trim((string) ($row['category'] ?? ''));
            if ($category !== '' && !in_array($category, $categories, true)) {
                $categories[] = $category;
            }
        }

        return $categories !== [] ? $categories : FaqData::categories();
    }
}

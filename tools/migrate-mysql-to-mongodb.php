<?php
declare(strict_types=1);

use App\Core\Env;
use App\Services\MongoDatabase;
use App\Services\SubmissionStore;
require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this migration from the command line only.\n");
    exit(1);
}

if (!MongoDatabase::driverAvailable()) {
    fwrite(STDERR, "Enable the PHP mongodb extension, then retry.\n");
    exit(1);
}
if (!MongoDatabase::isAvailable()) {
    fwrite(STDERR, "MongoDB is not reachable. Check MONGODB_URI and the Atlas network access list.\n");
    exit(1);
}

$totals = ['imported' => 0, 'skipped' => 0];
$name = Env::string('LEGACY_MYSQL_DATABASE', '');
$user = Env::string('LEGACY_MYSQL_USERNAME', '');

// MySQL is an optional source only. With no legacy credentials, continue by
// importing JSONL fallback submissions and seeding the default FAQ collection.
if ($name !== '' && $user !== '') {
    $host = Env::string('LEGACY_MYSQL_HOST', 'localhost');
    $port = Env::int('LEGACY_MYSQL_PORT', 3306);
    $pass = Env::string('LEGACY_MYSQL_PASSWORD', '');

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name),
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Throwable $e) {
        fwrite(STDERR, "Could not connect to the configured legacy MySQL source: " . $e::class . "\n");
        exit(1);
    }

    $tables = [
        'users' => 'users',
        'appointments' => 'appointments',
        'messages' => 'messages',
        'faqs' => 'faqs',
    ];
    foreach ($tables as $table => $collection) {
        try {
            $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
        } catch (Throwable) {
            fwrite(STDERR, "Could not read source table {$table}; migration stopped.\n");
            exit(1);
        }

        foreach ($rows as $row) {
            if (MongoDatabase::insertImported($collection, $row)) {
                $totals['imported']++;
            } else {
                $totals['skipped']++;
            }
        }
        fwrite(STDOUT, sprintf("%s: %d source records processed\n", $table, count($rows)));
    }

    try {
        $rows = $pdo->query('SELECT bucket_key, hits, window_start, updated_at FROM `rate_limits`')->fetchAll();
        foreach ($rows as $row) {
            if (MongoDatabase::insertImportedRateLimit($row)) {
                $totals['imported']++;
            } else {
                $totals['skipped']++;
            }
        }
        fwrite(STDOUT, sprintf("rate_limits: %d source records processed\n", count($rows)));
    } catch (Throwable) {
        fwrite(STDERR, "Could not read source table rate_limits; migration stopped.\n");
        exit(1);
    }
} else {
    fwrite(STDOUT, "No legacy MySQL credentials configured; skipping MySQL import.\n");
}

foreach (['appointments' => 'appointments', 'messages' => 'messages'] as $kind => $collection) {
    $rows = SubmissionStore::read($kind, 0);
    foreach ($rows as $row) {
        if (MongoDatabase::importSubmission($collection, $row)) {
            $totals['imported']++;
        } else {
            $totals['skipped']++;
        }
    }
    fwrite(STDOUT, sprintf("%s JSONL fallback: %d records processed\n", $kind, count($rows)));
}

// Creates the bundled FAQ documents once, so a fresh MongoDB database is ready.
\App\Models\Faq::all();

fwrite(STDOUT, sprintf("Migration finished. Imported: %d; already present/skipped: %d.\n", $totals['imported'], $totals['skipped']));

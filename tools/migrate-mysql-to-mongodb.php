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

$host = Env::string('LEGACY_MYSQL_HOST', Env::string('DB_HOST', 'localhost'));
$port = Env::int('LEGACY_MYSQL_PORT', Env::int('DB_PORT', 3306));
$name = Env::string('LEGACY_MYSQL_DATABASE', Env::string('DB_DATABASE', ''));
$user = Env::string('LEGACY_MYSQL_USERNAME', Env::string('DB_USERNAME', ''));
$pass = Env::string('LEGACY_MYSQL_PASSWORD', Env::string('DB_PASSWORD', ''));

if ($name === '' || $user === '') {
    fwrite(STDERR, "Set the source MySQL credentials in LEGACY_MYSQL_* in .env.\n");
    exit(1);
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Could not connect to the source MySQL database: " . $e::class . "\n");
    exit(1);
}

$tables = [
    'users' => 'users',
    'appointments' => 'appointments',
    'messages' => 'messages',
    'faqs' => 'faqs',
];
$totals = ['imported' => 0, 'skipped' => 0];

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

fwrite(STDOUT, sprintf("Migration finished. Imported: %d; already present/skipped: %d.\n", $totals['imported'], $totals['skipped']));

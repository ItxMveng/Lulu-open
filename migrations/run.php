<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script doit être exécuté en CLI.' . PHP_EOL);
}

$pdo = db();
$migrationsPath = __DIR__;

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
);

$executed = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$executed = array_map('strval', $executed);

$files = glob($migrationsPath . DIRECTORY_SEPARATOR . '*.sql') ?: [];
sort($files, SORT_STRING);

foreach ($files as $file) {
    $migration = basename($file);

    if (in_array($migration, $executed, true)) {
        echo "[SKIP] {$migration}" . PHP_EOL;
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException(sprintf('Impossible de lire la migration %s', $migration));
    }

    // NB: en MySQL, le DDL (CREATE/ALTER TABLE) déclenche un COMMIT implicite ;
    // on ne peut donc pas envelopper les migrations de schéma dans une transaction.
    try {
        $pdo->exec($sql);
        $statement = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $statement->execute(['migration' => $migration]);
        echo "[OK] {$migration}" . PHP_EOL;
    } catch (Throwable $throwable) {
        echo "[FAIL] {$migration} - {$throwable->getMessage()}" . PHP_EOL;
        throw $throwable;
    }
}

echo 'Migrations terminées.' . PHP_EOL;
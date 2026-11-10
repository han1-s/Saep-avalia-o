<?php
require __DIR__ . '/backend/config/database.php';

try {
    $pdo = db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS probe (id INTEGER PRIMARY KEY)');
    echo 'DB_OK\n';
    echo getSqlitePath() . "\n";
} catch (Throwable $e) {
    echo 'DB_FAIL: ' . $e->getMessage() . "\n";
}

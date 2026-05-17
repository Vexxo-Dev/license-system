<?php

 
function db_connection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $config = require __DIR__ . '/config.php';
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['db_host'],
            $config['db_name'],
            $config['db_charset']
        );

        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        ensure_database_shape($pdo);
    }

    return $pdo;
}

function ensure_database_shape(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;
    $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'client_id'")->fetch();

    if (!$column) {
        $pdo->exec('ALTER TABLE users ADD COLUMN client_id INT NULL AFTER id');
        $pdo->exec('ALTER TABLE users ADD INDEX idx_users_client_id (client_id)');
    }
}

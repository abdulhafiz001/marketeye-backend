<?php

/**
 * Poll MySQL until a TCP connection + auth succeeds.
 * Env: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 * Optional: DB_WAIT_TIMEOUT (seconds, default 90), DB_WAIT_INTERVAL (seconds, default 2)
 */

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: 3306);
$db = getenv('DB_DATABASE') ?: '';
$user = getenv('DB_USERNAME') ?: '';
$pass = getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : '';
$timeout = (int) (getenv('DB_WAIT_TIMEOUT') ?: 90);
$interval = max(1, (int) (getenv('DB_WAIT_INTERVAL') ?: 2));

if ($db === '' || $user === '') {
    fwrite(STDERR, "[wait-for-db] DB_DATABASE and DB_USERNAME are required.\n");
    exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $db);
$deadline = time() + $timeout;
$attempt = 0;

fwrite(STDOUT, "[wait-for-db] Waiting for MySQL at {$host}:{$port}/{$db} (timeout {$timeout}s)…\n");

while (time() <= $deadline) {
    $attempt++;
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $pdo->query('SELECT 1');
        fwrite(STDOUT, "[wait-for-db] MySQL is ready (attempt {$attempt}).\n");
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDOUT, "[wait-for-db] attempt {$attempt}: ".$e->getMessage()."\n");
        sleep($interval);
    }
}

fwrite(STDERR, "[wait-for-db] ERROR: MySQL at {$host}:{$port} did not become ready within {$timeout}s.\n");
exit(1);

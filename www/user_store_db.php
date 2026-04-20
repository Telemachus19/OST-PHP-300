<?php

final class DBConnection
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new LogicException('Cannot unserialize singleton.');
    }
}

// Temporary compatibility wrapper (to make that i forgot something, nothing breaks really badly)
function get_pdo(): PDO
{
    return DBConnection::getInstance();
}

<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $driver = (string) env('DB_CONNECTION', 'mysql');

        if ($driver === 'sqlite') {
            $database = (string) env('DB_NAME', base_path('database.sqlite'));
            self::$connection = new PDO('sqlite:' . $database);
        } else {
            $host = (string) env('DB_HOST', '127.0.0.1');
            $port = (string) env('DB_PORT', '3306');
            $database = (string) env('DB_NAME', 'lulu_open_v2');
            $charset = (string) env('DB_CHARSET', 'utf8mb4');
            $user = (string) env('DB_USER', 'root');
            $password = (string) env('DB_PASS', '');

            $dsn = sprintf('%s:host=%s;port=%s;dbname=%s;charset=%s', $driver, $host, $port, $database, $charset);
            self::$connection = new PDO($dsn, $user, $password);
        }

        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return self::$connection;
    }
}

function db(): PDO
{
    return Database::connection();
}
<?php

class DB
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        global $db;

        if (self::$pdo === null) {
            $host = $db['host'];
            $port = $db['port'];
            $dbname = $db['db_name'];
            $user = $db['user'];
            $password = $db['password'];

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

            try {
                self::$pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
            } catch (PDOException $e) {
                die('Ошибка подключения к БД: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }
}
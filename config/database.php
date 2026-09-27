<?php
// Local MySQL connection; environment variables can override these defaults.
$host = getenv('FOODFUSION_DB_HOST') ?: '127.0.0.1';
$database = getenv('FOODFUSION_DB_NAME') ?: 'foodfusion';
$username = getenv('FOODFUSION_DB_USER') ?: 'root';
$password = getenv('FOODFUSION_DB_PASSWORD') ?: '';
$socket = getenv('FOODFUSION_DB_SOCKET') ?: '';

$dataSource = $socket !== ''
    ? "mysql:unix_socket=$socket;dbname=$database;charset=utf8mb4"
    : "mysql:host=$host;dbname=$database;charset=utf8mb4";

try {
    $pdo = new PDO(
        $dataSource,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $error) {
    if ($host === '127.0.0.1' && $socket === '') {
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=$database;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $fallbackError) {
            exit('Database connection failed. Please start MySQL and import database/foodfusion.sql.');
        }
    } else {
        exit('Database connection failed. Please start MySQL and import database/foodfusion.sql.');
    }
}

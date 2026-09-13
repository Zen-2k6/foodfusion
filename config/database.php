<?php
// Simple PDO connection for the default XAMPP MySQL settings.
// XAMPP normally uses the root account with no password on a local computer.
$host = getenv('FOODFUSION_DB_HOST') ?: 'localhost';
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
    // A simple message is useful for this local classroom demonstration.
    // A production website should log the real error instead of displaying it.
    exit('Database connection failed. Please start MySQL in XAMPP and import database/foodfusion.sql.');
}

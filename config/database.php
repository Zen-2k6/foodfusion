<?php
// Database configuration with out-of-the-box support for XAMPP on macOS and Windows

$host = getenv('FOODFUSION_DB_HOST') ?: '127.0.0.1';
$port = getenv('FOODFUSION_DB_PORT') ?: '3306';
$database = getenv('FOODFUSION_DB_NAME') ?: 'foodfusion';
$username = getenv('FOODFUSION_DB_USER') ?: 'root';
$password = getenv('FOODFUSION_DB_PASSWORD') ?: '';
$socket = getenv('FOODFUSION_DB_SOCKET') ?: '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

// Candidates for DSN connection in order of preference
$dsnCandidates = [];

if ($socket !== '') {
    $dsnCandidates[] = "mysql:unix_socket=$socket;dbname=$database;charset=utf8mb4";
}

// XAMPP Mac standard socket locations
$macSockets = [
    '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock',
    '/tmp/mysql.sock',
    '/Applications/MAMP/tmp/mysql/mysql.sock',
];
foreach ($macSockets as $sockPath) {
    if (file_exists($sockPath)) {
        $dsnCandidates[] = "mysql:unix_socket=$sockPath;dbname=$database;charset=utf8mb4";
    }
}

// Standard TCP connections
$dsnCandidates[] = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
$dsnCandidates[] = "mysql:host=127.0.0.1;port=3306;dbname=$database;charset=utf8mb4";
$dsnCandidates[] = "mysql:host=localhost;dbname=$database;charset=utf8mb4";

$pdo = null;
$lastException = null;

foreach (array_unique($dsnCandidates) as $dsn) {
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        break;
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!$pdo) {
    exit('Database connection failed. Please ensure MySQL is running in XAMPP and database/foodfusion.sql is imported into a database named "foodfusion".');
}

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
    // Check if MySQL server is reachable without database name
    $serverRunning = false;
    foreach (['127.0.0.1', 'localhost'] as $h) {
        try {
            new PDO("mysql:host=$h;port=$port;charset=utf8mb4", $username, $password, $options);
            $serverRunning = true;
            break;
        } catch (Exception $ex) {}
    }

    echo '<div style="font-family: -apple-system, sans-serif; max-width: 620px; margin: 50px auto; padding: 28px; border: 1px solid #e0d0b0; border-radius: 14px; background: #fffdf7; box-shadow: 0 4px 20px rgba(0,0,0,0.06); line-height: 1.6;">';
    if ($serverRunning) {
        echo '<h2 style="color: #bd4829; margin-top: 0;">Database "foodfusion" Not Found</h2>
            <p>MySQL is running in XAMPP, but the database <strong>foodfusion</strong> has not been created yet.</p>
            <p><strong>Quick Fix in phpMyAdmin:</strong></p>
            <ol>
                <li>Open <a href="http://localhost/phpmyadmin/" target="_blank">http://localhost/phpmyadmin/</a></li>
                <li>Click <strong>"New"</strong> in the left sidebar.</li>
                <li>Enter Database name: <strong>foodfusion</strong> and click <strong>Create</strong>.</li>
                <li>Click the <strong>Import</strong> tab, choose <code>foodfusion/database/foodfusion.sql</code>, and click <strong>Go / Import</strong>.</li>
                <li>Reload this page!</li>
            </ol>';
    } else {
        echo '<h2 style="color: #bd4829; margin-top: 0;">MySQL Server Not Running</h2>
            <p>Cannot connect to MySQL server.</p>
            <p><strong>How to start in XAMPP:</strong></p>
            <ol>
                <li>Open the <strong>XAMPP</strong> app on your Mac.</li>
                <li>Go to the <strong>Manage Servers</strong> tab and click <strong>Start</strong> next to <strong>MySQL Database</strong>.</li>
                <li>Ensure the status light turns green.</li>
                <li>Reload this page!</li>
            </ol>';
    }
    echo '</div>';
    exit;
}

// Verify that core tables have been imported
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'recipes'")->fetch();
    if (!$tableCheck) {
        echo '<div style="font-family: -apple-system, sans-serif; max-width: 620px; margin: 50px auto; padding: 28px; border: 1px solid #e0d0b0; border-radius: 14px; background: #fffdf7; box-shadow: 0 4px 20px rgba(0,0,0,0.06); line-height: 1.6;">
            <h2 style="color: #bd4829; margin-top: 0;">Database Tables Not Imported Yet</h2>
            <p>Connected to database <strong>foodfusion</strong>, but the tables have not been imported yet.</p>
            <p><strong>Quick Fix in phpMyAdmin:</strong></p>
            <ol>
                <li>Open <a href="http://localhost/phpmyadmin/" target="_blank">http://localhost/phpmyadmin/</a></li>
                <li>Click <strong>foodfusion</strong> in the left sidebar.</li>
                <li>Click the <strong>Import</strong> tab at the top.</li>
                <li>Choose <code>foodfusion/database/foodfusion.sql</code> and click <strong>Go / Import</strong>.</li>
                <li>Reload this page!</li>
            </ol>
        </div>';
        exit;
    }
} catch (Exception $e) {
    // Continue
}

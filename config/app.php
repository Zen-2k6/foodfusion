<?php
// Report errors during local development instead of a blank HTTP 500 page
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Compatibility polyfills for PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

// Shared settings used by every page.
define('SITE_NAME', 'FoodFusion');
// Determine base URL: supports standalone server, env override, and XAMPP subdirectories
if (!defined('BASE_URL')) {
    $envBase = getenv('FOODFUSION_BASE_URL');
    if ($envBase !== false && $envBase !== '') {
        define('BASE_URL', rtrim($envBase, '/'));
    } else {
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: '') : '';
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');
        if ($docRoot !== '' && $appRoot !== '' && str_starts_with($appRoot, $docRoot)) {
            $subDir = trim(substr($appRoot, strlen($docRoot)), '/');
            define('BASE_URL', $subDir === '' ? '' : '/' . $subDir);
        } else {
            define('BASE_URL', '');
        }
    }
}

// Start one session for login details, form messages and CSRF tokens.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

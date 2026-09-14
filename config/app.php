<?php
// Shared settings used by every page.
define('SITE_NAME', 'FoodFusion');
// Empty for the local server root; set a prefix for subdirectory hosting.
define('BASE_URL', rtrim(getenv('FOODFUSION_BASE_URL') ?: '', '/'));

// Start one session for login details, form messages and CSRF tokens.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

<?php
// Shared settings used by every page.
define('SITE_NAME', 'FoodFusion');
define('BASE_URL', '/foodfusion');

// Start one session for login details, form messages and CSRF tokens.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';


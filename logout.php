<?php
require_once __DIR__ . '/config/app.php';

if (is_post_request() && verify_csrf_token($_POST['csrf_token'] ?? null)) {
    unset($_SESSION['user']);
    session_regenerate_id(true);
    set_flash('success', 'You have been logged out.');
}

redirect('index.php');

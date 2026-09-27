<?php

// Safely display database or form text inside HTML.
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Remove HTML tags and unnecessary spaces from short text fields.
function clean_text_input(?string $value): string
{
    $cleanValue = strip_tags(trim($value ?? ''));
    return preg_replace('/\s+/u', ' ', $cleanValue) ?? '';
}

function clean_email_input(?string $value): string
{
    $cleanEmail = filter_var(trim($value ?? ''), FILTER_SANITIZE_EMAIL);
    return is_string($cleanEmail) ? strtolower($cleanEmail) : '';
}

function url(string $path = ''): string
{
    return BASE_URL . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_post_request(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to use that feature.');
        redirect('login.php');
    }
}

function is_admin(): bool
{
    return is_logged_in() && current_user()['role'] === 'admin';
}

function home_page(): string
{
    return is_admin() ? 'admin.php' : (is_logged_in() ? 'member.php' : 'index.php');
}

function require_admin(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in with an administrator account to open the dashboard.');
        redirect('login.php');
    }

    if (!is_admin()) {
        set_flash('error', 'This page is only available to administrators.');
        redirect('index.php');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function is_valid_image_url(string $imageUrl): bool
{
    if ($imageUrl === '') {
        return true;
    }

    return filter_var($imageUrl, FILTER_VALIDATE_URL) !== false
        && str_starts_with($imageUrl, 'https://');
}

// Convert the short database value into a visitor-friendly post type.
function community_post_type_label(string $postType): string
{
    return match ($postType) {
        'recipe' => 'Favourite Recipe',
        'tip' => 'Cooking Tip',
        'experience' => 'Culinary Experience',
        default => 'Community Post'
    };
}

function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

<?php
require_once __DIR__ . '/config/app.php';

$error = '';
$token = $_POST['reset_token'] ?? $_GET['token'] ?? '';
$resetRequest = $_SESSION['password_reset'] ?? null;

$validToken = is_string($token)
    && strlen($token) === 64
    && ctype_xdigit($token)
    && $resetRequest
    && $resetRequest['expires_at'] >= time()
    && hash_equals($resetRequest['token_hash'], hash('sha256', $token));

if (is_post_request()) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'The form expired. Please try again.';
    } elseif (!$validToken) {
        $error = 'This reset request is invalid or has expired. Please start again.';
    } elseif (strlen($newPassword) < 8 || !preg_match('/\d/', $newPassword)) {
        $error = 'Password must contain at least 8 characters and one number.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        $statement = $pdo->prepare(
            'UPDATE users
             SET password_hash = ?, failed_attempts = 0, locked_until = NULL
             WHERE user_id = ?'
        );
        $statement->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            $resetRequest['user_id']
        ]);

        unset($_SESSION['password_reset']);
        set_flash('success', 'Your password was reset successfully. You can now log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Choose a new password';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero">
    <div class="container narrow">
        <p class="eyebrow">Secure reset</p>
        <h1>Choose a new password</h1>
        <p>The reset request expires after ten minutes and works only in this browser session.</p>
    </div>
</section>

<section class="section">
    <div class="container form-card narrow">
        <?php if ($error): ?>
            <div class="form-errors" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <div class="verified-account">
                <span aria-hidden="true">&#10003;</span>
                <div><strong>Account verified</strong><small><?= e($resetRequest['email']) ?></small></div>
            </div>
            <form class="stack-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="reset_token" value="<?= e($token) ?>">
                <label>New password
                    <input type="password" name="new_password" minlength="8" required autocomplete="new-password">
                    <small>Use at least 8 characters, including a number.</small>
                </label>
                <label>Confirm new password
                    <input type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
                </label>
                <button class="button" type="submit">Save new password</button>
            </form>
        <?php else: ?>
            <div class="form-errors" role="alert">This reset link is invalid or has expired.</div>
            <a class="button" href="<?= url('forgot_password.php') ?>">Start a new reset</a>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>


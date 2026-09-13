<?php
require_once __DIR__ . '/config/app.php';
require_login();

$errors = [];
if (is_post_request()) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'The form expired. Please try again.';
    }
    if (strlen($newPassword) < 8 || !preg_match('/\d/', $newPassword)) {
        $errors[] = 'The new password must contain at least 8 characters and one number.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'The new passwords do not match.';
    }

    $statement = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
    $statement->execute([current_user()['user_id']]);
    $passwordHash = $statement->fetchColumn();

    if (!password_verify($currentPassword, $passwordHash)) {
        $errors[] = 'Your current password is incorrect.';
    }

    if (!$errors) {
        $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        $update->execute([
            password_hash($newPassword, PASSWORD_DEFAULT),
            current_user()['user_id']
        ]);
        set_flash('success', 'Your password was changed successfully.');
        redirect('profile.php');
    }
}

$pageTitle = 'Change password';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero">
    <div class="container narrow">
        <p class="eyebrow">Account security</p>
        <h1>Change your password</h1>
        <p>Confirm your current password before choosing a new one.</p>
    </div>
</section>

<section class="section">
    <div class="container form-card narrow">
        <?php if ($errors): ?>
            <div class="form-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>New password<input type="password" name="new_password" minlength="8" required autocomplete="new-password"><small>At least 8 characters, including a number.</small></label>
            <label>Confirm new password<input type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></label>
            <button class="button" type="submit">Change password</button>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>


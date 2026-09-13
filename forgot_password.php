<?php
require_once __DIR__ . '/config/app.php';

$error = '';

if (is_post_request()) {
    $email = clean_email_input($_POST['email'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'The form expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $statement = $pdo->prepare('SELECT user_id, email FROM users WHERE email = ?');
        $statement->execute([$email]);
        $account = $statement->fetch();

        if (!$account) {
            $error = 'No FoodFusion account was found with that email address.';
        } else {
            // A real website would email this token. For localhost, it stays in
            // the current session and the member moves directly to the reset form.
            $plainToken = bin2hex(random_bytes(32));
            $_SESSION['password_reset'] = [
                'user_id' => (int) $account['user_id'],
                'email' => $account['email'],
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => time() + 600
            ];

            redirect('reset_password.php?token=' . urlencode($plainToken));
        }
    }
}

$pageTitle = 'Forgot password';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero">
    <div class="container narrow">
        <p class="eyebrow">Account help</p>
        <h1>Reset your password</h1>
        <p>Enter the email used for your FoodFusion account.</p>
    </div>
</section>

<section class="section">
    <div class="container form-card narrow">
        <?php if ($error): ?>
            <div class="form-errors" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <label>Email address
                <input type="email" name="email" maxlength="100" required autocomplete="email" value="<?= e($email ?? '') ?>">
            </label>
            <button class="button" type="submit">Continue securely</button>
            <p class="form-note">Local demonstration: no email is sent. If the account exists, you will continue directly to a secure reset form.</p>
            <a class="arrow-link" href="<?= url('login.php') ?>">&larr; Back to login</a>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>


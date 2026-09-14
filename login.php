<?php
require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    redirect(home_page());
}

$error = '';

if (is_post_request()) {
    $email = clean_email_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'The form expired. Please try again.';
    } else {
        $statement = $pdo->prepare(
            'SELECT users.*, TIMESTAMPDIFF(SECOND, NOW(), locked_until) AS lock_seconds
             FROM users WHERE email = ?'
        );
        $statement->execute([$email]);
        $account = $statement->fetch();

        if ($account) {
            $lockSeconds = max(0, (int) ($account['lock_seconds'] ?? 0));

            if ($lockSeconds > 0) {
                $error = 'Account temporarily locked. Try again in about ' . ceil($lockSeconds / 60) . ' minute(s).';
            } else {
                // Once three minutes pass, give the member three fresh attempts.
                if ($account['locked_until']) {
                    $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?')
                        ->execute([$account['user_id']]);
                    $account['failed_attempts'] = 0;
                }

                if (password_verify($password, $account['password_hash'])) {
                    $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?')
                        ->execute([$account['user_id']]);

                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'user_id' => (int) $account['user_id'],
                        'first_name' => $account['first_name'],
                        'last_name' => $account['last_name'],
                        'email' => $account['email'],
                        'role' => $account['role']
                    ];

                    set_flash('success', 'Welcome back, ' . $account['first_name'] . '!');
                    redirect(home_page());
                }

                $attempts = (int) $account['failed_attempts'] + 1;
                if ($attempts >= 3) {
                    $pdo->prepare(
                        'UPDATE users
                         SET failed_attempts = 3, locked_until = DATE_ADD(NOW(), INTERVAL 3 MINUTE)
                         WHERE user_id = ?'
                    )->execute([$account['user_id']]);
                    $error = 'Three incorrect attempts. Your account is locked for three minutes.';
                } else {
                    $pdo->prepare('UPDATE users SET failed_attempts = ? WHERE user_id = ?')
                        ->execute([$attempts, $account['user_id']]);
                    $error = 'Incorrect email or password. Attempt ' . $attempts . ' of 3.';
                }
            }
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero">
    <div class="container narrow">
        <p class="eyebrow">Welcome back</p>
        <h1>Log in to your account</h1>
        <p>You have three attempts before a three-minute temporary lock.</p>
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
            <label>Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button class="button" type="submit">Log in</button>
            <a class="arrow-link" href="<?= url('forgot_password.php') ?>">Forgot your password?</a>
            <p>New to FoodFusion? <a href="<?= url('register.php') ?>">Create an account</a>.</p>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

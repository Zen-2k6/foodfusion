<?php
require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    redirect(home_page());
}

$errors = [];

if (is_post_request()) {
    $firstName = clean_text_input($_POST['first_name'] ?? '');
    $lastName = clean_text_input($_POST['last_name'] ?? '');
    $email = clean_email_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'The form expired. Please try again.';
    }
    if ($firstName === '' || $lastName === '') {
        $errors[] = 'Please enter your first and last name.';
    }
    if (strlen($firstName) > 50 || strlen($lastName) > 50) {
        $errors[] = 'Names must be no more than 50 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8 || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain at least 8 characters and one number.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'An account already exists with this email address.';
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO users (first_name, last_name, email, password_hash)
                 VALUES (?, ?, ?, ?)'
            );
            $statement->execute([
                $firstName,
                $lastName,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            set_flash('success', 'Account created successfully. You can now log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero">
    <div class="container narrow">
        <p class="eyebrow">Join the community</p>
        <h1>Create your FoodFusion account</h1>
        <p>Share recipes, save favourites and exchange cooking ideas.</p>
    </div>
</section>

<section class="section">
    <div class="container form-card narrow">
        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-row">
                <label>First name
                    <input type="text" name="first_name" maxlength="50" required value="<?= e($firstName ?? '') ?>">
                </label>
                <label>Last name
                    <input type="text" name="last_name" maxlength="50" required value="<?= e($lastName ?? '') ?>">
                </label>
            </div>
            <label>Email address
                <input type="email" name="email" maxlength="100" required value="<?= e($email ?? '') ?>">
            </label>
            <label>Password
                <input type="password" name="password" minlength="8" required autocomplete="new-password">
                <small>At least 8 characters, including a number.</small>
            </label>
            <label>Confirm password
                <input type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
            </label>
            <button class="button" type="submit">Create account</button>
            <p>Already a member? <a href="<?= url('login.php') ?>">Log in here</a>.</p>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

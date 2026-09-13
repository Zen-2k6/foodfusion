<?php
require_once __DIR__ . '/config/app.php';
require_login();

$errors = [];
$userId = current_user()['user_id'];

if (is_post_request()) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'The form expired. Please try again.';
    }
    if ($firstName === '' || $lastName === '') {
        $errors[] = 'Please enter your first and last name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ?');
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            $errors[] = 'That email address is already being used.';
        } else {
            $update = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?');
            $update->execute([$firstName, $lastName, $email, $userId]);
            $_SESSION['user']['first_name'] = $firstName;
            $_SESSION['user']['last_name'] = $lastName;
            $_SESSION['user']['email'] = $email;
            set_flash('success', 'Your profile was updated.');
            redirect('profile.php');
        }
    }
}

$statement = $pdo->prepare('SELECT first_name, last_name, email, role, created_at FROM users WHERE user_id = ?');
$statement->execute([$userId]);
$account = $statement->fetch();

$stats = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM recipes WHERE user_id = ?) AS recipes,
        (SELECT COUNT(*) FROM comments WHERE user_id = ?) AS comments,
        (SELECT COUNT(*) FROM interactions WHERE user_id = ? AND interaction_type = 'save') AS saved"
);
$stats->execute([$userId, $userId, $userId]);
$stats = $stats->fetch();

$pageTitle = 'My profile';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero">
    <div class="container">
        <p class="eyebrow">Your FoodFusion space</p>
        <h1>Hello, <?= e($account['first_name']) ?>.</h1>
        <p>Manage your account details and see your community activity.</p>
    </div>
</section>

<section class="section">
    <div class="container profile-layout">
        <aside class="profile-summary">
            <div class="initial-avatar coral"><?= e(strtoupper(substr($account['first_name'], 0, 1) . substr($account['last_name'], 0, 1))) ?></div>
            <h2><?= e($account['first_name'] . ' ' . $account['last_name']) ?></h2>
            <p>Member since <?= e(date('F Y', strtotime($account['created_at']))) ?></p>
            <div class="stats-grid"><div><strong><?= (int) $stats['recipes'] ?></strong><span>Recipes</span></div><div><strong><?= (int) $stats['comments'] ?></strong><span>Comments</span></div><div><strong><?= (int) $stats['saved'] ?></strong><span>Saved</span></div></div>
        </aside>
        <div class="form-card">
            <h2>Account information</h2>
            <?php if ($errors): ?><div class="form-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <form class="stack-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-row">
                    <label>First name<input type="text" name="first_name" maxlength="50" value="<?= e($firstName ?? $account['first_name']) ?>" required></label>
                    <label>Last name<input type="text" name="last_name" maxlength="50" value="<?= e($lastName ?? $account['last_name']) ?>" required></label>
                </div>
                <label>Email address<input type="email" name="email" maxlength="100" value="<?= e($email ?? $account['email']) ?>" required></label>
                <button class="button" type="submit">Save changes</button>
                <a class="arrow-link" href="<?= url('change-password.php') ?>">Change my password &rarr;</a>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

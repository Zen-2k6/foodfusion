<?php
require_once __DIR__ . '/config/app.php';

$errors = [];
if (is_post_request()) {
    $name = clean_text_input($_POST['name'] ?? '');
    $email = clean_email_input($_POST['email'] ?? '');
    $subject = clean_text_input($_POST['subject'] ?? '');
    $message = trim(strip_tags($_POST['message'] ?? ''));

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'The form expired. Please try again.';
    }
    if ($name === '' || $subject === '' || $message === '') {
        $errors[] = 'Please complete every field.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        $statement = $pdo->prepare(
            'INSERT INTO contact_messages (user_id, name, email, subject, message)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([
            current_user()['user_id'] ?? null,
            $name, $email, $subject, $message
        ]);
        set_flash('success', 'Thank you. Your message has been received.');
        redirect('contact.php');
    }
}

$user = current_user();
$pageTitle = 'Contact us';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero contact-hero">
    <div class="container">
        <p class="eyebrow light">Let us talk food</p>
        <h1>We would love to hear from you.</h1>
        <p>Send an enquiry, request a recipe or share feedback about FoodFusion.</p>
    </div>
</section>

<section class="section">
    <div class="container contact-layout">
        <aside>
            <p class="eyebrow">Get in touch</p>
            <h2>A friendly kitchen starts with conversation.</h2>
            <p>We aim to respond to classroom demonstration messages within two working days.</p>
            <div class="contact-note"><strong>Email</strong><a href="mailto:hello@foodfusion.test">hello@foodfusion.test</a></div>
            <div class="contact-note"><strong>Community hours</strong><span>Monday-Friday, 9:00 AM-5:00 PM</span></div>
        </aside>

        <div class="form-card">
            <?php if ($errors): ?>
                <div class="form-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form class="stack-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <label>Your name<input type="text" name="name" maxlength="100" value="<?= e($name ?? ($user ? $user['first_name'] . ' ' . $user['last_name'] : '')) ?>" required></label>
                <label>Email address<input type="email" name="email" maxlength="100" value="<?= e($email ?? ($user['email'] ?? '')) ?>" required></label>
                <label>What is this about?
                    <select name="subject" required>
                        <option value="">Choose a subject</option>
                        <?php foreach (['General enquiry', 'Recipe request', 'Website feedback', 'Community support'] as $choice): ?>
                            <option value="<?= e($choice) ?>" <?= ($subject ?? '') === $choice ? 'selected' : '' ?>><?= e($choice) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Your message<textarea name="message" rows="7" maxlength="3000" required><?= e($message ?? '') ?></textarea></label>
                <button class="button" type="submit">Send message</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

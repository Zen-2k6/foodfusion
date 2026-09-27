<?php
require_once __DIR__ . '/config/app.php';

$errors = [];
$user = current_user();

// Admin reply handler
if (is_post_request() && isset($_POST['admin_action']) && $_POST['admin_action'] === 'reply_message') {
    require_admin();
    $messageId = filter_var($_POST['message_id'] ?? null, FILTER_VALIDATE_INT);
    $replyText = trim(strip_tags($_POST['reply_text'] ?? ''));

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
    } elseif (!$messageId || $replyText === '') {
        set_flash('error', 'Please write a reply before submitting.');
    } else {
        $stmt = $pdo->prepare(
            'UPDATE contact_messages
             SET reply_text = ?, replied_at = NOW(), replied_by = ?
             WHERE message_id = ?'
        );
        $stmt->execute([$replyText, current_user()['user_id'], $messageId]);
        set_flash('success', 'Your reply has been saved and sent.');
    }
    redirect('contact.php#admin-messages');
}

// User / Visitor contact submission
if (is_post_request() && !isset($_POST['admin_action'])) {
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
            $user['user_id'] ?? null,
            $name, $email, $subject, $message
        ]);
        set_flash('success', 'Thank you! Your message and feedback have been received. We will respond shortly.');
        redirect('contact.php');
    }
}

// Fetch messages for admin or member
$adminMessages = [];
if (is_admin()) {
    $adminMessages = $pdo->query(
        "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) AS responder_name
         FROM contact_messages m
         LEFT JOIN users u ON u.user_id = m.replied_by
         ORDER BY (m.reply_text IS NULL) DESC, m.created_at DESC"
    )->fetchAll();
}

$myMessages = [];
if (is_logged_in() && !is_admin()) {
    $stmt = $pdo->prepare(
        "SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) AS responder_name
         FROM contact_messages m
         LEFT JOIN users u ON u.user_id = m.replied_by
         WHERE m.user_id = ?
         ORDER BY m.created_at DESC"
    );
    $stmt->execute([$user['user_id']]);
    $myMessages = $stmt->fetchAll();
}

$pageTitle = 'Contact Us';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero contact-hero">
    <div class="container">
        <p class="eyebrow light">Let us talk food</p>
        <h1>We would love to hear from you.</h1>
        <p>Send an enquiry, request a recipe, or share your valuable feedback about FoodFusion.</p>
    </div>
</section>

<!-- Admin Inbox & Reply Section -->
<?php if (is_admin()): ?>
<section class="section" id="admin-messages" style="background: #fdfaf6; border-bottom: 2px solid var(--line);">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin Inbox</p>
                <h2>Visitor &amp; Member Feedback (<?= count($adminMessages) ?>)</h2>
            </div>
            <span style="font-size: .85rem; color: var(--ink-soft);">Review messages and reply directly below</span>
        </div>

        <?php if ($adminMessages): ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($adminMessages as $msg): ?>
                    <article class="form-card" style="margin: 0; padding: 22px; <?= empty($msg['reply_text']) ? 'border-left: 4px solid var(--coral);' : 'border-left: 4px solid #2e7d32;' ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                            <div>
                                <h3 style="margin: 0; font-size: 1.15rem;"><?= e($msg['subject']) ?></h3>
                                <p style="margin: 2px 0 0; font-size: .85rem; color: var(--ink-soft);">
                                    From <strong><?= e($msg['name']) ?></strong> (<?= e($msg['email']) ?>)
                                    <?php if ($msg['user_id']): ?>
                                        <span class="status status-approved" style="padding: 2px 6px; font-size: .7rem;">Registered Member</span>
                                    <?php else: ?>
                                        <span class="status status-pending" style="padding: 2px 6px; font-size: .7rem;">Visitor</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <time style="font-size: .8rem; color: var(--ink-soft);"><?= e(date('j M Y - g:i A', strtotime($msg['created_at']))) ?></time>
                        </div>

                        <!-- Message Content -->
                        <div style="background: var(--paper); padding: 14px; border-radius: 8px; margin: 10px 0; border: 1px solid var(--line);">
                            <p style="margin: 0; color: var(--ink);"><?= nl2br(e($msg['message'])) ?></p>
                        </div>

                        <!-- Existing Reply Display -->
                        <?php if (!empty($msg['reply_text'])): ?>
                            <div style="background: #eef7ee; padding: 14px; border-radius: 8px; margin-top: 10px; border: 1px solid #c8e6c9;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <strong style="color: #2e7d32; font-size: .85rem;">&#x2714; Replied by <?= e($msg['responder_name'] ?: 'Admin') ?></strong>
                                    <time style="font-size: .78rem; color: #2e7d32;"><?= e(date('j M Y, g:i A', strtotime($msg['replied_at']))) ?></time>
                                </div>
                                <p style="margin: 0; color: #1b5e20; font-size: .9rem;"><?= nl2br(e($msg['reply_text'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Reply Form -->
                        <details style="margin-top: 12px;" <?= empty($msg['reply_text']) ? 'open' : '' ?>>
                            <summary style="cursor: pointer; font-size: .85rem; font-weight: 700; color: var(--coral-dark);">
                                <?= empty($msg['reply_text']) ? 'Write a Reply &rarr;' : 'Edit Reply &rarr;' ?>
                            </summary>
                            <form method="post" action="<?= url('contact.php#admin-messages') ?>" style="margin-top: 10px;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="admin_action" value="reply_message">
                                <input type="hidden" name="message_id" value="<?= (int) $msg['message_id'] ?>">
                                <label style="font-size: .85rem;">Admin Reply to <?= e($msg['name']) ?>:
                                    <textarea name="reply_text" rows="3" required placeholder="Type your response here..."><?= e($msg['reply_text'] ?? '') ?></textarea>
                                </label>
                                <button type="submit" class="button button-small" style="margin-top: 6px;">Send Reply</button>
                            </form>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--ink-soft);">No messages or feedback received yet.</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- Member My Messages & Replies Section -->
<?php if ($myMessages): ?>
<section class="section section-tint" id="my-messages">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Your Messages</p>
                <h2>My Submitted Enquiries &amp; Responses</h2>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 14px;">
            <?php foreach ($myMessages as $myMsg): ?>
                <article class="form-card" style="margin: 0; padding: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <h3 style="margin: 0; font-size: 1.05rem;"><?= e($myMsg['subject']) ?></h3>
                        <time style="font-size: .78rem; color: var(--ink-soft);"><?= e(date('j M Y', strtotime($myMsg['created_at']))) ?></time>
                    </div>
                    <p style="margin: 0 0 10px; color: var(--ink-soft);"><?= nl2br(e($myMsg['message'])) ?></p>

                    <?php if (!empty($myMsg['reply_text'])): ?>
                        <div style="background: #eef7ee; padding: 12px; border-radius: 8px; border-left: 3px solid #2e7d32;">
                            <strong style="color: #2e7d32; font-size: .82rem;">FoodFusion Response (<?= e(date('j M Y', strtotime($myMsg['replied_at']))) ?>):</strong>
                            <p style="margin: 4px 0 0; color: #1b5e20; font-size: .88rem;"><?= nl2br(e($myMsg['reply_text'])) ?></p>
                        </div>
                    <?php else: ?>
                        <span class="status status-pending" style="font-size: .75rem;">Awaiting staff reply</span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Contact Form Section -->
<section class="section">
    <div class="container contact-layout">
        <aside>
            <p class="eyebrow">Get in touch</p>
            <h2>A friendly kitchen starts with conversation.</h2>
            <p>Whether you have a question about a recipe, would like to recommend a cooking resource, or share feedback, we'd love to hear from you.</p>
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
                <label>Your message<textarea name="message" rows="7" maxlength="3000" placeholder="Type your feedback, question, or suggestions..." required><?= e($message ?? '') ?></textarea></label>
                <button class="button" type="submit">Send message</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

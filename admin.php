<?php
require_once __DIR__ . '/config/app.php';
require_admin();

// Handle Admin POST Actions
if (is_post_request()) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
        redirect('admin.php');
    }

    $action = $_POST['action'] ?? '';
    $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);

    // Moderation: Approve / Reject community post
    if ($action === 'approve_post' || $action === 'reject_post') {
        if ($recordId) {
            $newStatus = $action === 'approve_post' ? 'approved' : 'rejected';
            $statement = $pdo->prepare('UPDATE community_posts SET status = ? WHERE post_id = ?');
            $statement->execute([$newStatus, $recordId]);
            set_flash('success', 'Community post ' . $newStatus . '.');
        }
        redirect('admin.php#community-posts');
    }

    // Feature / Unfeature recipe toggle
    if ($action === 'toggle_featured_recipe') {
        if ($recordId) {
            $stmt = $pdo->prepare('UPDATE recipes SET is_featured = NOT is_featured WHERE recipe_id = ?');
            $stmt->execute([$recordId]);
            set_flash('success', 'Recipe featured status updated.');
        }
        redirect('admin.php#featured-recipes');
    }

    // Add upcoming event
    if ($action === 'add_event') {
        $title = clean_text_input($_POST['event_title'] ?? '');
        $description = trim(strip_tags($_POST['event_description'] ?? ''));
        $eventDate = clean_text_input($_POST['event_date'] ?? '');
        $location = clean_text_input($_POST['event_location'] ?? '');
        $imagePath = trim($_POST['event_image'] ?? '');

        if ($title === '' || $eventDate === '' || $location === '' || $description === '') {
            set_flash('error', 'Please fill in all event details.');
        } else {
            if ($imagePath === '') {
                $imagePath = 'https://images.unsplash.com/photo-1556761223-4c4282c73f77?auto=format&fit=crop&w=1200&q=80';
            }
            $ins = $pdo->prepare(
                'INSERT INTO events (user_id, title, description, event_date, location, image_path)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([current_user()['user_id'], $title, $description, $eventDate, $location, $imagePath]);
            set_flash('success', 'New cooking event added successfully.');
        }
        redirect('admin.php#events');
    }

    // Delete upcoming event
    if ($action === 'delete_event') {
        if ($recordId) {
            $pdo->prepare('DELETE FROM events WHERE event_id = ?')->execute([$recordId]);
            set_flash('success', 'Event removed.');
        }
        redirect('admin.php#events');
    }

    // Direct reply to contact feedback
    if ($action === 'reply_message') {
        $msgId = filter_var($_POST['message_id'] ?? null, FILTER_VALIDATE_INT);
        $replyText = trim($_POST['reply_text'] ?? '');
        if ($msgId && $replyText !== '') {
            $stmt = $pdo->prepare('UPDATE contact_messages SET reply_text = ?, replied_at = NOW(), replied_by = ? WHERE message_id = ?');
            $stmt->execute([$replyText, current_user()['user_id'], $msgId]);
            set_flash('success', 'Reply sent successfully.');
        }
        redirect('admin.php#messages');
    }

    redirect('admin.php');
}

// Queries for Admin Home Overview
$counts = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users WHERE role = 'member') AS users,
        (SELECT COUNT(*) FROM recipes WHERE status = 'published') AS recipes,
        (SELECT COUNT(*) FROM resources) AS resources,
        (SELECT COUNT(*) FROM community_posts WHERE status = 'pending') AS pending_posts,
        (SELECT COUNT(*) FROM contact_messages WHERE reply_text IS NULL) AS unreplied_messages,
        (SELECT COUNT(*) FROM contact_messages) AS total_messages"
)->fetch();

// Recipes for Featured Management
$allRecipes = $pdo->query(
    "SELECT recipe_id, title, cuisine_type, difficulty, image_path, is_featured, status, created_at
     FROM recipes
     WHERE status = 'published'
     ORDER BY is_featured DESC, title ASC"
)->fetchAll();

// Upcoming Events
$events = $pdo->query(
    'SELECT * FROM events ORDER BY event_date ASC'
)->fetchAll();

// Pending & Recent Community Posts for Moderation
$communityPosts = $pdo->query(
    "SELECT p.post_id, p.title, p.post_type, p.status, p.content, p.image_path, p.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS author_name
     FROM community_posts p
     INNER JOIN users u ON u.user_id = p.user_id
     ORDER BY FIELD(p.status, 'pending', 'approved', 'rejected'), p.created_at DESC
     LIMIT 12"
)->fetchAll();

// Registered Users
$recentUsers = $pdo->query(
    'SELECT user_id, first_name, last_name, email, role, created_at
     FROM users
     ORDER BY created_at DESC'
)->fetchAll();

// Recent Messages
$recentMessages = $pdo->query(
    'SELECT message_id, name, email, subject, message, reply_text, replied_at, created_at
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 8'
)->fetchAll();

$pageTitle = 'Dashboard Overview';
require __DIR__ . '/includes/admin-header.php';
?>

<!-- Admin Page Header -->
<div class="admin-header-title">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?= e($user['first_name']) ?>! Real-time metrics, content showcase, event scheduling, and community oversight.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a class="button button-small" href="<?= url('manage-recipes.php#recipe-form') ?>">+ Add Recipe</a>
            <a class="button button-small button-light" href="<?= url('manage-resources.php#resource-form') ?>">+ Add Resource</a>
        </div>
    </div>
</div>

<!-- Section Navigation Shortcuts -->
<div class="admin-section-nav" aria-label="Quick section navigation">
    <a class="admin-section-pill" href="#featured-recipes">★ Featured Recipes</a>
    <a class="admin-section-pill" href="#events">📅 Cooking Events (<?= count($events) ?>)</a>
    <a class="admin-section-pill" href="#community-posts">
        🛡️ Post Moderation
        <?php if ((int)$counts['pending_posts'] > 0): ?>
            <span style="color: #b78103; font-weight: 800;">(<?= (int)$counts['pending_posts'] ?>)</span>
        <?php endif; ?>
    </a>
    <a class="admin-section-pill" href="#users">👥 User Directory (<?= count($recentUsers) ?>)</a>
    <a class="admin-section-pill" href="#messages">
        📬 Inquiries
        <?php if ((int)$counts['unreplied_messages'] > 0): ?>
            <span style="color: var(--coral-dark); font-weight: 800;">(<?= (int)$counts['unreplied_messages'] ?>)</span>
        <?php endif; ?>
    </a>
</div>

<!-- Metrics Cards Grid -->
<div class="admin-metrics-grid">
    <div class="admin-metric-card">
        <div>
            <span class="admin-metric-label">Registered Members</span>
            <div class="admin-metric-value"><?= (int) $counts['users'] ?></div>
        </div>
        <div class="admin-metric-icon icon-teal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
    </div>

    <div class="admin-metric-card">
        <div>
            <span class="admin-metric-label">Published Recipes</span>
            <div class="admin-metric-value"><?= (int) $counts['recipes'] ?></div>
        </div>
        <div class="admin-metric-icon icon-coral">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
    </div>

    <div class="admin-metric-card">
        <div>
            <span class="admin-metric-label">Curated Resources</span>
            <div class="admin-metric-value"><?= (int) $counts['resources'] ?></div>
        </div>
        <div class="admin-metric-icon icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
        </div>
    </div>

    <div class="admin-metric-card" style="<?= (int)$counts['pending_posts'] > 0 ? 'border-color: #ffe082; background: #fffdf8;' : '' ?>">
        <div>
            <span class="admin-metric-label">Posts Awaiting Review</span>
            <div class="admin-metric-value" style="<?= (int)$counts['pending_posts'] > 0 ? 'color: #b78103;' : '' ?>"><?= (int) $counts['pending_posts'] ?></div>
        </div>
        <div class="admin-metric-icon icon-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        </div>
    </div>

    <div class="admin-metric-card" style="<?= (int)$counts['unreplied_messages'] > 0 ? 'border-color: #fccbbe; background: #fffbf9;' : '' ?>">
        <div>
            <span class="admin-metric-label">Unreplied Inquiries</span>
            <div class="admin-metric-value" style="<?= (int)$counts['unreplied_messages'] > 0 ? 'color: var(--coral-dark);' : '' ?>"><?= (int) $counts['unreplied_messages'] ?></div>
        </div>
        <div class="admin-metric-icon icon-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
        </div>
    </div>
</div>

<!-- Section 1: Featured Recipes Management -->
<section class="admin-card" id="featured-recipes">
    <div class="admin-card-header">
        <div>
            <p class="eyebrow" style="margin-bottom: 2px;">Homepage Showcase</p>
            <h2>Manage Featured Recipes</h2>
            <p>Click the star button to instantly promote or unpromote recipes on the visitor and member homepages.</p>
        </div>
        <div class="admin-card-actions">
            <a class="button button-small" href="<?= url('manage-recipes.php#recipe-form') ?>">+ Add Recipe</a>
            <a class="button button-small button-outline" href="<?= url('manage-recipes.php') ?>">All Recipes &rarr;</a>
        </div>
    </div>

    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Cover</th>
                    <th>Recipe Title</th>
                    <th>Cuisine</th>
                    <th>Difficulty</th>
                    <th>Homepage Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allRecipes as $rec): ?>
                <tr>
                    <td>
                        <img class="admin-table-img" src="<?= e(str_starts_with($rec['image_path'], 'http') ? $rec['image_path'] : url($rec['image_path'])) ?>" alt="">
                    </td>
                    <td>
                        <strong><a href="<?= url('recipe.php?id=' . $rec['recipe_id']) ?>" target="_blank" style="text-decoration: none; color: var(--ink);"><?= e($rec['title']) ?></a></strong>
                    </td>
                    <td><span class="status status-published" style="background: #eef2f0; color: var(--ink);"><?= e($rec['cuisine_type']) ?></span></td>
                    <td><?= e($rec['difficulty']) ?></td>
                    <td>
                        <form method="post" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="toggle_featured_recipe">
                            <input type="hidden" name="record_id" value="<?= (int) $rec['recipe_id'] ?>">
                            <button type="submit" class="btn-star-featured <?= $rec['is_featured'] ? 'is-featured' : '' ?>">
                                <?= $rec['is_featured'] ? '★ Featured on Home' : '☆ Click to Feature' ?>
                            </button>
                        </form>
                    </td>
                    <td style="text-align: right;">
                        <a class="button button-small button-outline" style="padding: 4px 10px; font-size: .8rem;" href="<?= url('manage-recipes.php?action=edit&id=' . $rec['recipe_id'] . '#recipe-form') ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Section 2: Upcoming Cooking Events -->
<section class="admin-card" id="events">
    <div class="admin-card-header">
        <div>
            <p class="eyebrow" style="margin-bottom: 2px;">Workshops &amp; Gatherings</p>
            <h2>Cooking Events Schedule</h2>
            <p>Schedule seasonal masterclasses and live cooking sessions visible to visitors and members.</p>
        </div>
        <button class="button button-small" type="button" onclick="document.getElementById('add-event-drawer').toggleAttribute('hidden');">+ Schedule New Event</button>
    </div>

    <!-- Add Event Drawer Form -->
    <div id="add-event-drawer" hidden style="background: #fdfaf6; padding: 22px; border-radius: 14px; margin-bottom: 24px; border: 1px solid var(--line);">
        <h3 style="margin-top: 0; font-size: 1.25rem;">Schedule a Cooking Event</h3>
        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add_event">
            
            <div class="form-row">
                <label>Event Title
                    <input type="text" name="event_title" maxlength="150" placeholder="e.g. Traditional Burmese Mohinga Masterclass" required>
                </label>
                <label>Date &amp; Time
                    <input type="datetime-local" name="event_date" required>
                </label>
            </div>

            <div class="form-row">
                <label>Location / Venue
                    <input type="text" name="event_location" maxlength="150" placeholder="e.g. FoodFusion Kitchen Lab / Online Zoom" required>
                </label>
                <label>Banner Image URL (optional)
                    <input type="url" name="event_image" placeholder="https://images.unsplash.com/photo-...">
                </label>
            </div>

            <label>Description &amp; Agenda
                <textarea name="event_description" rows="3" placeholder="Workshop overview, what participants will cook, prerequisites..." required></textarea>
            </label>

            <button class="button" type="submit">Publish Event</button>
        </form>
    </div>

    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Event Title</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <tr>
                    <td style="white-space: nowrap;">
                        <strong><?= e(date('j M Y', strtotime($ev['event_date']))) ?></strong>
                        <small style="color: var(--ink-soft);"><?= e(date('g:i A', strtotime($ev['event_date']))) ?></small>
                    </td>
                    <td><strong><?= e($ev['title']) ?></strong></td>
                    <td><?= e($ev['location']) ?></td>
                    <td><small><?= e(substr($ev['description'], 0, 75)) ?>...</small></td>
                    <td style="text-align: right;">
                        <form method="post" onsubmit="return confirm('Remove this event?');" style="margin: 0; display: inline-block;">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_event">
                            <input type="hidden" name="record_id" value="<?= (int) $ev['event_id'] ?>">
                            <button type="submit" class="danger" style="padding: 5px 10px; font-size: .8rem;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Section 3: Community Posts Moderation -->
<section class="admin-card" id="community-posts">
    <div class="admin-card-header">
        <div>
            <p class="eyebrow" style="margin-bottom: 2px;">Community Quality &amp; Safety</p>
            <h2>Community Posts Moderation</h2>
            <p>Review recipes and cooking stories submitted by members before they display publicly on the community feed.</p>
        </div>
        <a class="button button-small button-outline" href="<?= url('community.php') ?>" target="_blank">View Public Feed &rarr;</a>
    </div>

    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Post Title &amp; Details</th>
                    <th>Author</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($communityPosts as $post): ?>
                <tr>
                    <td>
                        <strong><a href="<?= url('community-post.php?id=' . $post['post_id']) ?>" target="_blank" style="text-decoration: none; color: var(--ink);"><?= e($post['title']) ?></a></strong>
                        <small style="color: var(--ink-soft);"><?= e(date('j M Y', strtotime($post['created_at']))) ?> &middot; <?= e(substr($post['content'], 0, 70)) ?>...</small>
                    </td>
                    <td><?= e($post['author_name']) ?></td>
                    <td><span class="status" style="background: #eef2ef; color: var(--ink);"><?= e(community_post_type_label($post['post_type'])) ?></span></td>
                    <td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                    <td style="text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end;">
                            <a class="button button-small button-outline" style="padding: 4px 9px; font-size: .78rem;" href="<?= url('community-post.php?id=' . $post['post_id']) ?>" target="_blank">View</a>
                            <?php if ($post['status'] !== 'approved'): ?>
                                <form method="post" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="approve_post">
                                    <input type="hidden" name="record_id" value="<?= (int) $post['post_id'] ?>">
                                    <button type="submit" style="background: #2e7d32; color: #fff; border: 0; padding: 4px 10px; font-size: .78rem; border-radius: 6px;">&#x2714; Approve</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($post['status'] !== 'rejected'): ?>
                                <form method="post" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="reject_post">
                                    <input type="hidden" name="record_id" value="<?= (int) $post['post_id'] ?>">
                                    <button class="danger" type="submit" style="padding: 4px 10px; font-size: .78rem;">Reject</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Section 4: Two Columns: Accounts & Messages -->
<div class="admin-two-column">
    <!-- Registered Users Directory -->
    <section class="admin-card" id="users" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <div>
                <p class="eyebrow" style="margin-bottom: 2px;">Directory</p>
                <h2>Member Accounts (<?= count($recentUsers) ?>)</h2>
            </div>
        </div>
        <div class="admin-list user-list" style="max-height: 480px; overflow-y: auto;">
            <?php foreach ($recentUsers as $recentUser): ?>
                <article style="padding: 12px 0;">
                    <div class="mini-avatar" style="<?= $recentUser['role'] === 'admin' ? 'background: var(--ink);' : '' ?>">
                        <?= e(strtoupper(substr($recentUser['first_name'], 0, 1) . substr($recentUser['last_name'], 0, 1))) ?>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong><?= e($recentUser['first_name'] . ' ' . $recentUser['last_name']) ?></strong>
                            <span class="status <?= $recentUser['role'] === 'admin' ? 'status-approved' : 'status-pending' ?>" style="font-size: .68rem;">
                                <?= ucfirst($recentUser['role']) ?>
                            </span>
                        </div>
                        <span style="font-size: .82rem; color: var(--ink-soft);"><?= e($recentUser['email']) ?></span>
                        <small style="color: var(--ink-soft); font-size: .72rem;">Joined <?= e(date('j M Y', strtotime($recentUser['created_at']))) ?></small>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Feedback & Inquiry Messages with Direct Reply -->
    <section class="admin-card" id="messages" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <div>
                <p class="eyebrow" style="margin-bottom: 2px;">Communication</p>
                <h2>Visitor &amp; Member Inquiries</h2>
            </div>
            <a class="button button-small button-outline" href="<?= url('contact.php#admin-messages') ?>">Full Inbox &rarr;</a>
        </div>
        <div class="admin-list" style="max-height: 480px; overflow-y: auto;">
            <?php if ($recentMessages): ?>
                <?php foreach ($recentMessages as $msg): ?>
                    <article style="padding: 14px; border-radius: 10px; background: #fafafa; border: 1px solid var(--line); margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                            <div>
                                <strong style="font-size: .92rem;"><?= e($msg['subject']) ?></strong>
                                <span style="font-size: .78rem; color: var(--ink-soft);"><?= e($msg['name']) ?> (<?= e($msg['email']) ?>)</span>
                            </div>
                            <?php if (!empty($msg['reply_text'])): ?>
                                <span class="status status-approved" style="font-size: .68rem;">Replied</span>
                            <?php else: ?>
                                <span class="status status-pending" style="font-size: .68rem;">Needs Reply</span>
                            <?php endif; ?>
                        </div>
                        <p style="margin: 8px 0; font-size: .84rem; color: var(--ink);"><?= e(substr($msg['message'], 0, 110)) ?>...</p>

                        <?php if (!empty($msg['reply_text'])): ?>
                            <div style="background: #eef7ee; padding: 8px 12px; border-radius: 6px; border-left: 3px solid #2e7d32; font-size: .8rem; color: #1b5e20;">
                                <strong>&#x2714; Replied:</strong> <?= e($msg['reply_text']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Inline Reply Accordion -->
                        <details style="margin-top: 8px;">
                            <summary style="cursor: pointer; font-size: .78rem; font-weight: 700; color: var(--coral-dark);">
                                <?= empty($msg['reply_text']) ? 'Write quick reply &rarr;' : 'Edit reply &rarr;' ?>
                            </summary>
                            <form method="post" style="margin-top: 8px;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="reply_message">
                                <input type="hidden" name="message_id" value="<?= (int) $msg['message_id'] ?>">
                                <textarea name="reply_text" rows="2" style="font-size: .82rem; padding: 8px;" placeholder="Type reply to <?= e($msg['name']) ?>..." required><?= e($msg['reply_text'] ?? '') ?></textarea>
                                <button type="submit" class="button button-small" style="padding: 4px 10px; font-size: .78rem; margin-top: 5px;">Send Reply</button>
                            </form>
                        </details>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: var(--ink-soft); font-size: .9rem;">No feedback messages received yet.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>

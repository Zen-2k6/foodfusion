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
    'SELECT message_id, name, email, subject, message, reply_text, created_at
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 5'
)->fetchAll();

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero admin-hero">
    <div class="container">
        <p class="eyebrow">Administration</p>
        <h1>Control Center &amp; Management</h1>
        <p>Manage featured homepage recipes, cooking events, community moderation, and registered member accounts.</p>
        <div class="hero-actions">
            <a class="button" href="<?= url('manage-recipes.php') ?>">Manage Recipes</a>
            <a class="button button-light" href="<?= url('manage-resources.php') ?>">Manage Resources</a>
            <a class="button button-outline" style="color:#fff; border-color: rgba(255,255,255,.5);" href="<?= url('contact.php#admin-messages') ?>">Feedback Inbox</a>
        </div>
    </div>
</section>

<section class="section admin-section">
    <div class="container">

        <!-- Stats Overview Cards -->
        <div class="admin-stats">
            <article>
                <span>Registered Members</span>
                <strong><?= (int) $counts['users'] ?></strong>
            </article>
            <article>
                <span>Published Recipes</span>
                <strong><?= (int) $counts['recipes'] ?></strong>
            </article>
            <article>
                <span>Culinary &amp; Edu Resources</span>
                <strong><?= (int) $counts['resources'] ?></strong>
            </article>
            <article style="<?= (int)$counts['pending_posts'] > 0 ? 'background: #fff8e1; border-color: #ffe082;' : '' ?>">
                <span>Posts Awaiting Review</span>
                <strong style="<?= (int)$counts['pending_posts'] > 0 ? 'color: #b78103;' : '' ?>"><?= (int) $counts['pending_posts'] ?></strong>
            </article>
            <article style="<?= (int)$counts['unreplied_messages'] > 0 ? 'background: #fee5df; border-color: #fccbbe;' : '' ?>">
                <span>Unreplied Feedback</span>
                <strong style="<?= (int)$counts['unreplied_messages'] > 0 ? 'color: var(--coral-dark);' : '' ?>"><?= (int) $counts['unreplied_messages'] ?></strong>
            </article>
        </div>

        <!-- Section 1: Featured Recipes Management -->
        <section class="admin-panel" id="featured-recipes" style="margin-top: 30px;">
            <div class="admin-panel-heading">
                <div>
                    <p class="eyebrow">Homepage Showcase</p>
                    <h2>Manage Featured Recipes</h2>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a class="button button-small" href="<?= url('manage-recipes.php#recipe-form') ?>">+ Add New Recipe</a>
                    <a class="arrow-link" href="<?= url('manage-recipes.php') ?>">All Recipes &rarr;</a>
                </div>
            </div>
            <p style="margin-top: -10px; margin-bottom: 18px; color: var(--ink-soft); font-size: .88rem;">
                Toggle the star button to instantly add or remove recipes from the visitor and member homepages.
            </p>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Recipe Title</th>
                            <th>Cuisine</th>
                            <th>Difficulty</th>
                            <th>Featured Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allRecipes as $rec): ?>
                        <tr>
                            <td style="width: 60px;">
                                <img src="<?= e(str_starts_with($rec['image_path'], 'http') ? $rec['image_path'] : url($rec['image_path'])) ?>" alt="" style="width: 50px; height: 38px; object-fit: cover; border-radius: 5px;">
                            </td>
                            <td>
                                <strong><a href="<?= url('recipe.php?id=' . $rec['recipe_id']) ?>" target="_blank"><?= e($rec['title']) ?></a></strong>
                            </td>
                            <td><?= e($rec['cuisine_type']) ?></td>
                            <td><?= e($rec['difficulty']) ?></td>
                            <td>
                                <form method="post" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_featured_recipe">
                                    <input type="hidden" name="record_id" value="<?= (int) $rec['recipe_id'] ?>">
                                    <button type="submit" class="button button-small <?= $rec['is_featured'] ? '' : 'button-outline' ?>" style="padding: 4px 10px; font-size: .78rem;">
                                        <?= $rec['is_featured'] ? '★ Featured on Home' : '☆ Click to Feature' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a class="button button-small button-outline" href="<?= url('manage-recipes.php?action=edit&id=' . $rec['recipe_id'] . '#recipe-form') ?>">Edit Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section 2: Upcoming Cooking Events Management -->
        <section class="admin-panel" id="events" style="margin-top: 35px;">
            <div class="admin-panel-heading">
                <div>
                    <p class="eyebrow">Workshops &amp; Gatherings</p>
                    <h2>Upcoming Events Management</h2>
                </div>
                <button class="button button-small" type="button" onclick="document.getElementById('add-event-drawer').toggleAttribute('hidden');">+ Schedule New Event</button>
            </div>

            <!-- Add Event Drawer -->
            <div id="add-event-drawer" hidden style="background: var(--cream); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid var(--line);">
                <h3 style="margin-top: 0;">Schedule a Cooking Event</h3>
                <form class="stack-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="add_event">
                    
                    <div class="form-row">
                        <label>Event Title
                            <input type="text" name="event_title" maxlength="150" placeholder="e.g. Burmese Mohinga Masterclass" required>
                        </label>
                        <label>Date &amp; Time
                            <input type="datetime-local" name="event_date" required>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Location / Venue
                            <input type="text" name="event_location" maxlength="150" placeholder="e.g. FoodFusion Kitchen Lab / Online Zoom" required>
                        </label>
                        <label>Banner Image URL
                            <input type="url" name="event_image" placeholder="https://images.unsplash.com/photo-...">
                        </label>
                    </div>

                    <label>Description &amp; Agenda
                        <textarea name="event_description" rows="3" placeholder="Overview of the workshop, required ingredients, chef bio..." required></textarea>
                    </label>

                    <button class="button" type="submit">Publish Event</button>
                </form>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Event Title</th>
                            <th>Location</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $ev): ?>
                        <tr>
                            <td style="white-space: nowrap;">
                                <strong><?= e(date('j M Y', strtotime($ev['event_date']))) ?></strong>
                                <br><small><?= e(date('g:i A', strtotime($ev['event_date']))) ?></small>
                            </td>
                            <td><strong><?= e($ev['title']) ?></strong></td>
                            <td><?= e($ev['location']) ?></td>
                            <td><small><?= e(substr($ev['description'], 0, 80)) ?>...</small></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Remove this event?');" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="record_id" value="<?= (int) $ev['event_id'] ?>">
                                    <button type="submit" class="danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section 3: Community Posts Moderation -->
        <section class="admin-panel" id="community-posts" style="margin-top: 35px;">
            <div class="admin-panel-heading">
                <div>
                    <p class="eyebrow">Community Moderation</p>
                    <h2>Community Posts Review</h2>
                </div>
                <a class="arrow-link" href="<?= url('community.php') ?>" target="_blank">Browse Community &rarr;</a>
            </div>
            <p style="margin-top: -10px; margin-bottom: 18px; color: var(--ink-soft); font-size: .88rem;">
                Review posts submitted by members before they appear on the public Community wall.
            </p>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Author</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($communityPosts as $post): ?>
                        <tr>
                            <td>
                                <strong><a href="<?= url('community-post.php?id=' . $post['post_id']) ?>" target="_blank"><?= e($post['title']) ?></a></strong>
                                <br><small style="color: var(--ink-soft);"><?= e(date('j M Y', strtotime($post['created_at']))) ?> &middot; <?= e(substr($post['content'], 0, 70)) ?>...</small>
                            </td>
                            <td><?= e($post['author_name']) ?></td>
                            <td><?= e(community_post_type_label($post['post_type'])) ?></td>
                            <td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-small button-outline" href="<?= url('community-post.php?id=' . $post['post_id']) ?>" target="_blank">View</a>
                                    <?php if ($post['status'] !== 'approved'): ?>
                                        <form method="post" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="approve_post">
                                            <input type="hidden" name="record_id" value="<?= (int) $post['post_id'] ?>">
                                            <button type="submit" style="background: #2e7d32; color: #fff; border-color: #2e7d32;">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($post['status'] !== 'rejected'): ?>
                                        <form method="post" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="reject_post">
                                            <input type="hidden" name="record_id" value="<?= (int) $post['post_id'] ?>">
                                            <button class="danger" type="submit">Reject</button>
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
        <div class="admin-two-column" style="margin-top: 35px;">
            <!-- Registered Users -->
            <section class="admin-panel" id="users">
                <div class="admin-panel-heading">
                    <div>
                        <p class="eyebrow">Community Directory</p>
                        <h2>Registered Users (<?= count($recentUsers) ?>)</h2>
                    </div>
                </div>
                <div class="admin-list user-list" style="max-height: 420px; overflow-y: auto;">
                    <?php foreach ($recentUsers as $recentUser): ?>
                        <article>
                            <div class="mini-avatar"><?= e(strtoupper(substr($recentUser['first_name'], 0, 1) . substr($recentUser['last_name'], 0, 1))) ?></div>
                            <div>
                                <strong><?= e($recentUser['first_name'] . ' ' . $recentUser['last_name']) ?></strong>
                                <span><?= e($recentUser['email']) ?> &middot; <?= e(ucfirst($recentUser['role'])) ?></span>
                                <small style="display: block; color: var(--ink-soft); font-size: .75rem;">Joined <?= e(date('j M Y', strtotime($recentUser['created_at']))) ?></small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Feedback Inbox Quick View -->
            <section class="admin-panel" id="messages">
                <div class="admin-panel-heading">
                    <div>
                        <p class="eyebrow">Inbox &amp; Replies</p>
                        <h2>Feedback Messages</h2>
                    </div>
                    <a class="arrow-link" href="<?= url('contact.php#admin-messages') ?>">Reply to all &rarr;</a>
                </div>
                <div class="admin-list" style="max-height: 420px; overflow-y: auto;">
                    <?php foreach ($recentMessages as $message): ?>
                        <article style="padding: 12px; border-radius: 8px; background: var(--cream); margin-bottom: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong><?= e($message['subject']) ?></strong>
                                <?php if (!empty($message['reply_text'])): ?>
                                    <span class="status status-approved" style="font-size: .7rem;">Replied</span>
                                <?php else: ?>
                                    <span class="status status-pending" style="font-size: .7rem;">Pending Reply</span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size: .8rem; color: var(--ink-soft); display: block; margin: 3px 0;"><?= e($message['name']) ?> (<?= e($message['email']) ?>)</span>
                            <p style="margin: 4px 0 0; font-size: .82rem; color: var(--ink);"><?= e(substr($message['message'], 0, 100)) ?>...</p>
                            <div style="margin-top: 6px;">
                                <a class="button button-small button-outline" style="font-size: .75rem; padding: 2px 8px;" href="<?= url('contact.php#admin-messages') ?>">
                                    <?= empty($message['reply_text']) ? 'Reply Now &rarr;' : 'View Conversation &rarr;' ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

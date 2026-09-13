<?php
require_once __DIR__ . '/config/app.php';
require_admin();

if (is_post_request()) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
        redirect('admin.php');
    }

    $action = $_POST['action'] ?? '';
    $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$recordId) {
        set_flash('error', 'A valid record was not selected.');
        redirect('admin.php');
    }

    if ($action === 'approve_post' || $action === 'reject_post') {
        $newStatus = $action === 'approve_post' ? 'approved' : 'rejected';
        $statement = $pdo->prepare('UPDATE community_posts SET status = ? WHERE post_id = ?');
        $statement->execute([$newStatus, $recordId]);
        set_flash('success', 'Community post status updated.');
    } elseif ($action === 'publish_recipe' || $action === 'archive_recipe') {
        $newStatus = $action === 'publish_recipe' ? 'published' : 'archived';
        $statement = $pdo->prepare('UPDATE recipes SET status = ? WHERE recipe_id = ?');
        $statement->execute([$newStatus, $recordId]);
        set_flash('success', 'Recipe status updated.');
    } else {
        set_flash('error', 'Unknown admin action.');
    }

    redirect('admin.php');
}

$counts = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users) AS users,
        (SELECT COUNT(*) FROM recipes WHERE status = 'published') AS recipes,
        (SELECT COUNT(*) FROM community_posts WHERE status = 'pending') AS pending_posts,
        (SELECT COUNT(*) FROM contact_messages) AS messages"
)->fetch();

$communityPosts = $pdo->query(
    "SELECT p.post_id, p.title, p.post_type, p.status, p.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS author_name
     FROM community_posts p
     INNER JOIN users u ON u.user_id = p.user_id
     ORDER BY FIELD(p.status, 'pending', 'approved', 'rejected'), p.created_at DESC
     LIMIT 10"
)->fetchAll();

$recipes = $pdo->query(
    "SELECT r.recipe_id, r.title, r.cuisine_type, r.status, r.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS author_name
     FROM recipes r
     LEFT JOIN users u ON u.user_id = r.user_id
     ORDER BY r.created_at DESC
     LIMIT 10"
)->fetchAll();

$messages = $pdo->query(
    'SELECT message_id, name, email, subject, message, created_at
     FROM contact_messages
     ORDER BY created_at DESC
     LIMIT 8'
)->fetchAll();

$recentUsers = $pdo->query(
    'SELECT first_name, last_name, email, role, created_at
     FROM users
     ORDER BY created_at DESC
     LIMIT 8'
)->fetchAll();

$pageTitle = 'Admin dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero admin-hero">
    <div class="container">
        <p class="eyebrow">Admin area</p>
        <h1>FoodFusion dashboard</h1>
        <p>A simple overview and moderation area for the classroom demonstration.</p>
    </div>
</section>

<section class="section admin-section">
    <div class="container">
        <div class="admin-stats">
            <article><span>Members</span><strong><?= (int) $counts['users'] ?></strong></article>
            <article><span>Published recipes</span><strong><?= (int) $counts['recipes'] ?></strong></article>
            <article><span>Posts awaiting review</span><strong><?= (int) $counts['pending_posts'] ?></strong></article>
            <article><span>Contact messages</span><strong><?= (int) $counts['messages'] ?></strong></article>
        </div>

        <section class="admin-panel">
            <div class="admin-panel-heading">
                <div><p class="eyebrow">Moderation</p><h2>Community posts</h2></div>
                <p>Approve suitable posts or reject content that should stay private.</p>
            </div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Post</th><th>Author</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($communityPosts as $post): ?>
                        <tr>
                            <td><strong><a href="<?= url('community-post.php?id=' . $post['post_id']) ?>"><?= e($post['title']) ?></a></strong><small><?= e(date('j M Y', strtotime($post['created_at']))) ?></small></td>
                            <td><?= e($post['author_name']) ?></td>
                            <td><?= e(community_post_type_label($post['post_type'])) ?></td>
                            <td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?= url('community-post.php?id=' . $post['post_id']) ?>">View</a>
                                    <?php if ($post['status'] !== 'approved'): ?>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="approve_post"><input type="hidden" name="record_id" value="<?= (int) $post['post_id'] ?>"><button type="submit">Approve</button></form>
                                    <?php endif; ?>
                                    <?php if ($post['status'] !== 'rejected'): ?>
                                        <form method="post"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="reject_post"><input type="hidden" name="record_id" value="<?= (int) $post['post_id'] ?>"><button class="danger" type="submit">Reject</button></form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-heading">
                <div><p class="eyebrow">Collection</p><h2>Recipe status</h2></div>
                <a class="arrow-link" href="<?= url('recipes.php') ?>">View public recipes &rarr;</a>
            </div>
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Recipe</th><th>Author</th><th>Cuisine</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($recipes as $recipe): ?>
                        <tr>
                            <td><strong><?= e($recipe['title']) ?></strong><small><?= e(date('j M Y', strtotime($recipe['created_at']))) ?></small></td>
                            <td><?= e($recipe['author_name'] ?? 'FoodFusion') ?></td>
                            <td><?= e($recipe['cuisine_type']) ?></td>
                            <td><span class="status status-<?= e($recipe['status']) ?>"><?= e(ucfirst($recipe['status'])) ?></span></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="record_id" value="<?= (int) $recipe['recipe_id'] ?>">
                                    <?php if ($recipe['status'] === 'published'): ?>
                                        <input type="hidden" name="action" value="archive_recipe"><button class="danger" type="submit">Archive</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="publish_recipe"><button type="submit">Publish</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="admin-two-column">
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="eyebrow">Inbox</p><h2>Recent messages</h2></div></div>
                <div class="admin-list">
                    <?php foreach ($messages as $message): ?>
                        <article>
                            <div><strong><?= e($message['subject']) ?></strong><span><?= e($message['name']) ?> &middot; <?= e($message['email']) ?></span></div>
                            <p><?= e($message['message']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="eyebrow">Accounts</p><h2>Recent users</h2></div></div>
                <div class="admin-list user-list">
                    <?php foreach ($recentUsers as $recentUser): ?>
                        <article>
                            <div class="mini-avatar"><?= e(strtoupper(substr($recentUser['first_name'], 0, 1) . substr($recentUser['last_name'], 0, 1))) ?></div>
                            <div><strong><?= e($recentUser['first_name'] . ' ' . $recentUser['last_name']) ?></strong><span><?= e($recentUser['email']) ?> &middot; <?= e(ucfirst($recentUser['role'])) ?></span></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

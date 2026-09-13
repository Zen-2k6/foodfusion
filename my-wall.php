<?php
require_once __DIR__ . '/config/app.php';
require_login();

$statement = $pdo->prepare(
    "SELECT p.*,
            (SELECT COUNT(*) FROM community_post_likes l WHERE l.post_id = p.post_id) AS like_count,
            (SELECT COUNT(*) FROM community_post_comments c WHERE c.post_id = p.post_id) AS comment_count
     FROM community_posts p
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC"
);
$statement->execute([current_user()['user_id']]);
$posts = $statement->fetchAll();

$pageTitle = 'My Wall';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero wall-hero">
    <div class="container">
        <p class="eyebrow">Your community space</p>
        <h1>My Wall</h1>
        <p>Create posts and follow their moderation status. Edited posts return to Pending for another review.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <div><p class="eyebrow">Your contributions</p><h2><?= count($posts) ?> post<?= count($posts) === 1 ? '' : 's' ?></h2></div>
            <a class="button" href="<?= url('community-post-form.php') ?>">Create New Post</a>
        </div>

        <?php if ($posts): ?>
            <div class="wall-grid">
                <?php foreach ($posts as $post): ?>
                    <article class="wall-card">
                        <?php if ($post['image_path']): ?>
                            <img src="<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="post-placeholder wall-placeholder" aria-hidden="true"><span><?= e(strtoupper(substr($post['post_type'], 0, 1))) ?></span></div>
                        <?php endif; ?>
                        <div class="wall-card-body">
                            <div class="wall-card-heading">
                                <div class="tag-row"><span><?= e(community_post_type_label($post['post_type'])) ?></span></div>
                                <span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span>
                            </div>
                            <h2><?= e($post['title']) ?></h2>
                            <p><?= e($post['content']) ?></p>
                            <div class="wall-meta">
                                <time datetime="<?= e(date('Y-m-d', strtotime($post['created_at']))) ?>"><?= e(date('j M Y', strtotime($post['created_at']))) ?></time>
                                <span><?= (int) $post['like_count'] ?> likes &middot; <?= (int) $post['comment_count'] ?> comments</span>
                            </div>
                            <div class="wall-actions">
                                <a class="button button-small button-outline" href="<?= url('community-post.php?id=' . $post['post_id']) ?>">View</a>
                                <a class="button button-small" href="<?= url('community-post-form.php?id=' . $post['post_id']) ?>">Edit</a>
                                <form method="post" action="<?= url('community-post-delete.php') ?>" data-confirm-delete>
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                                    <button class="button button-small button-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>Your wall is ready</h2>
                <p>Create your first favourite recipe, cooking tip or culinary experience.</p>
                <a class="button" href="<?= url('community-post-form.php') ?>">Create New Post</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

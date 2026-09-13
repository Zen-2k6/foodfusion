<?php
require_once __DIR__ . '/config/app.php';

$viewerId = current_user()['user_id'] ?? 0;
$statement = $pdo->prepare(
    "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name,
            (SELECT COUNT(*) FROM community_post_likes l WHERE l.post_id = p.post_id) AS like_count,
            (SELECT COUNT(*) FROM community_post_comments c WHERE c.post_id = p.post_id) AS comment_count,
            EXISTS(
                SELECT 1 FROM community_post_likes l
                WHERE l.post_id = p.post_id AND l.user_id = ?
            ) AS liked_by_viewer
     FROM community_posts p
     INNER JOIN users u ON u.user_id = p.user_id
     WHERE p.status = 'approved'
     ORDER BY p.created_at DESC"
);
$statement->execute([$viewerId]);
$posts = $statement->fetchAll();

$pageTitle = 'Community cookbook';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero community-hero">
    <div class="container">
        <p class="eyebrow light">The community cookbook</p>
        <h1>Every dish carries a story.</h1>
        <p>Discover favourite recipes, practical cooking tips and real kitchen experiences shared by FoodFusion members.</p>
        <?php if (is_logged_in()): ?>
            <a class="button" href="<?= url('my-wall.php') ?>">Visit My Wall</a>
        <?php else: ?>
            <a class="button" href="<?= url('register.php') ?>" data-open-join>Join the community</a>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Approved by FoodFusion</p>
                <h2>Community posts</h2>
            </div>
            <?php if (is_logged_in()): ?>
                <a class="button button-outline" href="<?= url('community-post-form.php') ?>">Create New Post</a>
            <?php endif; ?>
        </div>

        <?php if ($posts): ?>
            <div class="community-grid">
                <?php foreach ($posts as $post): ?>
                    <article class="community-card">
                        <?php if ($post['image_path']): ?>
                            <img src="<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="post-placeholder" aria-hidden="true"><span><?= e(strtoupper(substr($post['post_type'], 0, 1))) ?></span></div>
                        <?php endif; ?>

                        <div class="card-body">
                            <div class="tag-row"><span><?= e(community_post_type_label($post['post_type'])) ?></span></div>
                            <h3><?= e($post['title']) ?></h3>
                            <p class="community-description"><?= e($post['content']) ?></p>
                            <div class="community-byline">
                                <span>By <?= e($post['author_name']) ?></span>
                                <time datetime="<?= e(date('Y-m-d', strtotime($post['created_at']))) ?>"><?= e(date('j M Y', strtotime($post['created_at']))) ?></time>
                            </div>

                            <div class="community-card-stats" aria-label="Post activity">
                                <span>&hearts; <?= (int) $post['like_count'] ?> like<?= (int) $post['like_count'] === 1 ? '' : 's' ?></span>
                                <span><?= (int) $post['comment_count'] ?> comment<?= (int) $post['comment_count'] === 1 ? '' : 's' ?></span>
                            </div>

                            <div class="community-card-actions">
                                <?php if (is_logged_in()): ?>
                                    <form method="post" action="<?= url('community-like.php') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                                        <input type="hidden" name="return_page" value="community">
                                        <button class="button button-small button-outline <?= $post['liked_by_viewer'] ? 'is-active' : '' ?>" type="submit">
                                            <?= $post['liked_by_viewer'] ? 'Liked' : 'Like' ?> (<?= (int) $post['like_count'] ?>)
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a class="button button-small button-outline" href="<?= url('login.php') ?>">Log in to like</a>
                                <?php endif; ?>
                                <a class="button button-small" href="<?= url('community-post.php?id=' . $post['post_id']) ?>">View Post</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No approved posts yet</h2>
                <p>Approved member posts will appear here after admin review.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!is_logged_in()): ?>
<section class="join-band">
    <div class="container join-band-content">
        <div>
            <p class="eyebrow light">Share your cooking story</p>
            <h2>Register to create posts, like stories and join the conversation.</h2>
        </div>
        <a class="button button-light" href="<?= url('register.php') ?>" data-open-join>Join FoodFusion</a>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

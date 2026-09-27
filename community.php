<?php
require_once __DIR__ . '/config/app.php';

$viewer = current_user();
$viewerId = (int) ($viewer['user_id'] ?? 0);
$isAdmin = is_admin() ? 1 : 0;

// Handle inline comment submission
if (is_post_request() && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    require_login();
    $postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
    $commentText = clean_text_input($_POST['comment_text'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
    } elseif (!$postId) {
        set_flash('error', 'A valid post was not selected.');
    } elseif ($commentText === '' || strlen($commentText) > 1000) {
        set_flash('error', 'Please enter a comment of up to 1,000 characters.');
    } else {
        // Verify post exists and is approved or owned
        $checkStmt = $pdo->prepare('SELECT post_id, status, user_id FROM community_posts WHERE post_id = ?');
        $checkStmt->execute([$postId]);
        $targetPost = $checkStmt->fetch();

        if (!$targetPost || ($targetPost['status'] !== 'approved' && (int)$targetPost['user_id'] !== $viewerId && !$isAdmin)) {
            set_flash('error', 'Comments cannot be added to this post.');
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO community_post_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)'
            );
            $insert->execute([$postId, $viewerId, $commentText]);
            set_flash('success', 'Your comment was posted.');
        }
    }
    redirect('community.php#post-' . $postId);
}

// Fetch all approved community posts + own posts
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
     WHERE p.status = 'approved' OR p.user_id = ? OR ? = 1
     ORDER BY p.created_at DESC"
);
$statement->execute([$viewerId, $viewerId, $isAdmin]);
$posts = $statement->fetchAll();

// Fetch comments for all displayed posts
$commentsByPost = [];
if ($posts) {
    $postIds = array_column($posts, 'post_id');
    $inClause = implode(',', array_map('intval', $postIds));
    $commentsQuery = $pdo->query(
        "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name
         FROM community_post_comments c
         INNER JOIN users u ON u.user_id = c.user_id
         WHERE c.post_id IN ($inClause)
         ORDER BY c.created_at ASC"
    )->fetchAll();

    foreach ($commentsQuery as $comm) {
        $commentsByPost[$comm['post_id']][] = $comm;
    }
}

$pageTitle = 'Community Posts';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero community-hero">
    <div class="container">
        <p class="eyebrow light">Community Cookbook</p>
        <h1>Every dish carries a story.</h1>
        <p>Discover favorite recipes, cooking tips, and culinary experiences shared by home cooks across the community.</p>
        <?php if (is_logged_in()): ?>
            <div class="hero-actions">
                <a class="button" href="<?= url('community-post-form.php') ?>">Create New Post</a>
                <a class="button button-light" href="<?= url('my-wall.php') ?>">Visit My Wall</a>
            </div>
        <?php else: ?>
            <a class="button" href="<?= url('register.php') ?>" data-open-join>Join the community to post</a>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Community Kitchen</p>
                <h2>Community posts</h2>
            </div>
            <?php if (is_logged_in()): ?>
                <a class="button button-outline" href="<?= url('community-post-form.php') ?>">+ Create New Post</a>
            <?php endif; ?>
        </div>

        <?php if ($posts): ?>
            <div class="community-grid">
                <?php foreach ($posts as $post): ?>
                    <?php
                    $isOwnPost = is_logged_in() && (int)$post['user_id'] === $viewerId;
                    $postComments = $commentsByPost[$post['post_id']] ?? [];
                    ?>
                    <article class="community-card" id="post-<?= (int)$post['post_id'] ?>">
                        <?php if ($post['image_path']): ?>
                            <img src="<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="post-placeholder" aria-hidden="true"><span><?= e(strtoupper(substr($post['post_type'], 0, 1))) ?></span></div>
                        <?php endif; ?>

                        <div class="card-body">
                            <div class="tag-row" style="display: flex; justify-content: space-between; align-items: center;">
                                <span><?= e(community_post_type_label($post['post_type'])) ?></span>
                                <?php if ($post['status'] !== 'approved'): ?>
                                    <span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3><?= e($post['title']) ?></h3>
                            <p class="community-description"><?= nl2br(e($post['content'])) ?></p>

                            <div class="community-byline">
                                <strong>Posted by <?= e($post['author_name']) ?><?= $isOwnPost ? ' (You)' : '' ?></strong>
                                <time datetime="<?= e(date('Y-m-d', strtotime($post['created_at']))) ?>"><?= e(date('j M Y', strtotime($post['created_at']))) ?></time>
                            </div>

                            <!-- Actions Row: Direct Like & Comment Toggle -->
                            <div class="community-card-actions" style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php if (is_logged_in()): ?>
                                        <form method="post" action="<?= url('community-like.php') ?>" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                                            <input type="hidden" name="return_page" value="community">
                                            <button class="button button-small button-outline <?= $post['liked_by_viewer'] ? 'is-active' : '' ?>" type="submit" title="Like this post">
                                                &hearts; <?= $post['liked_by_viewer'] ? 'Liked' : 'Like' ?> (<?= (int) $post['like_count'] ?>)
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a class="button button-small button-outline" href="<?= url('login.php') ?>" title="Log in to like">
                                            &hearts; Like (<?= (int) $post['like_count'] ?>)
                                        </a>
                                    <?php endif; ?>

                                    <button class="button button-small button-outline comment-toggle-btn" type="button" data-toggle-comments="comments-<?= (int)$post['post_id'] ?>">
                                        &#128172; Comments (<?= count($postComments) ?>)
                                    </button>
                                </div>

                                <a class="arrow-link" href="<?= url('community-post.php?id=' . $post['post_id']) ?>" style="font-size: .8rem;">Full view &rarr;</a>
                            </div>

                            <!-- Inline Comments Section right under the card -->
                            <div class="inline-comment-section" id="comments-<?= (int)$post['post_id'] ?>" style="margin-top: 12px; padding: 12px; background: var(--cream); border-radius: 10px;">
                                <?php if ($postComments): ?>
                                    <div class="inline-comment-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 180px; overflow-y: auto; margin-bottom: 12px;">
                                        <?php foreach ($postComments as $comment): ?>
                                            <div class="inline-comment-item" style="padding: 6px 10px; background: var(--white); border-radius: 8px; font-size: .82rem;">
                                                <div style="display: flex; justify-content: space-between; gap: 8px; margin-bottom: 2px;">
                                                    <strong style="color: var(--ink);"><?= e($comment['author_name']) ?></strong>
                                                    <time style="color: var(--ink-soft); font-size: .75rem;"><?= e(date('j M, g:i A', strtotime($comment['created_at']))) ?></time>
                                                </div>
                                                <p style="margin: 0; color: var(--ink);"><?= e($comment['comment_text']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p style="font-size: .8rem; color: var(--ink-soft); margin: 0 0 10px 0;">No comments yet. Be the first to chime in!</p>
                                <?php endif; ?>

                                <?php if (is_logged_in()): ?>
                                    <form method="post" action="<?= url('community.php') ?>#post-<?= (int)$post['post_id'] ?>" class="inline-comment-form" style="display: flex; gap: 6px;">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="add_comment">
                                        <input type="hidden" name="post_id" value="<?= (int)$post['post_id'] ?>">
                                        <input type="text" name="comment_text" placeholder="Write a comment..." maxlength="1000" required style="flex: 1; padding: 6px 10px; font-size: .82rem; border-radius: 6px; border: 1px solid var(--line);">
                                        <button type="submit" class="button button-small" style="padding: 6px 12px; font-size: .82rem;">Post</button>
                                    </form>
                                <?php else: ?>
                                    <p style="font-size: .8rem; margin: 0; color: var(--ink-soft);">
                                        <a href="<?= url('login.php') ?>">Log in</a> or <a href="<?= url('register.php') ?>" data-open-join>Join</a> to leave a comment.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No posts yet</h2>
                <p>Be the first member to share a recipe, tip, or kitchen experience!</p>
                <?php if (is_logged_in()): ?>
                    <a class="button" href="<?= url('community-post-form.php') ?>">Create Post</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!is_logged_in()): ?>
<section class="join-band">
    <div class="container join-band-content">
        <div>
            <p class="eyebrow light">Share your cooking story</p>
            <h2>Register to create posts, like stories, and join the conversation.</h2>
        </div>
        <a class="button button-light" href="<?= url('register.php') ?>" data-open-join>Join FoodFusion</a>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

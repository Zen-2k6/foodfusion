<?php
require_once __DIR__ . '/config/app.php';

$postId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$postId) {
    http_response_code(404);
    exit('Community post not found.');
}

$viewer = current_user();
$viewerId = $viewer['user_id'] ?? 0;
$adminAccess = is_admin() ? 1 : 0;

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
     WHERE p.post_id = ?
       AND (p.status = 'approved' OR p.user_id = ? OR ? = 1)"
);
$statement->execute([$viewerId, $postId, $viewerId, $adminAccess]);
$post = $statement->fetch();

if (!$post) {
    http_response_code(404);
    exit('Community post not found or not available.');
}

if (is_post_request() && isset($_POST['add_comment'])) {
    require_login();
    $commentText = trim(strip_tags($_POST['comment_text'] ?? ''));

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
    } elseif ($post['status'] !== 'approved') {
        set_flash('error', 'Comments can only be added after the post is approved.');
    } elseif ($commentText === '' || strlen($commentText) > 1000) {
        set_flash('error', 'Please enter a comment of no more than 1,000 characters.');
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO community_post_comments (post_id, user_id, comment_text) VALUES (?, ?, ?)'
        );
        $insert->execute([$postId, current_user()['user_id'], $commentText]);
        set_flash('success', 'Your comment was added.');
    }
    redirect('community-post.php?id=' . $postId . '#post-comments');
}

$comments = $pdo->prepare(
    "SELECT c.comment_text, c.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS author_name
     FROM community_post_comments c
     INNER JOIN users u ON u.user_id = c.user_id
     WHERE c.post_id = ?
     ORDER BY c.created_at DESC"
);
$comments->execute([$postId]);
$postComments = $comments->fetchAll();

$isOwner = is_logged_in() && (int) $post['user_id'] === (int) current_user()['user_id'];
$pageTitle = $post['title'];
require __DIR__ . '/includes/header.php';
?>

<article class="community-post-detail">
    <div class="container community-post-layout">
        <div class="community-post-image">
            <?php if ($post['image_path']): ?>
                <img src="<?= e($post['image_path']) ?>" alt="<?= e($post['title']) ?>">
            <?php else: ?>
                <div class="post-placeholder" aria-hidden="true"><span><?= e(strtoupper(substr($post['post_type'], 0, 1))) ?></span></div>
            <?php endif; ?>
        </div>

        <div class="community-post-copy">
            <a class="back-link" href="<?= url($isOwner ? 'my-wall.php' : 'community.php') ?>">&larr; <?= $isOwner ? 'Back to My Wall' : 'Back to Community' ?></a>
            <div class="post-heading-row">
                <div class="tag-row"><span><?= e(community_post_type_label($post['post_type'])) ?></span></div>
                <?php if ($post['status'] !== 'approved'): ?>
                    <span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span>
                <?php endif; ?>
            </div>
            <h1><?= e($post['title']) ?></h1>
            <p class="community-post-author">By <?= e($post['author_name']) ?> &middot; <?= e(date('j F Y', strtotime($post['created_at']))) ?></p>
            <div class="community-post-description"><?= nl2br(e($post['content'])) ?></div>

            <div class="community-detail-actions">
                <?php if ($post['status'] === 'approved' && is_logged_in()): ?>
                    <form method="post" action="<?= url('community-like.php') ?>">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="post_id" value="<?= (int) $post['post_id'] ?>">
                        <input type="hidden" name="return_page" value="post">
                        <button class="button button-outline <?= $post['liked_by_viewer'] ? 'is-active' : '' ?>" type="submit">
                            &hearts; <?= $post['liked_by_viewer'] ? 'Liked' : 'Like' ?> (<?= (int) $post['like_count'] ?>)
                        </button>
                    </form>
                <?php elseif ($post['status'] === 'approved'): ?>
                    <a class="button button-outline" href="<?= url('login.php') ?>">Log in to like</a>
                <?php endif; ?>

                <?php if ($isOwner): ?>
                    <a class="button" href="<?= url('community-post-form.php?id=' . $post['post_id']) ?>">Edit Post</a>
                <?php endif; ?>
            </div>

            <?php if ($post['status'] !== 'approved'): ?>
                <p class="moderation-note">This post is visible only to you and administrators until it is approved.</p>
            <?php endif; ?>
        </div>
    </div>
</article>

<?php if ($post['status'] === 'approved'): ?>
<section class="section section-tint" id="post-comments">
    <div class="container narrow">
        <div class="section-heading">
            <div><p class="eyebrow">Join the conversation</p><h2>Comments (<?= count($postComments) ?>)</h2></div>
        </div>

        <?php if (is_logged_in()): ?>
            <form class="comment-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_comment" value="1">
                <label for="comment_text">Add a helpful comment</label>
                <textarea id="comment_text" name="comment_text" rows="4" maxlength="1000" required></textarea>
                <button class="button" type="submit">Post Comment</button>
            </form>
        <?php else: ?>
            <p class="callout"><a href="<?= url('login.php') ?>">Log in</a> or <a href="<?= url('register.php') ?>" data-open-join>register</a> to comment on this post.</p>
        <?php endif; ?>

        <div class="comment-list">
            <?php foreach ($postComments as $comment): ?>
                <article class="comment-card">
                    <div class="comment-avatar" aria-hidden="true"><?= e(strtoupper(substr($comment['author_name'], 0, 1))) ?></div>
                    <div>
                        <strong><?= e($comment['author_name']) ?></strong>
                        <time datetime="<?= e(date('Y-m-d', strtotime($comment['created_at']))) ?>"><?= e(date('j M Y', strtotime($comment['created_at']))) ?></time>
                        <p><?= nl2br(e($comment['comment_text'])) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$postComments): ?><p>No comments yet. Start the conversation.</p><?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

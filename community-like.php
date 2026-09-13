<?php
require_once __DIR__ . '/config/app.php';
require_login();

if (!is_post_request() || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid request. Please refresh the page and try again.');
    redirect('community.php');
}

$postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
$returnPage = $_POST['return_page'] ?? 'community';
$redirectPath = $returnPage === 'post' && $postId
    ? 'community-post.php?id=' . $postId
    : 'community.php';

if (!$postId) {
    set_flash('error', 'A valid community post was not selected.');
    redirect('community.php');
}

$postCheck = $pdo->prepare("SELECT post_id FROM community_posts WHERE post_id = ? AND status = 'approved'");
$postCheck->execute([$postId]);
if (!$postCheck->fetch()) {
    set_flash('error', 'Only approved community posts can be liked.');
    redirect($redirectPath);
}

$userId = current_user()['user_id'];
$existing = $pdo->prepare('SELECT like_id FROM community_post_likes WHERE post_id = ? AND user_id = ?');
$existing->execute([$postId, $userId]);

if ($existing->fetch()) {
    $pdo->prepare('DELETE FROM community_post_likes WHERE post_id = ? AND user_id = ?')
        ->execute([$postId, $userId]);
    set_flash('success', 'Like removed.');
} else {
    $pdo->prepare('INSERT INTO community_post_likes (post_id, user_id) VALUES (?, ?)')
        ->execute([$postId, $userId]);
    set_flash('success', 'Post liked.');
}

redirect($redirectPath);

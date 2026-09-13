<?php
require_once __DIR__ . '/config/app.php';
require_login();

if (!is_post_request() || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid delete request. Please try again.');
    redirect('my-wall.php');
}

$postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
if (!$postId) {
    set_flash('error', 'A valid post was not selected.');
    redirect('my-wall.php');
}

// Including user_id in the query prevents members deleting another user's post.
$statement = $pdo->prepare('DELETE FROM community_posts WHERE post_id = ? AND user_id = ?');
$statement->execute([$postId, current_user()['user_id']]);

if ($statement->rowCount() === 1) {
    set_flash('success', 'Your post was deleted.');
} else {
    set_flash('error', 'You can only delete posts that you created.');
}

redirect('my-wall.php');

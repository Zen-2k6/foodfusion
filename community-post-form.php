<?php
require_once __DIR__ . '/config/app.php';
require_login();

$errors = [];
$postId = filter_var($_POST['post_id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
$editingPost = null;

if ($postId) {
    $statement = $pdo->prepare('SELECT * FROM community_posts WHERE post_id = ? AND user_id = ?');
    $statement->execute([$postId, current_user()['user_id']]);
    $editingPost = $statement->fetch();

    if (!$editingPost) {
        set_flash('error', 'You can only edit posts that you created.');
        redirect('my-wall.php');
    }
}

$postType = $_POST['post_type'] ?? ($editingPost['post_type'] ?? 'recipe');
$title = $_POST['title'] ?? ($editingPost['title'] ?? '');
$description = $_POST['description'] ?? ($editingPost['content'] ?? '');
$imageUrl = $_POST['image_url'] ?? ($editingPost['image_path'] ?? '');

if (is_post_request()) {
    $postType = $_POST['post_type'] ?? '';
    $title = clean_text_input($_POST['title'] ?? '');
    $description = trim(strip_tags($_POST['description'] ?? ''));
    $imageUrl = trim($_POST['image_url'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'The form expired. Please try again.';
    }
    if (!in_array($postType, ['recipe', 'tip', 'experience'], true)) {
        $errors[] = 'Please choose a valid post type.';
    }
    if ($title === '' || strlen($title) > 150) {
        $errors[] = 'Enter a title of no more than 150 characters.';
    }
    if ($description === '' || strlen($description) > 3000) {
        $errors[] = 'Enter a description of no more than 3,000 characters.';
    }
    if ($imageUrl === '' || strlen($imageUrl) > 500 || !is_valid_image_url($imageUrl)) {
        $errors[] = 'Enter a valid HTTPS image URL.';
    }

    if (!$errors) {
        if ($editingPost) {
            $statement = $pdo->prepare(
                "UPDATE community_posts
                 SET post_type = ?, title = ?, content = ?, image_path = ?, status = 'pending'
                 WHERE post_id = ? AND user_id = ?"
            );
            $statement->execute([
                $postType, $title, $description, $imageUrl,
                $postId, current_user()['user_id']
            ]);
            set_flash('success', 'Post updated and returned to Pending for admin review.');
        } else {
            $statement = $pdo->prepare(
                "INSERT INTO community_posts
                 (user_id, post_type, title, content, image_path, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            $statement->execute([
                current_user()['user_id'], $postType, $title, $description, $imageUrl
            ]);
            set_flash('success', 'Post created. Its status is Pending until an admin reviews it.');
        }
        redirect('my-wall.php');
    }
}

$pageTitle = $editingPost ? 'Edit community post' : 'Create community post';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero wall-hero">
    <div class="container narrow">
        <p class="eyebrow">My Wall</p>
        <h1><?= $editingPost ? 'Edit your post' : 'Create a new post' ?></h1>
        <p>All new or edited posts are marked Pending so an administrator can review them.</p>
    </div>
</section>

<section class="section">
    <div class="container form-card narrow">
        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <strong>Please correct the following:</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form class="stack-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <?php if ($editingPost): ?><input type="hidden" name="post_id" value="<?= (int) $postId ?>"><?php endif; ?>

            <label for="post_type">Post Type
                <select id="post_type" name="post_type" required>
                    <?php foreach (['recipe' => 'Favourite Recipe', 'tip' => 'Cooking Tip', 'experience' => 'Culinary Experience'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $postType === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label for="title">Title
                <input id="title" type="text" name="title" maxlength="150" value="<?= e($title) ?>" required>
            </label>

            <label for="description">Description
                <textarea id="description" name="description" rows="8" maxlength="3000" required><?= e($description) ?></textarea>
            </label>

            <label for="image_url">Image
                <input id="image_url" type="url" name="image_url" maxlength="500" value="<?= e($imageUrl) ?>" placeholder="https://images.unsplash.com/..." required>
                <small>Paste a direct HTTPS image URL. The website does not generate or upload images.</small>
            </label>

            <div class="form-actions">
                <button class="button" type="submit"><?= $editingPost ? 'Save Changes' : 'Create Post' ?></button>
                <a class="button button-outline" href="<?= url('my-wall.php') ?>">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

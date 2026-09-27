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
    $finalImagePath = $editingPost['image_path'] ?? '';

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

    // Handle file upload if provided
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_file'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions, true)) {
            $errors[] = 'Allowed image formats are JPG, PNG, WEBP, and GIF.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image file size must not exceed 5 MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/posts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = 'post_' . uniqid('', true) . '.' . $ext;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $finalImagePath = 'uploads/posts/' . $newFileName;
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    } elseif ($imageUrl !== '') {
        if (!is_valid_image_url($imageUrl) || strlen($imageUrl) > 500) {
            $errors[] = 'Please enter a valid HTTPS image URL.';
        } else {
            $finalImagePath = $imageUrl;
        }
    }

    if ($finalImagePath === '') {
        $errors[] = 'Please upload an image file or enter an image URL.';
    }

    if (!$errors) {
        if ($editingPost) {
            $statement = $pdo->prepare(
                "UPDATE community_posts
                 SET post_type = ?, title = ?, content = ?, image_path = ?, status = 'pending'
                 WHERE post_id = ? AND user_id = ?"
            );
            $statement->execute([
                $postType, $title, $description, $finalImagePath,
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
                current_user()['user_id'], $postType, $title, $description, $finalImagePath
            ]);
            set_flash('success', 'Post created! Its status is Pending until an admin reviews it.');
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
        <p>Share your recipe, kitchen tip, or culinary adventure. Posts are reviewed by an administrator before appearing on the public community wall.</p>
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

        <form class="stack-form" method="post" enctype="multipart/form-data">
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
                <input id="title" type="text" name="title" maxlength="150" value="<?= e($title) ?>" placeholder="e.g. Grandma's Mohinga Secrets" required>
            </label>

            <label for="description">Description / Content
                <textarea id="description" name="description" rows="8" maxlength="3000" placeholder="Describe the recipe, steps, or tips in detail..." required><?= e($description) ?></textarea>
            </label>

            <fieldset style="border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin: 10px 0;">
                <legend style="font-weight: 700; padding: 0 8px; color: var(--ink);">Post Image</legend>
                
                <?php if ($editingPost && $editingPost['image_path']): ?>
                    <div style="margin-bottom: 12px;">
                        <small style="display: block; margin-bottom: 4px; color: var(--ink-soft);">Current Image:</small>
                        <img src="<?= e(str_starts_with($editingPost['image_path'], 'http') ? $editingPost['image_path'] : url($editingPost['image_path'])) ?>" alt="Preview" style="max-height: 120px; border-radius: 8px; object-fit: cover;">
                    </div>
                <?php endif; ?>

                <label for="image_file">Option 1: Upload Image File (from device)
                    <input id="image_file" type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small>Choose a file from your computer (JPG, PNG, WEBP, max 5MB).</small>
                </label>

                <div style="text-align: center; margin: 10px 0; font-weight: 700; color: var(--ink-soft);">— OR —</div>

                <label for="image_url">Option 2: Image URL (online link)
                    <input id="image_url" type="url" name="image_url" maxlength="500" value="<?= e($imageUrl) ?>" placeholder="https://images.unsplash.com/...">
                    <small>Paste a direct HTTPS image URL from Unsplash or image host.</small>
                </label>
            </fieldset>

            <div class="form-actions">
                <button class="button" type="submit"><?= $editingPost ? 'Save Changes' : 'Create Post' ?></button>
                <a class="button button-outline" href="<?= url('my-wall.php') ?>">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

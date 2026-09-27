<?php
require_once __DIR__ . '/config/app.php';
require_admin();

$errors = [];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$resourceId = filter_var($_GET['id'] ?? ($_POST['resource_id'] ?? null), FILTER_VALIDATE_INT);
$editingResource = null;

if ($resourceId) {
    $stmt = $pdo->prepare('SELECT * FROM resources WHERE resource_id = ?');
    $stmt->execute([$resourceId]);
    $editingResource = $stmt->fetch();
}

// Handle POST actions (Create, Edit, Delete)
if (is_post_request()) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
        redirect('manage-resources.php');
    }

    $postAction = $_POST['action'] ?? '';

    // DELETE RESOURCE
    if ($postAction === 'delete') {
        $delId = filter_var($_POST['resource_id'] ?? null, FILTER_VALIDATE_INT);
        if ($delId) {
            $pdo->prepare('DELETE FROM resources WHERE resource_id = ?')->execute([$delId]);
            set_flash('success', 'Resource deleted successfully.');
        }
        redirect('manage-resources.php');
    }

    // CREATE OR UPDATE RESOURCE
    if ($postAction === 'save_resource') {
        $title = clean_text_input($_POST['title'] ?? '');
        $category = in_array($_POST['resource_category'] ?? '', ['culinary', 'educational'], true) ? $_POST['resource_category'] : 'culinary';
        $type = in_array($_POST['resource_type'] ?? '', ['recipe_card', 'tutorial', 'video', 'infographic', 'guide', 'article'], true) ? $_POST['resource_type'] : 'tutorial';
        $description = trim(strip_tags($_POST['description'] ?? ''));
        $fileUrl = trim($_POST['file_url'] ?? '');
        $thumbnailUrl = trim($_POST['thumbnail_url'] ?? '');

        $finalFilePath = $editingResource['file_path'] ?? '';
        $finalThumbnailPath = $editingResource['thumbnail_path'] ?? '';

        if ($title === '') $errors[] = 'Please enter a resource title.';
        if ($description === '') $errors[] = 'Please provide a description.';

        // Handle Resource File Upload (PDF, HTML, etc.)
        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['resource_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'html'];

            if (!in_array($ext, $allowedExtensions, true)) {
                $errors[] = 'Resource file must be a PDF, DOC, DOCX, TXT, or HTML document.';
            } elseif ($file['size'] > 15 * 1024 * 1024) {
                $errors[] = 'Resource file must not exceed 15MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/resources/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newFilename = 'res_' . uniqid('', true) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename)) {
                    $finalFilePath = 'uploads/resources/' . $newFilename;
                } else {
                    $errors[] = 'Failed to upload resource file.';
                }
            }
        } elseif ($fileUrl !== '') {
            $finalFilePath = $fileUrl;
        }

        if ($finalFilePath === '') {
            $errors[] = 'Please upload a resource file (PDF) or provide a web/YouTube URL.';
        }

        // Handle Thumbnail Upload or URL
        if (isset($_FILES['thumbnail_file']) && $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_OK) {
            $thumbFile = $_FILES['thumbnail_file'];
            $thumbExt = strtolower(pathinfo($thumbFile['name'], PATHINFO_EXTENSION));
            $allowedThumbExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($thumbExt, $allowedThumbExts, true)) {
                $errors[] = 'Thumbnail must be a valid image (JPG, PNG, WEBP, GIF).';
            } elseif ($thumbFile['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Thumbnail size must not exceed 5MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/resources/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newThumbName = 'thumb_' . uniqid('', true) . '.' . $thumbExt;
                if (move_uploaded_file($thumbFile['tmp_name'], $uploadDir . $newThumbName)) {
                    $finalThumbnailPath = 'uploads/resources/' . $newThumbName;
                }
            }
        } elseif ($thumbnailUrl !== '') {
            $finalThumbnailPath = $thumbnailUrl;
        }

        if (!$errors) {
            if ($editingResource) {
                $stmt = $pdo->prepare(
                    "UPDATE resources
                     SET title = ?, description = ?, resource_category = ?, resource_type = ?,
                         file_path = ?, thumbnail_path = ?
                     WHERE resource_id = ?"
                );
                $stmt->execute([
                    $title, $description, $category, $type,
                    $finalFilePath, $finalThumbnailPath, $resourceId
                ]);
                set_flash('success', 'Resource updated successfully.');
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO resources
                     (user_id, title, description, resource_category, resource_type, file_path, thumbnail_path)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    current_user()['user_id'], $title, $description, $category, $type,
                    $finalFilePath, $finalThumbnailPath
                ]);
                set_flash('success', 'New resource published successfully.');
            }
            redirect('manage-resources.php');
        }
    }
}

// Fetch resources
$catFilter = in_array($_GET['category'] ?? '', ['culinary', 'educational'], true) ? $_GET['category'] : '';
$sql = "SELECT * FROM resources WHERE 1=1";
$params = [];
if ($catFilter !== '') {
    $sql .= " AND resource_category = ?";
    $params[] = $catFilter;
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allResources = $stmt->fetchAll();

$culinaryCount = $pdo->query("SELECT COUNT(*) FROM resources WHERE resource_category = 'culinary'")->fetchColumn();
$educationalCount = $pdo->query("SELECT COUNT(*) FROM resources WHERE resource_category = 'educational'")->fetchColumn();

$pageTitle = 'Manage Resources';
require __DIR__ . '/includes/admin-header.php';
?>

<!-- Admin Page Header -->
<div class="admin-header-title">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1>Manage Resources</h1>
            <p>Upload and organize cooking tutorials, printable culinary PDF cards, kitchen guides, and instructional videos.</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a class="button button-small" href="#resource-form"><?= $editingResource ? 'Edit Resource Below' : '+ Add New Resource' ?></a>
            <a class="button button-small button-outline" href="<?= url('culinary-resources.php') ?>" target="_blank">View Culinary &rarr;</a>
            <a class="button button-small button-outline" href="<?= url('educational-resources.php') ?>" target="_blank">View Educational &rarr;</a>
        </div>
    </div>
</div>

<!-- Resource Form Card (Add or Edit) -->
<section class="admin-card" id="resource-form">
    <div class="admin-card-header">
        <div>
            <p class="eyebrow" style="margin-bottom: 2px;"><?= $editingResource ? 'Edit Resource #' . $editingResource['resource_id'] : 'New Resource Upload' ?></p>
            <h2><?= $editingResource ? 'Edit: ' . e($editingResource['title']) : 'Add New Resource' ?></h2>
            <p><?= $editingResource ? 'Update details, download files, or video URLs.' : 'Provide guides, recipe cards, or tutorials for home cooks.' ?></p>
        </div>
        <?php if ($editingResource): ?>
            <a class="button button-small button-outline" href="<?= url('manage-resources.php') ?>">Cancel Edit</a>
        <?php endif; ?>
    </div>

    <?php if ($errors): ?>
        <div class="form-errors" role="alert" style="margin-bottom: 20px;">
            <strong>Please correct the following:</strong>
            <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form class="stack-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="save_resource">
        <?php if ($editingResource): ?>
            <input type="hidden" name="resource_id" value="<?= (int) $editingResource['resource_id'] ?>">
        <?php endif; ?>

        <div class="form-row">
            <label>Resource Title
                <input type="text" name="title" maxlength="100" value="<?= e($editingResource['title'] ?? ($_POST['title'] ?? '')) ?>" placeholder="e.g. Traditional Mohinga Preparation Guide" required>
            </label>

            <label>Resource Category
                <select name="resource_category" required>
                    <option value="culinary" <?= ($editingResource['resource_category'] ?? ($_POST['resource_category'] ?? 'culinary')) === 'culinary' ? 'selected' : '' ?>>Culinary Resources</option>
                    <option value="educational" <?= ($editingResource['resource_category'] ?? ($_POST['resource_category'] ?? '')) === 'educational' ? 'selected' : '' ?>>Educational Resources</option>
                </select>
            </label>

            <label>Resource Type
                <select name="resource_type" required>
                    <option value="tutorial" <?= ($editingResource['resource_type'] ?? ($_POST['resource_type'] ?? 'tutorial')) === 'tutorial' ? 'selected' : '' ?>>Tutorial</option>
                    <option value="recipe_card" <?= ($editingResource['resource_type'] ?? '') === 'recipe_card' ? 'selected' : '' ?>>Recipe Card (PDF)</option>
                    <option value="video" <?= ($editingResource['resource_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video (YouTube)</option>
                    <option value="guide" <?= ($editingResource['resource_type'] ?? '') === 'guide' ? 'selected' : '' ?>>Guide</option>
                    <option value="article" <?= ($editingResource['resource_type'] ?? '') === 'article' ? 'selected' : '' ?>>Article / Lesson</option>
                </select>
            </label>
        </div>

        <label>Description / Overview
            <textarea name="description" rows="3" maxlength="1000" placeholder="Describe the resource, techniques covered, or learning outcomes..." required><?= e($editingResource['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
        </label>

        <!-- Resource File or URL -->
        <fieldset style="border: 1px solid var(--line); border-radius: 12px; padding: 18px; margin: 12px 0; background: #fafbfa;">
            <legend style="font-weight: 700; padding: 0 8px; color: var(--ink);">Resource File or Video Link</legend>

            <?php if ($editingResource && $editingResource['file_path']): ?>
                <div style="margin-bottom: 12px; font-size: .85rem;">
                    <strong>Current File / Link:</strong> 
                    <a href="<?= e(str_starts_with($editingResource['file_path'], 'http') ? $editingResource['file_path'] : url($editingResource['file_path'])) ?>" target="_blank" rel="noopener">
                        <?= e($editingResource['file_path']) ?>
                    </a>
                </div>
            <?php endif; ?>

            <label for="resource_file">Option 1: Upload PDF / Tutorial File
                <input id="resource_file" type="file" name="resource_file" accept=".pdf,.doc,.docx,.html,.txt">
                <small>Upload downloadable PDF guide, recipe card, or tutorial (max 15MB).</small>
            </label>

            <div style="text-align: center; margin: 10px 0; font-weight: 700; color: var(--ink-soft); font-size: .85rem;">— OR —</div>

            <label for="file_url">Option 2: Video or External URL
                <input id="file_url" type="url" name="file_url" value="<?= e($editingResource['file_path'] ?? ($_POST['file_url'] ?? '')) ?>" placeholder="https://www.youtube.com/watch?v=...">
                <small>Paste a YouTube video URL or article link.</small>
            </label>
        </fieldset>

        <!-- Thumbnail -->
        <fieldset style="border: 1px solid var(--line); border-radius: 12px; padding: 18px; margin: 12px 0; background: #fafbfa;">
            <legend style="font-weight: 700; padding: 0 8px; color: var(--ink);">Cover Thumbnail Image</legend>

            <?php if ($editingResource && $editingResource['thumbnail_path']): ?>
                <div style="margin-bottom: 12px;">
                    <img src="<?= e(str_starts_with($editingResource['thumbnail_path'], 'http') ? $editingResource['thumbnail_path'] : url($editingResource['thumbnail_path'])) ?>" alt="" style="max-height: 90px; border-radius: 8px; object-fit: cover; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                </div>
            <?php endif; ?>

            <label for="thumbnail_file">Upload Thumbnail File
                <input id="thumbnail_file" type="file" name="thumbnail_file" accept="image/*">
                <small>Upload JPG or PNG preview image.</small>
            </label>

            <div style="text-align: center; margin: 10px 0; font-weight: 700; color: var(--ink-soft); font-size: .85rem;">— OR —</div>

            <label for="thumbnail_url">Thumbnail Image URL
                <input id="thumbnail_url" type="url" name="thumbnail_url" value="<?= e($editingResource['thumbnail_path'] ?? ($_POST['thumbnail_url'] ?? '')) ?>" placeholder="https://images.unsplash.com/...">
            </label>
        </fieldset>

        <div class="form-actions" style="margin-top: 18px;">
            <button class="button" type="submit"><?= $editingResource ? 'Update Resource' : 'Save &amp; Publish Resource' ?></button>
            <?php if ($editingResource): ?>
                <a class="button button-outline" href="<?= url('manage-resources.php') ?>">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<!-- Resources Table Card -->
<section class="admin-card" id="resources-list">
    <div class="admin-card-header">
        <div>
            <p class="eyebrow" style="margin-bottom: 2px;">Resource Library</p>
            <h2>Published Resources (<?= count($allResources) ?>)</h2>
            <p>Filter between educational guides and culinary materials.</p>
        </div>
    </div>

    <!-- Category Switcher Tabs -->
    <div class="resource-switcher" style="margin-bottom: 22px;">
        <a class="<?= $catFilter === '' ? 'active' : '' ?>" href="<?= url('manage-resources.php') ?>">All Resources (<?= $culinaryCount + $educationalCount ?>)</a>
        <a class="<?= $catFilter === 'culinary' ? 'active' : '' ?>" href="<?= url('manage-resources.php?category=culinary') ?>">Culinary (<?= $culinaryCount ?>)</a>
        <a class="<?= $catFilter === 'educational' ? 'active' : '' ?>" href="<?= url('manage-resources.php?category=educational') ?>">Educational (<?= $educationalCount ?>)</a>
    </div>

    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 65px;">Cover</th>
                    <th>Resource Title &amp; Summary</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Download / Link</th>
                    <th>Date Added</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allResources as $res): ?>
                <tr>
                    <td>
                        <img class="admin-table-img" src="<?= e(str_starts_with($res['thumbnail_path'], 'http') ? $res['thumbnail_path'] : url($res['thumbnail_path'] ?: 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=80')) ?>" alt="">
                    </td>
                    <td>
                        <strong><?= e($res['title']) ?></strong>
                        <small style="color: var(--ink-soft);"><?= e(substr($res['description'], 0, 75)) ?>...</small>
                    </td>
                    <td>
                        <span class="status <?= $res['resource_category'] === 'culinary' ? 'status-approved' : 'status-pending' ?>">
                            <?= e(ucfirst($res['resource_category'])) ?>
                        </span>
                    </td>
                    <td><span class="status" style="background: #eef2ef; color: var(--ink);"><?= e(str_replace('_', ' ', ucfirst($res['resource_type']))) ?></span></td>
                    <td>
                        <a href="<?= e(str_starts_with($res['file_path'], 'http') ? $res['file_path'] : url($res['file_path'])) ?>" target="_blank" rel="noopener" style="font-size: .82rem; font-weight: 700; color: var(--coral-dark); text-decoration: none;">
                            <?= str_ends_with(strtolower($res['file_path']), '.pdf') ? '📄 PDF Guide' : (str_starts_with($res['file_path'], 'http') ? '🔗 Video / Web' : '📁 Document') ?>
                        </a>
                    </td>
                    <td><small><?= e(date('j M Y', strtotime($res['created_at']))) ?></small></td>
                    <td style="text-align: right;">
                        <div class="table-actions" style="justify-content: flex-end;">
                            <a class="button button-small button-outline" style="padding: 4px 10px; font-size: .8rem;" href="<?= url('manage-resources.php?action=edit&id=' . $res['resource_id'] . '#resource-form') ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this resource?');" style="margin: 0;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="resource_id" value="<?= (int) $res['resource_id'] ?>">
                                <button type="submit" class="danger" style="padding: 4px 10px; font-size: .8rem;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>

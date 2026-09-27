<?php
require_once __DIR__ . '/config/app.php';
require_admin();

$errors = [];
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$recipeId = filter_var($_GET['id'] ?? ($_POST['recipe_id'] ?? null), FILTER_VALIDATE_INT);
$editingRecipe = null;

if ($recipeId) {
    $stmt = $pdo->prepare('SELECT * FROM recipes WHERE recipe_id = ?');
    $stmt->execute([$recipeId]);
    $editingRecipe = $stmt->fetch();
}

// Handle POST actions (Create, Edit, Delete, Toggle Featured, Toggle Status)
if (is_post_request()) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
        redirect('manage-recipes.php');
    }

    $postAction = $_POST['action'] ?? '';

    // DELETE RECIPE
    if ($postAction === 'delete') {
        $delId = filter_var($_POST['recipe_id'] ?? null, FILTER_VALIDATE_INT);
        if ($delId) {
            $pdo->prepare('DELETE FROM recipes WHERE recipe_id = ?')->execute([$delId]);
            set_flash('success', 'Recipe deleted successfully.');
        }
        redirect('manage-recipes.php');
    }

    // TOGGLE FEATURED
    if ($postAction === 'toggle_featured') {
        $featId = filter_var($_POST['recipe_id'] ?? null, FILTER_VALIDATE_INT);
        if ($featId) {
            $pdo->prepare('UPDATE recipes SET is_featured = NOT is_featured WHERE recipe_id = ?')->execute([$featId]);
            set_flash('success', 'Recipe featured status toggled.');
        }
        redirect('manage-recipes.php');
    }

    // CREATE OR UPDATE RECIPE
    if ($postAction === 'save_recipe') {
        $title = clean_text_input($_POST['title'] ?? '');
        $cuisine = clean_text_input($_POST['cuisine_type'] ?? '');
        $diet = clean_text_input($_POST['dietary_preference'] ?? 'None');
        $difficulty = clean_text_input($_POST['difficulty'] ?? 'Easy');
        $description = trim(strip_tags($_POST['description'] ?? ''));
        $ingredients = trim(strip_tags($_POST['ingredients'] ?? ''));
        $instructions = trim(strip_tags($_POST['instructions'] ?? ''));
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $status = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived'], true) ? $_POST['status'] : 'published';
        $imageUrl = trim($_POST['image_url'] ?? '');
        $finalImagePath = $editingRecipe['image_path'] ?? '';

        if ($title === '') $errors[] = 'Please enter a recipe title.';
        if ($cuisine === '') $errors[] = 'Please specify the cuisine type.';
        if ($description === '') $errors[] = 'Please provide a short description.';
        if ($ingredients === '') $errors[] = 'Please list the ingredients.';
        if ($instructions === '') $errors[] = 'Please provide cooking instructions.';

        // Handle file upload if provided
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                $errors[] = 'Allowed image formats are JPG, PNG, WEBP, and GIF.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image size must not exceed 5MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/recipes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newFilename = 'recipe_' . uniqid('', true) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename)) {
                    $finalImagePath = 'uploads/recipes/' . $newFilename;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
        } elseif ($imageUrl !== '') {
            if (!is_valid_image_url($imageUrl)) {
                $errors[] = 'Please enter a valid HTTPS image URL.';
            } else {
                $finalImagePath = $imageUrl;
            }
        }

        if ($finalImagePath === '') {
            $errors[] = 'Please provide an image (file upload or HTTPS URL).';
        }

        if (!$errors) {
            if ($editingRecipe) {
                $stmt = $pdo->prepare(
                    "UPDATE recipes
                     SET title = ?, cuisine_type = ?, dietary_preference = ?, difficulty = ?,
                         description = ?, ingredients = ?, instructions = ?, image_path = ?,
                         is_featured = ?, status = ?
                     WHERE recipe_id = ?"
                );
                $stmt->execute([
                    $title, $cuisine, $diet, $difficulty,
                    $description, $ingredients, $instructions, $finalImagePath,
                    $isFeatured, $status, $recipeId
                ]);
                set_flash('success', 'Recipe updated successfully.');
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO recipes
                     (user_id, title, cuisine_type, dietary_preference, difficulty,
                      description, ingredients, instructions, image_path, is_featured, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    current_user()['user_id'], $title, $cuisine, $diet, $difficulty,
                    $description, $ingredients, $instructions, $finalImagePath, $isFeatured, $status
                ]);
                set_flash('success', 'New recipe created successfully.');
            }
            redirect('manage-recipes.php');
        }
    }
}

// Fetch all recipes for listing
$filterCuisine = clean_text_input($_GET['cuisine'] ?? '');
$filterSearch = clean_text_input($_GET['q'] ?? '');

$sql = "SELECT r.*,
               (SELECT COUNT(*) FROM interactions i WHERE i.recipe_id = r.recipe_id AND i.interaction_type = 'like') AS like_count,
               CONCAT(u.first_name, ' ', u.last_name) AS creator_name
        FROM recipes r
        LEFT JOIN users u ON u.user_id = r.user_id
        WHERE 1=1";
$params = [];

if ($filterCuisine !== '') {
    $sql .= " AND r.cuisine_type = ?";
    $params[] = $filterCuisine;
}
if ($filterSearch !== '') {
    $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$filterSearch%";
    $params[] = "%$filterSearch%";
}
$sql .= " ORDER BY r.created_at DESC";

$recipesStmt = $pdo->prepare($sql);
$recipesStmt->execute($params);
$allRecipes = $recipesStmt->fetchAll();

$cuisinesList = $pdo->query("SELECT DISTINCT cuisine_type FROM recipes ORDER BY cuisine_type")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Manage Recipes';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero admin-hero">
    <div class="container">
        <p class="eyebrow">Admin Control</p>
        <h1>Manage Recipes</h1>
        <p>Create, edit, feature, and organize recipes in the FoodFusion collection.</p>
        <div class="hero-actions">
            <a class="button" href="#recipe-form"><?= $editingRecipe ? 'Edit Recipe Below' : '+ Add New Recipe' ?></a>
            <a class="button button-outline" style="color:#fff; border-color: rgba(255,255,255,.5);" href="<?= url('recipes.php') ?>" target="_blank">View Public Collection &rarr;</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">

        <!-- Recipe Form (Add or Edit) -->
        <section class="admin-panel" id="recipe-form" style="margin-bottom: 40px;">
            <div class="admin-panel-heading">
                <div>
                    <p class="eyebrow"><?= $editingRecipe ? 'Edit Recipe #' . $editingRecipe['recipe_id'] : 'New Recipe' ?></p>
                    <h2><?= $editingRecipe ? 'Edit: ' . e($editingRecipe['title']) : 'Add a New Recipe' ?></h2>
                </div>
                <?php if ($editingRecipe): ?>
                    <a class="button button-small button-outline" href="<?= url('manage-recipes.php') ?>">Cancel Edit</a>
                <?php endif; ?>
            </div>

            <?php if ($errors): ?>
                <div class="form-errors" role="alert">
                    <strong>Please correct the following errors:</strong>
                    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form class="stack-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_recipe">
                <?php if ($editingRecipe): ?>
                    <input type="hidden" name="recipe_id" value="<?= (int) $editingRecipe['recipe_id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <label>Recipe Title
                        <input type="text" name="title" maxlength="150" value="<?= e($editingRecipe['title'] ?? ($_POST['title'] ?? '')) ?>" placeholder="e.g. Traditional Mohinga (မုန့်ဟင်းခါး)" required>
                    </label>
                    <label>Cuisine Type
                        <input type="text" name="cuisine_type" maxlength="60" value="<?= e($editingRecipe['cuisine_type'] ?? ($_POST['cuisine_type'] ?? '')) ?>" placeholder="e.g. Burmese, Italian, Thai" required>
                    </label>
                </div>

                <div class="form-row">
                    <label>Dietary Preference
                        <select name="dietary_preference">
                            <?php foreach (['None', 'Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free', 'Halal'] as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($editingRecipe['dietary_preference'] ?? ($_POST['dietary_preference'] ?? 'None')) === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Difficulty Level
                        <select name="difficulty">
                            <?php foreach (['Easy', 'Medium', 'Hard'] as $lvl): ?>
                                <option value="<?= e($lvl) ?>" <?= ($editingRecipe['difficulty'] ?? ($_POST['difficulty'] ?? 'Easy')) === $lvl ? 'selected' : '' ?>><?= e($lvl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Status
                        <select name="status">
                            <option value="published" <?= ($editingRecipe['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= ($editingRecipe['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="archived" <?= ($editingRecipe['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </label>
                </div>

                <label>Short Description
                    <textarea name="description" rows="3" maxlength="1000" placeholder="A brief, appetizing summary of this dish..." required><?= e($editingRecipe['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
                </label>

                <div class="form-row">
                    <label>Ingredients (one per line)
                        <textarea name="ingredients" rows="7" placeholder="250 g rice noodles&#10;500 g catfish fillet&#10;3 stalks lemongrass..." required><?= e($editingRecipe['ingredients'] ?? ($_POST['ingredients'] ?? '')) ?></textarea>
                    </label>
                    <label>Instructions (numbered steps)
                        <textarea name="instructions" rows="7" placeholder="1. Simmer fish with aromatics...&#10;2. Whisk chickpea flour in stock...&#10;3. Assemble bowls..." required><?= e($editingRecipe['instructions'] ?? ($_POST['instructions'] ?? '')) ?></textarea>
                    </label>
                </div>

                <fieldset style="border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin: 10px 0;">
                    <legend style="font-weight: 700; padding: 0 8px; color: var(--ink);">Recipe Photograph</legend>
                    
                    <?php if ($editingRecipe && $editingRecipe['image_path']): ?>
                        <div style="margin-bottom: 12px;">
                            <small style="display: block; margin-bottom: 4px; color: var(--ink-soft);">Current Image:</small>
                            <img src="<?= e(str_starts_with($editingRecipe['image_path'], 'http') ? $editingRecipe['image_path'] : url($editingRecipe['image_path'])) ?>" alt="Preview" style="max-height: 120px; border-radius: 8px; object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <label for="image_file">Upload Image File
                        <input id="image_file" type="file" name="image_file" accept="image/*">
                        <small>Upload JPG, PNG, or WEBP from your computer.</small>
                    </label>

                    <div style="text-align: center; margin: 8px 0; font-weight: 700; color: var(--ink-soft);">— OR —</div>

                    <label for="image_url">Image URL
                        <input id="image_url" type="url" name="image_url" value="<?= e($editingRecipe['image_path'] ?? ($_POST['image_url'] ?? '')) ?>" placeholder="https://images.unsplash.com/...">
                        <small>Or paste a direct HTTPS image URL.</small>
                    </label>
                </fieldset>

                <div style="margin: 12px 0;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_featured" value="1" <?= (!empty($editingRecipe['is_featured']) || !empty($_POST['is_featured'])) ? 'checked' : '' ?> style="width: auto;">
                        <strong>Feature this recipe on the homepage</strong>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button" type="submit"><?= $editingRecipe ? 'Update Recipe' : 'Publish Recipe' ?></button>
                    <?php if ($editingRecipe): ?>
                        <a class="button button-outline" href="<?= url('manage-recipes.php') ?>">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Recipes Table & Filter -->
        <section class="admin-panel" id="recipes-list">
            <div class="admin-panel-heading">
                <div>
                    <p class="eyebrow">Database Catalog</p>
                    <h2>Existing Recipes (<?= count($allRecipes) ?>)</h2>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="get" class="filter-bar" style="margin-bottom: 20px;">
                <label>Search
                    <input type="search" name="q" value="<?= e($filterSearch) ?>" placeholder="Title or keyword">
                </label>
                <label>Cuisine
                    <select name="cuisine">
                        <option value="">All Cuisines</option>
                        <?php foreach ($cuisinesList as $c): ?>
                            <option value="<?= e($c) ?>" <?= $filterCuisine === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button button-small" type="submit">Filter</button>
                <a class="button button-small button-outline" href="<?= url('manage-recipes.php') ?>">Reset</a>
            </form>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Recipe</th>
                            <th>Cuisine</th>
                            <th>Difficulty</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allRecipes as $r): ?>
                        <tr>
                            <td style="width: 70px;">
                                <img src="<?= e(str_starts_with($r['image_path'], 'http') ? $r['image_path'] : url($r['image_path'])) ?>" alt="" style="width: 60px; height: 45px; object-fit: cover; border-radius: 6px;">
                            </td>
                            <td>
                                <strong><a href="<?= url('recipe.php?id=' . $r['recipe_id']) ?>" target="_blank"><?= e($r['title']) ?></a></strong>
                                <br><small style="color: var(--ink-soft);">&hearts; <?= (int)$r['like_count'] ?> likes &middot; <?= e($r['creator_name'] ?? 'Admin') ?></small>
                            </td>
                            <td><?= e($r['cuisine_type']) ?></td>
                            <td><?= e($r['difficulty']) ?></td>
                            <td>
                                <form method="post" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_featured">
                                    <input type="hidden" name="recipe_id" value="<?= (int) $r['recipe_id'] ?>">
                                    <button type="submit" class="button button-small <?= $r['is_featured'] ? '' : 'button-outline' ?>" style="padding: 4px 8px; font-size: .75rem;">
                                        <?= $r['is_featured'] ? '★ Featured' : '☆ Not Featured' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <span class="status status-<?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-small button-outline" href="<?= url('manage-recipes.php?action=edit&id=' . $r['recipe_id'] . '#recipe-form') ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to permanently delete this recipe?');" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="recipe_id" value="<?= (int) $r['recipe_id'] ?>">
                                        <button type="submit" class="danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

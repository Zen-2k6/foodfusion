<?php
require_once __DIR__ . '/../config/app.php';

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Please log in to submit a recipe.'], 401);
}
if (!is_post_request() || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response(['success' => false, 'message' => 'Invalid request. Refresh and try again.'], 403);
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$ingredients = trim($_POST['ingredients'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');
$cuisine = trim($_POST['cuisine_type'] ?? '');
$diet = trim($_POST['dietary_preference'] ?? 'None');
$difficulty = $_POST['difficulty'] ?? '';
$imageUrl = trim($_POST['image_url'] ?? '');

if ($title === '' || $description === '' || $ingredients === '' || $instructions === '' || $cuisine === '') {
    json_response(['success' => false, 'message' => 'Please complete every required recipe field.'], 422);
}
if (!in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
    json_response(['success' => false, 'message' => 'Please choose a valid difficulty.'], 422);
}
if (!is_valid_image_url($imageUrl)) {
    json_response(['success' => false, 'message' => 'The image must use a valid HTTPS URL.'], 422);
}

if ($imageUrl === '') {
    $imageUrl = 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80';
}

try {
    $pdo->beginTransaction();
    $recipe = $pdo->prepare(
        "INSERT INTO recipes
         (user_id, title, description, ingredients, instructions, cuisine_type,
          dietary_preference, difficulty, image_path, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')"
    );
    $recipe->execute([
        current_user()['user_id'], $title, $description, $ingredients,
        $instructions, $cuisine, $diet, $difficulty, $imageUrl
    ]);
    $recipeId = (int) $pdo->lastInsertId();

    $post = $pdo->prepare(
        "INSERT INTO community_posts
         (user_id, post_type, title, content, image_path, status)
         VALUES (?, 'recipe', ?, ?, ?, 'pending')"
    );
    $post->execute([current_user()['user_id'], $title, $description, $imageUrl]);
    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Recipe saved. Its community post is waiting for admin approval.',
        'redirect' => url('recipe.php?id=' . $recipeId)
    ]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'The recipe could not be saved. Please try again.'], 500);
}

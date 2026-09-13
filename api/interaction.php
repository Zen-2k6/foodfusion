<?php
require_once __DIR__ . '/../config/app.php';

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Please log in first.'], 401);
}
if (!is_post_request() || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    json_response(['success' => false, 'message' => 'Invalid request. Refresh the page and try again.'], 403);
}

$recipeId = filter_var($_POST['recipe_id'] ?? null, FILTER_VALIDATE_INT);
$type = $_POST['interaction_type'] ?? '';
if (!$recipeId || !in_array($type, ['like', 'save'], true)) {
    json_response(['success' => false, 'message' => 'Invalid recipe interaction.'], 422);
}

$recipeCheck = $pdo->prepare("SELECT recipe_id FROM recipes WHERE recipe_id = ? AND status = 'published'");
$recipeCheck->execute([$recipeId]);
if (!$recipeCheck->fetch()) {
    json_response(['success' => false, 'message' => 'Recipe not found.'], 404);
}

$userId = current_user()['user_id'];
$existing = $pdo->prepare(
    'SELECT interaction_id FROM interactions WHERE user_id = ? AND recipe_id = ? AND interaction_type = ? LIMIT 1'
);
$existing->execute([$userId, $recipeId, $type]);
$interaction = $existing->fetch();

if ($interaction) {
    $pdo->prepare('DELETE FROM interactions WHERE user_id = ? AND recipe_id = ? AND interaction_type = ?')
        ->execute([$userId, $recipeId, $type]);
    $active = false;
} else {
    $pdo->prepare('INSERT INTO interactions (user_id, recipe_id, interaction_type) VALUES (?, ?, ?)')
        ->execute([$userId, $recipeId, $type]);
    $active = true;
}

$count = $pdo->prepare('SELECT COUNT(*) FROM interactions WHERE recipe_id = ? AND interaction_type = ?');
$count->execute([$recipeId, $type]);

json_response([
    'success' => true,
    'active' => $active,
    'count' => (int) $count->fetchColumn(),
    'message' => $active ? ucfirst($type) . ' added.' : ucfirst($type) . ' removed.'
]);


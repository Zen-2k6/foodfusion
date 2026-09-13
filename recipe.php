<?php
require_once __DIR__ . '/config/app.php';

$recipeId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$recipeId) {
    http_response_code(404);
    exit('Recipe not found.');
}

$statement = $pdo->prepare(
    "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name,
            (SELECT COUNT(*) FROM interactions i WHERE i.recipe_id = r.recipe_id AND i.interaction_type = 'like') AS like_count,
            (SELECT COUNT(*) FROM interactions i WHERE i.recipe_id = r.recipe_id AND i.interaction_type = 'save') AS save_count
     FROM recipes r
     LEFT JOIN users u ON u.user_id = r.user_id
     WHERE r.recipe_id = ? AND r.status = 'published'"
);
$statement->execute([$recipeId]);
$recipe = $statement->fetch();

if (!$recipe) {
    http_response_code(404);
    exit('Recipe not found.');
}

if (is_post_request()) {
    require_login();
    $comment = trim($_POST['comment_text'] ?? '');

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'The form expired. Please try again.');
    } elseif ($comment === '' || strlen($comment) > 1000) {
        set_flash('error', 'Please enter a comment of no more than 1,000 characters.');
    } else {
        $insert = $pdo->prepare('INSERT INTO comments (user_id, recipe_id, comment_text) VALUES (?, ?, ?)');
        $insert->execute([current_user()['user_id'], $recipeId, $comment]);
        set_flash('success', 'Your comment was added.');
    }
    redirect('recipe.php?id=' . $recipeId . '#comments');
}

if (is_logged_in()) {
    $pdo->prepare("INSERT INTO interactions (user_id, recipe_id, interaction_type) VALUES (?, ?, 'view')")
        ->execute([current_user()['user_id'], $recipeId]);
}

$comments = $pdo->prepare(
    "SELECT c.comment_text, c.created_at, CONCAT(u.first_name, ' ', u.last_name) AS author_name
     FROM comments c
     INNER JOIN users u ON u.user_id = c.user_id
     WHERE c.recipe_id = ?
     ORDER BY c.created_at DESC"
);
$comments->execute([$recipeId]);

$activeInteractions = [];
if (is_logged_in()) {
    $active = $pdo->prepare(
        "SELECT DISTINCT interaction_type FROM interactions
         WHERE user_id = ? AND recipe_id = ? AND interaction_type IN ('like', 'save')"
    );
    $active->execute([current_user()['user_id'], $recipeId]);
    $activeInteractions = $active->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = $recipe['title'];
require __DIR__ . '/includes/header.php';
?>

<article class="recipe-detail">
    <div class="container recipe-detail-grid">
        <div class="recipe-detail-image"><img src="<?= e($recipe['image_path']) ?>" alt="<?= e($recipe['title']) ?>"></div>
        <div class="recipe-intro">
            <a class="back-link" href="<?= url('recipes.php') ?>">&larr; Back to recipes</a>
            <div class="tag-row"><span><?= e($recipe['cuisine_type']) ?></span><span><?= e($recipe['difficulty']) ?></span><span><?= e($recipe['dietary_preference']) ?></span></div>
            <h1><?= e($recipe['title']) ?></h1>
            <p class="lead"><?= e($recipe['description']) ?></p>
            <p class="byline">Shared by <?= e($recipe['author_name'] ?? 'FoodFusion') ?></p>
            <?php if (is_logged_in()): ?>
                <div class="interaction-row">
                    <button class="button button-outline interaction-button <?= in_array('like', $activeInteractions, true) ? 'is-active' : '' ?>" type="button" data-recipe-id="<?= $recipeId ?>" data-interaction="like">
                        &hearts; Like <span data-count><?= (int) $recipe['like_count'] ?></span>
                    </button>
                    <button class="button button-outline interaction-button <?= in_array('save', $activeInteractions, true) ? 'is-active' : '' ?>" type="button" data-recipe-id="<?= $recipeId ?>" data-interaction="save">
                        &#9734; Save <span data-count><?= (int) $recipe['save_count'] ?></span>
                    </button>
                </div>
                <p class="interaction-message" aria-live="polite"></p>
            <?php else: ?>
                <div class="visitor-notice">
                    <strong>Want to like or save this recipe?</strong>
                    <p><a href="<?= url('login.php') ?>">Log in</a> or <a class="text-button" href="<?= url('register.php') ?>" data-open-join>register</a> to use member features.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container recipe-method-grid">
        <section><p class="eyebrow">What you need</p><h2>Ingredients</h2><div class="method-text"><?= nl2br(e($recipe['ingredients'])) ?></div></section>
        <section><p class="eyebrow">Step by step</p><h2>Instructions</h2><div class="method-text"><?= nl2br(e($recipe['instructions'])) ?></div></section>
    </div>
</article>

<section class="section section-tint" id="comments">
    <div class="container narrow">
        <div class="section-heading"><div><p class="eyebrow">Around the table</p><h2>Community comments</h2></div></div>

        <?php if (is_logged_in()): ?>
            <form class="comment-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <label for="comment_text">Share a helpful comment</label>
                <textarea id="comment_text" name="comment_text" rows="4" maxlength="1000" required></textarea>
                <button class="button" type="submit">Post comment</button>
            </form>
        <?php else: ?>
            <p class="callout"><a href="<?= url('login.php') ?>">Log in</a> or <a class="text-button" href="<?= url('register.php') ?>" data-open-join>register</a> to comment on this recipe.</p>
        <?php endif; ?>

        <div class="comment-list">
            <?php foreach ($comments as $comment): ?>
                <article class="comment">
                    <div class="comment-avatar"><?= e(strtoupper(substr($comment['author_name'], 0, 1))) ?></div>
                    <div><strong><?= e($comment['author_name']) ?></strong><time><?= e(date('j M Y', strtotime($comment['created_at']))) ?></time><p><?= e($comment['comment_text']) ?></p></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

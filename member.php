<?php
require_once __DIR__ . '/config/app.php';
require_login();
if (is_admin()) {
    redirect('admin.php');
}

$userId = current_user()['user_id'];
$statement = $pdo->prepare(
    "SELECT COUNT(*) AS total, COALESCE(SUM(status = 'pending'), 0) AS pending
     FROM community_posts WHERE user_id = ?"
);
$statement->execute([$userId]);
$counts = $statement->fetch();
$statement = $pdo->prepare(
    "SELECT r.recipe_id, r.title, r.description, r.image_path
     FROM recipes r
     WHERE r.status = 'published' AND EXISTS (
         SELECT 1 FROM interactions i
         WHERE i.recipe_id = r.recipe_id AND i.user_id = ? AND i.interaction_type = 'save'
     ) ORDER BY r.created_at DESC"
);
$statement->execute([$userId]);
$savedRecipes = $statement->fetchAll();
$pageTitle = 'My Home';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero compact-hero wall-hero">
    <div class="container">
        <p class="eyebrow">Member home</p>
        <h1>Welcome back, <?= e(current_user()['first_name']) ?>.</h1>
        <p>Your saved recipes and community contributions, all in one place.</p>
        <div class="hero-actions">
            <a class="button" href="<?= url('community-post-form.php') ?>">Create a post</a>
            <a class="button button-outline" href="<?= url('my-wall.php') ?>">My Wall</a>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="admin-stats">
            <article><span>My posts</span><strong><?= (int) $counts['total'] ?></strong></article>
            <article><span>Awaiting review</span><strong><?= (int) $counts['pending'] ?></strong></article>
            <article><span>Saved recipes</span><strong><?= count($savedRecipes) ?></strong></article>
        </div>
        <div class="section-heading">
            <h2>My saved recipes</h2>
            <a class="arrow-link" href="<?= url('recipes.php') ?>">Explore recipes &rarr;</a>
        </div>
        <?php if ($savedRecipes): ?>
            <div class="card-grid recipe-grid">
                <?php foreach ($savedRecipes as $recipe): ?>
                    <article class="recipe-card">
                        <a href="<?= url('recipe.php?id=' . $recipe['recipe_id']) ?>">
                            <img src="<?= e($recipe['image_path']) ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                        </a>
                        <div class="card-body">
                            <h3><a href="<?= url('recipe.php?id=' . $recipe['recipe_id']) ?>"><?= e($recipe['title']) ?></a></h3>
                            <p><?= e($recipe['description']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><p>No saved recipes yet. Explore the collection and save your favourites.</p></div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

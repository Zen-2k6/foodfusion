<?php
require_once __DIR__ . '/config/app.php';

$keyword = clean_text_input($_GET['keyword'] ?? '');
$cuisine = clean_text_input($_GET['cuisine'] ?? '');
$diet = clean_text_input($_GET['diet'] ?? '');
$difficulty = clean_text_input($_GET['difficulty'] ?? '');

$sql = "SELECT r.*,
               (SELECT COUNT(*) FROM interactions i WHERE i.recipe_id = r.recipe_id AND i.interaction_type = 'like') AS like_count
        FROM recipes r
        WHERE r.status = 'published'";
$parameters = [];

if ($keyword !== '') {
    $sql .= ' AND (r.title LIKE ? OR r.description LIKE ? OR r.cuisine_type LIKE ?)';
    $searchTerm = '%' . $keyword . '%';
    array_push($parameters, $searchTerm, $searchTerm, $searchTerm);
}
if ($cuisine !== '') {
    $sql .= ' AND r.cuisine_type = ?';
    $parameters[] = $cuisine;
}
if ($diet !== '') {
    $sql .= ' AND r.dietary_preference = ?';
    $parameters[] = $diet;
}
if (in_array($difficulty, ['Easy', 'Medium', 'Hard'], true)) {
    $sql .= ' AND r.difficulty = ?';
    $parameters[] = $difficulty;
}
$sql .= ' ORDER BY r.is_featured DESC, r.created_at DESC';

$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$recipes = $statement->fetchAll();

$cuisines = $pdo->query("SELECT DISTINCT cuisine_type FROM recipes WHERE status = 'published' ORDER BY cuisine_type")->fetchAll();
$diets = $pdo->query("SELECT DISTINCT dietary_preference FROM recipes WHERE status = 'published' ORDER BY dietary_preference")->fetchAll();

$pageTitle = 'Recipe collection';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero recipe-hero">
    <div class="container">
        <p class="eyebrow light">Find your next favourite</p>
        <h1>Recipes for curious cooks.</h1>
        <p>Explore dishes by cuisine, dietary preference and cooking difficulty.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <form class="filter-bar" method="get" aria-label="Filter recipes">
            <label>Search
                <input type="search" name="keyword" value="<?= e($keyword) ?>" placeholder="Try pasta or Thai">
            </label>
            <label>Cuisine
                <select name="cuisine">
                    <option value="">All cuisines</option>
                    <?php foreach ($cuisines as $item): ?>
                        <option value="<?= e($item['cuisine_type']) ?>" <?= $cuisine === $item['cuisine_type'] ? 'selected' : '' ?>><?= e($item['cuisine_type']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Diet
                <select name="diet">
                    <option value="">All preferences</option>
                    <?php foreach ($diets as $item): ?>
                        <option value="<?= e($item['dietary_preference']) ?>" <?= $diet === $item['dietary_preference'] ? 'selected' : '' ?>><?= e($item['dietary_preference']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Difficulty
                <select name="difficulty">
                    <option value="">All levels</option>
                    <?php foreach (['Easy', 'Medium', 'Hard'] as $level): ?>
                        <option value="<?= $level ?>" <?= $difficulty === $level ? 'selected' : '' ?>><?= $level ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button" type="submit">Apply filters</button>
            <a class="button button-outline" href="<?= url('recipes.php') ?>">Clear</a>
        </form>

        <div class="results-heading">
            <h2><?= count($recipes) ?> recipe<?= count($recipes) === 1 ? '' : 's' ?><?= $keyword !== '' ? ' matching “' . e($keyword) . '”' : '' ?></h2>
        </div>

        <?php if ($recipes): ?>
            <div class="card-grid recipe-grid">
                <?php foreach ($recipes as $recipe): ?>
                    <article class="recipe-card">
                        <a href="<?= url('recipe.php?id=' . $recipe['recipe_id']) ?>">
                            <img src="<?= e($recipe['image_path']) ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                        </a>
                        <div class="card-body">
                            <div class="tag-row"><span><?= e($recipe['cuisine_type']) ?></span><span><?= e($recipe['difficulty']) ?></span></div>
                            <h3><a href="<?= url('recipe.php?id=' . $recipe['recipe_id']) ?>"><?= e($recipe['title']) ?></a></h3>
                            <p><?= e($recipe['description']) ?></p>
                            <div class="card-meta"><span><?= e($recipe['dietary_preference']) ?></span><span>&hearts; <?= (int) $recipe['like_count'] ?></span></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><h2>No recipes found</h2><p>Try another keyword or remove one of the filters.</p></div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

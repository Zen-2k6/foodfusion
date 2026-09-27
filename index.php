<?php
require_once __DIR__ . '/config/app.php';

if (is_logged_in() && !is_admin() && !isset($_GET['preview'])) {
    redirect('member.php');
}

$featuredRecipes = $pdo->query(
    "SELECT recipe_id, title, description, cuisine_type, dietary_preference,
            difficulty, image_path
     FROM recipes
     WHERE status = 'published' AND is_featured = 1
     ORDER BY created_at DESC
     LIMIT 3"
)->fetchAll();

$newsPosts = $pdo->query(
    'SELECT title, description, image_path, published_at
     FROM news_posts
     ORDER BY published_at DESC
     LIMIT 3'
)->fetchAll();

$events = $pdo->query(
    'SELECT title, description, event_date, location, image_path
     FROM events
     WHERE event_date >= NOW()
     ORDER BY event_date ASC
     LIMIT 5'
)->fetchAll();

$pageTitle = 'Cook, share and belong';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-image" role="img" aria-label="Fresh ingredients prepared in a bright kitchen"></div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <p class="eyebrow light">Cook with curiosity</p>
        <h1>Good food becomes<br><em>great</em> when shared.</h1>
        <p>FoodFusion is a welcoming place for home cooks to discover global recipes, learn practical skills and share the stories behind every dish.</p>
        <div class="hero-actions">
            <a class="button" href="<?= url('recipes.php') ?>">Explore recipes</a>
            <?php if (!is_logged_in()): ?>
                <a class="button button-light" href="<?= url('register.php') ?>" data-open-join>Sign up now</a>
            <?php else: ?>
                <a class="button button-light" href="<?= url('community.php#share') ?>">Share your recipe</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="mission-strip">
    <div class="container mission-grid">
        <p class="eyebrow">Our mission</p>
        <h2>Helping everyday cooks feel creative, capable and connected.</h2>
        <p>From quick weekday meals to treasured family recipes, we make cooking knowledge easy to explore and enjoyable to pass on.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Made for real kitchens</p>
                <h2>Featured recipes</h2>
            </div>
            <a class="arrow-link" href="<?= url('recipes.php') ?>">View the collection &rarr;</a>
        </div>

        <div class="card-grid recipe-grid">
            <?php foreach ($featuredRecipes as $recipe): ?>
                <article class="recipe-card">
                    <a href="<?= url('recipe.php?id=' . $recipe['recipe_id']) ?>">
                        <img src="<?= e($recipe['image_path']) ?>" alt="<?= e($recipe['title']) ?>" loading="lazy">
                    </a>
                    <div class="card-body">
                        <div class="tag-row">
                            <span><?= e($recipe['cuisine_type']) ?></span>
                            <span><?= e($recipe['difficulty']) ?></span>
                        </div>
                        <h3><a href="<?= url('recipe.php?id=' . $recipe['recipe_id']) ?>"><?= e($recipe['title']) ?></a></h3>
                        <p><?= e($recipe['description']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-tint">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">What is cooking</p>
                <h2>FoodFusion news</h2>
            </div>
        </div>
        <div class="news-grid">
            <?php foreach ($newsPosts as $news): ?>
                <article class="news-card">
                    <img src="<?= e($news['image_path']) ?>" alt="" loading="lazy">
                    <div>
                        <time datetime="<?= e(date('Y-m-d', strtotime($news['published_at']))) ?>">
                            <?= e(date('j F Y', strtotime($news['published_at']))) ?>
                        </time>
                        <h3><?= e($news['title']) ?></h3>
                        <p><?= e($news['description']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section event-section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Learn together</p>
                <h2>Upcoming cooking events</h2>
            </div>
            <?php if (count($events) > 1): ?>
                <div class="carousel-buttons">
                    <button class="round-button" type="button" data-carousel-prev aria-label="Previous event">&larr;</button>
                    <button class="round-button" type="button" data-carousel-next aria-label="Next event">&rarr;</button>
                </div>
            <?php endif; ?>
        </div>

        <div class="event-carousel" data-carousel>
            <?php foreach ($events as $index => $event): ?>
                <article class="event-slide <?= $index === 0 ? 'is-active' : '' ?>" data-slide>
                    <img src="<?= e($event['image_path']) ?>" alt="<?= e($event['title']) ?>" loading="lazy">
                    <div class="event-content">
                        <p class="event-date"><?= e(date('D, j M Y - g:i A', strtotime($event['event_date']))) ?></p>
                        <h3><?= e($event['title']) ?></h3>
                        <p><?= e($event['description']) ?></p>
                        <strong><?= e($event['location']) ?></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="join-band">
    <div class="container join-band-content">
        <div>
            <p class="eyebrow light">Your seat is ready</p>
            <h2>Bring your favourite dish to the FoodFusion table.</h2>
        </div>
        <a class="button button-light" href="<?= is_logged_in() ? url('community.php') : url('register.php') ?>" <?= !is_logged_in() ? 'data-open-join' : '' ?>>
            <?= is_logged_in() ? 'Visit the community' : 'Join the community' ?>
        </a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>


<?php
require_once __DIR__ . '/config/app.php';
require_login();
if (is_admin()) {
    redirect('admin.php');
}

$currentUser = current_user();
$userId = (int) $currentUser['user_id'];

// Member stats
$countsStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total, COALESCE(SUM(status = 'pending'), 0) AS pending, COALESCE(SUM(status = 'approved'), 0) AS approved
     FROM community_posts WHERE user_id = ?"
);
$countsStmt->execute([$userId]);
$counts = $countsStmt->fetch();

// Saved recipes count
$savedCountStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM interactions WHERE user_id = ? AND interaction_type = 'save'"
);
$savedCountStmt->execute([$userId]);
$savedCount = (int) $savedCountStmt->fetchColumn();

// Featured recipes
$featuredRecipes = $pdo->query(
    "SELECT r.recipe_id, r.title, r.description, r.cuisine_type, r.dietary_preference,
            r.difficulty, r.image_path,
            (SELECT COUNT(*) FROM interactions i WHERE i.recipe_id = r.recipe_id AND i.interaction_type = 'like') AS like_count
     FROM recipes r
     WHERE r.status = 'published' AND r.is_featured = 1
     ORDER BY r.created_at DESC
     LIMIT 4"
)->fetchAll();

// Saved recipes preview (up to 4)
$savedRecipesStmt = $pdo->prepare(
    "SELECT r.recipe_id, r.title, r.description, r.cuisine_type, r.difficulty, r.image_path
     FROM recipes r
     WHERE r.status = 'published' AND EXISTS (
         SELECT 1 FROM interactions i
         WHERE i.recipe_id = r.recipe_id AND i.user_id = ? AND i.interaction_type = 'save'
     ) ORDER BY r.created_at DESC
     LIMIT 4"
);
$savedRecipesStmt->execute([$userId]);
$savedRecipes = $savedRecipesStmt->fetchAll();

// Recent community highlights
$communityHighlights = $pdo->query(
    "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS author_name,
            (SELECT COUNT(*) FROM community_post_likes l WHERE l.post_id = p.post_id) AS like_count,
            (SELECT COUNT(*) FROM community_post_comments c WHERE c.post_id = p.post_id) AS comment_count
     FROM community_posts p
     INNER JOIN users u ON u.user_id = p.user_id
     WHERE p.status = 'approved'
     ORDER BY p.created_at DESC
     LIMIT 3"
)->fetchAll();

// Upcoming cooking events
$events = $pdo->query(
    'SELECT title, description, event_date, location, image_path
     FROM events
     WHERE event_date >= NOW()
     ORDER BY event_date ASC
     LIMIT 5'
)->fetchAll();

// FoodFusion news posts
$newsPosts = $pdo->query(
    'SELECT title, description, image_path, published_at
     FROM news_posts
     ORDER BY published_at DESC
     LIMIT 3'
)->fetchAll();

$pageTitle = 'My Home';
require __DIR__ . '/includes/header.php';
?>

<!-- Member Hero Section -->
<section class="hero member-hero">
    <div class="hero-image" role="img" aria-label="Fresh ingredients prepared in a bright kitchen"></div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <p class="eyebrow light">Member Home &middot; Welcome Back</p>
        <h1>Hello, <?= e($currentUser['first_name']) ?>.<br><em>What are we cooking today?</em></h1>
        <p>Explore seasonal recipes, browse practical guides, and share your culinary stories with fellow food lovers.</p>
        <div class="hero-actions">
            <a class="button" href="<?= url('recipes.php') ?>">Explore recipes</a>
            <a class="button button-light" href="<?= url('community-post-form.php') ?>">Share a recipe or tip</a>
            <a class="button button-outline" style="color:#fff; border-color: rgba(255,255,255,.5);" href="<?= url('my-wall.php') ?>">My Wall</a>
        </div>
    </div>
</section>

<!-- Member Dashboard Stats Strip -->
<section class="mission-strip">
    <div class="container admin-stats" style="margin-top: 0; padding-top: 0;">
        <article>
            <span>Saved recipes</span>
            <strong><?= $savedCount ?></strong>
        </article>
        <article>
            <span>My Wall posts</span>
            <strong><?= (int) $counts['total'] ?></strong>
        </article>
        <article>
            <span>Approved stories</span>
            <strong><?= (int) $counts['approved'] ?></strong>
        </article>
        <article>
            <span>Awaiting review</span>
            <strong><?= (int) $counts['pending'] ?></strong>
        </article>
    </div>
</section>

<!-- Featured Recipes Section -->
<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Handpicked inspiration</p>
                <h2>Featured recipes</h2>
            </div>
            <a class="arrow-link" href="<?= url('recipes.php') ?>">View all recipes &rarr;</a>
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
                        <div class="card-meta">
                            <span><?= e($recipe['dietary_preference']) ?></span>
                            <span>&hearts; <?= (int) $recipe['like_count'] ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Saved Recipes Quick Bar (if any) -->
<?php if ($savedRecipes): ?>
<section class="section section-tint">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Your personal kitchen</p>
                <h2>My saved recipes</h2>
            </div>
            <a class="arrow-link" href="<?= url('recipes.php') ?>">Browse more &rarr;</a>
        </div>
        <div class="card-grid recipe-grid">
            <?php foreach ($savedRecipes as $saved): ?>
                <article class="recipe-card">
                    <a href="<?= url('recipe.php?id=' . $saved['recipe_id']) ?>">
                        <img src="<?= e($saved['image_path']) ?>" alt="<?= e($saved['title']) ?>" loading="lazy">
                    </a>
                    <div class="card-body">
                        <div class="tag-row">
                            <span><?= e($saved['cuisine_type']) ?></span>
                            <span><?= e($saved['difficulty']) ?></span>
                        </div>
                        <h3><a href="<?= url('recipe.php?id=' . $saved['recipe_id']) ?>"><?= e($saved['title']) ?></a></h3>
                        <p><?= e($saved['description']) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Community Highlights -->
<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">From member kitchens</p>
                <h2>Community highlights</h2>
            </div>
            <a class="arrow-link" href="<?= url('community.php') ?>">Go to community posts &rarr;</a>
        </div>

        <div class="community-grid">
            <?php foreach ($communityHighlights as $highlight): ?>
                <article class="community-card">
                    <?php if ($highlight['image_path']): ?>
                        <img src="<?= e($highlight['image_path']) ?>" alt="<?= e($highlight['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="post-placeholder" aria-hidden="true"><span><?= e(strtoupper(substr($highlight['post_type'], 0, 1))) ?></span></div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="tag-row"><span><?= e(community_post_type_label($highlight['post_type'])) ?></span></div>
                        <h3><?= e($highlight['title']) ?></h3>
                        <p class="community-description"><?= e($highlight['content']) ?></p>
                        <div class="community-byline">
                            <span>Posted by <?= e($highlight['author_name']) ?></span>
                            <time datetime="<?= e(date('Y-m-d', strtotime($highlight['created_at']))) ?>"><?= e(date('j M Y', strtotime($highlight['created_at']))) ?></time>
                        </div>
                        <div class="community-card-stats">
                            <span>&hearts; <?= (int) $highlight['like_count'] ?> likes</span>
                            <span><?= (int) $highlight['comment_count'] ?> comments</span>
                        </div>
                        <div class="community-card-actions">
                            <a class="button button-small button-outline" href="<?= url('community.php#post-' . $highlight['post_id']) ?>">View &amp; Comment</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Upcoming Cooking Events Carousel -->
<section class="section event-section section-tint">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Learn &amp; Connect</p>
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

<!-- FoodFusion News Section -->
<section class="section">
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

<!-- Member Callout Band -->
<section class="join-band">
    <div class="container join-band-content">
        <div>
            <p class="eyebrow light">Share your culinary story</p>
            <h2>Have a treasured family recipe or kitchen tip?</h2>
        </div>
        <a class="button button-light" href="<?= url('community-post-form.php') ?>">Create a new post</a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

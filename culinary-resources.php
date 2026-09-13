<?php
require_once __DIR__ . '/config/app.php';
$statement = $pdo->prepare("SELECT * FROM resources WHERE resource_category = 'culinary' ORDER BY created_at DESC");
$statement->execute();
$resources = $statement->fetchAll();
$pageTitle = 'Culinary resources';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero resource-hero">
    <div class="container">
        <p class="eyebrow light">Build your kitchen confidence</p>
        <h1>Practical tools for better cooking.</h1>
        <p>Download recipe cards, follow clear tutorials and discover useful kitchen techniques.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="resource-switcher" aria-label="Resource categories">
            <a class="active" href="<?= url('culinary-resources.php') ?>">Culinary resources</a>
            <a href="<?= url('educational-resources.php') ?>">Educational resources</a>
        </div>
        <div class="resource-grid">
            <?php foreach ($resources as $resource): ?>
                <?php
                $isExternal = str_starts_with($resource['file_path'], 'http');
                $resourceUrl = $isExternal ? $resource['file_path'] : url($resource['file_path']);
                $buttonText = match ($resource['resource_type']) {
                    'video' => 'Watch video',
                    'tutorial' => 'Open tutorial',
                    'article' => 'Read article',
                    default => 'Download PDF',
                };
                ?>
                <article class="resource-card">
                    <img src="<?= e($resource['thumbnail_path']) ?>" alt="<?= e($resource['title']) ?>" loading="lazy">
                    <div class="card-body">
                        <div class="tag-row"><span><?= e(str_replace('_', ' ', ucfirst($resource['resource_type']))) ?></span></div>
                        <h2><?= e($resource['title']) ?></h2>
                        <p><?= e($resource['description']) ?></p>
                        <a class="button button-outline" href="<?= e($resourceUrl) ?>" <?= $isExternal ? 'target="_blank" rel="noopener"' : 'download' ?>>
                            <?= e($buttonText) ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

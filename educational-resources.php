<?php
require_once __DIR__ . '/config/app.php';
$statement = $pdo->prepare("SELECT * FROM resources WHERE resource_category = 'educational' ORDER BY created_at DESC");
$statement->execute();
$resources = $statement->fetchAll();
$pageTitle = 'Educational resources';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero education-hero">
    <div class="container">
        <p class="eyebrow light">Food for thought</p>
        <h1>Explore renewable energy.</h1>
        <p>Download guides and infographics, watch videos and read beginner-friendly articles.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="resource-strip">
            <strong>Why energy on a food website?</strong>
            <p>Sustainable food systems rely on responsible energy choices. This section is also included to meet the educational resource requirement in the assignment brief.</p>
        </div>
        <div class="resource-switcher" aria-label="Resource categories">
            <a href="<?= url('culinary-resources.php') ?>">Culinary resources</a>
            <a class="active" href="<?= url('educational-resources.php') ?>">Educational resources</a>
        </div>
        <div class="resource-grid">
            <?php foreach ($resources as $resource): ?>
                <?php
                $isExternal = str_starts_with($resource['file_path'], 'http');
                $resourceUrl = $isExternal ? $resource['file_path'] : url($resource['file_path']);
                $buttonText = match ($resource['resource_type']) {
                    'video' => 'Watch video',
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
                        <a class="button button-outline" href="<?= e($resourceUrl) ?>" <?= $isExternal ? 'target="_blank" rel="noopener"' : 'download' ?>><?= e($buttonText) ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

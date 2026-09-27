<?php
require_once __DIR__ . '/config/app.php';

$statement = $pdo->prepare("SELECT * FROM resources WHERE resource_category = 'educational' ORDER BY created_at DESC");
$statement->execute();
$resources = $statement->fetchAll();

$pageTitle = 'Educational Resources';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero education-hero">
    <div class="container">
        <p class="eyebrow light">Culinary Knowledge &amp; Skills</p>
        <h1>Food education &amp; cooking tutorials.</h1>
        <p>Explore chef-crafted tutorials on balanced culinary nutrition, kitchen food safety, knife care, and flavor science.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="resource-strip">
            <strong>Culinary Education &amp; Kitchen Science</strong>
            <p>Knowledge transforms cooking from guesswork into art. Download these reference guides and comprehensive tutorials uploaded by our culinary educators.</p>
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
                $isPdf = str_ends_with(strtolower($resource['file_path']), '.pdf');
                $buttonText = match ($resource['resource_type']) {
                    'tutorial' => $isPdf ? 'Download Tutorial' : 'Open Tutorial',
                    'video' => 'Watch Video',
                    'article' => 'Read Lesson',
                    default => 'Download PDF',
                };
                ?>
                <article class="resource-card">
                    <img src="<?= e($resource['thumbnail_path'] ?: 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=1200&q=80') ?>" alt="<?= e($resource['title']) ?>" loading="lazy">
                    <div class="card-body">
                        <div class="tag-row">
                            <span><?= e(str_replace('_', ' ', ucfirst($resource['resource_type']))) ?></span>
                            <?php if ($isPdf): ?>
                                <span style="background: #e1eedd; color: #2e6033;">Downloadable PDF</span>
                            <?php endif; ?>
                        </div>
                        <h2><?= e($resource['title']) ?></h2>
                        <p><?= e($resource['description']) ?></p>

                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                            <?php if ($isPdf): ?>
                                <a class="button button-small" href="<?= e($resourceUrl) ?>" download="<?= e(basename($resource['file_path'])) ?>">
                                    &#128196; <?= e($buttonText) ?>
                                </a>
                                <a class="button button-small button-outline" href="<?= e($resourceUrl) ?>" target="_blank" rel="noopener">
                                    Preview
                                </a>
                            <?php else: ?>
                                <a class="button button-small button-outline" href="<?= e($resourceUrl) ?>" target="_blank" rel="noopener">
                                    <?= e($buttonText) ?> &rarr;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

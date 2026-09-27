<?php
require_once __DIR__ . '/config/app.php';

$statement = $pdo->prepare("SELECT * FROM resources WHERE resource_category = 'culinary' ORDER BY created_at DESC");
$statement->execute();
$resources = $statement->fetchAll();

function extract_youtube_embed(?string $url): ?string
{
    if (!$url) return null;
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $url, $match)) {
        return 'https://www.youtube.com/embed/' . $match[1];
    }
    return null;
}

$pageTitle = 'Culinary Resources';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero resource-hero">
    <div class="container">
        <p class="eyebrow light">Build your kitchen confidence</p>
        <h1>Culinary resources &amp; cooking videos.</h1>
        <p>Download printable recipe cards and kitchen guides created by our chefs, or watch practical cooking tutorials on technique.</p>
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
                $embedUrl = $resource['resource_type'] === 'video' ? extract_youtube_embed($resource['file_path']) : null;
                $isPdf = str_ends_with(strtolower($resource['file_path']), '.pdf');
                $buttonText = match ($resource['resource_type']) {
                    'video' => 'Watch on YouTube',
                    'tutorial' => 'Open Tutorial',
                    'article' => 'Read Article',
                    default => 'Download PDF Card',
                };
                ?>
                <article class="resource-card">
                    <?php if ($embedUrl): ?>
                        <div class="video-container">
                            <iframe src="<?= e($embedUrl) ?>" title="<?= e($resource['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                        </div>
                    <?php else: ?>
                        <img src="<?= e($resource['thumbnail_path'] ?: 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=1200&q=80') ?>" alt="<?= e($resource['title']) ?>" loading="lazy">
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="tag-row">
                            <span><?= e(str_replace('_', ' ', ucfirst($resource['resource_type']))) ?></span>
                            <?php if ($isPdf): ?>
                                <span style="background: #e1eedd; color: #2e6033;">Printable PDF</span>
                            <?php elseif ($resource['resource_type'] === 'video'): ?>
                                <span style="background: #fee5df; color: #9c2a1c;">Video Lesson</span>
                            <?php endif; ?>
                        </div>
                        <h2><?= e($resource['title']) ?></h2>
                        <p><?= e($resource['description']) ?></p>
                        
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto;">
                            <?php if ($isPdf): ?>
                                <a class="button button-small" href="<?= e($resourceUrl) ?>" download="<?= e(basename($resource['file_path'])) ?>">
                                    &#128196; Download PDF
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

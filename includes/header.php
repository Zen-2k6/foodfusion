<?php
$pageTitle = $pageTitle ?? SITE_NAME;
$currentPage = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FoodFusion - recipes, cooking ideas and a friendly food community.">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle) ?> | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
</head>
<body data-base-url="<?= BASE_URL ?>">
<a class="skip-link" href="#main-content">Skip to main content</a>

<?php if (is_admin()): ?>
<div class="admin-preview-banner">
    <div class="container admin-preview-inner">
        <div class="admin-preview-left">
            <span class="admin-preview-dot"></span>
            <span>You are previewing the live website as <strong>Administrator</strong></span>
        </div>
        <a class="admin-preview-btn" href="<?= url('admin.php') ?>">&larr; Return to Admin Dashboard</a>
    </div>
</div>
<?php endif; ?>

<header class="site-header">
    <nav class="nav container" aria-label="Main navigation">
        <a class="brand" href="<?= url('index.php') ?>" aria-label="FoodFusion home">
            <span class="brand-mark">F</span>
            <span>FoodFusion</span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links">
            <span class="sr-only">Open navigation</span>
            <span></span><span></span><span></span>
        </button>

        <div class="nav-links" id="nav-links">
            <?php if (is_admin()): ?>
                <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>">Home</a>
                <a class="<?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= url('about.php') ?>">About Us</a>
                <a class="<?= in_array($currentPage, ['recipes.php', 'recipe.php'], true) ? 'active' : '' ?>" href="<?= url('recipes.php') ?>">Recipe Collection</a>
                <div class="nav-dropdown <?= in_array($currentPage, ['community.php', 'community-post.php', 'my-wall.php', 'community-post-form.php'], true) ? 'active' : '' ?>">
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="admin-community-nav-menu">
                        Community Cookbook <span aria-hidden="true">&#9662;</span>
                    </button>
                    <div class="nav-dropdown-menu" id="admin-community-nav-menu">
                        <a class="<?= in_array($currentPage, ['community.php', 'community-post.php'], true) ? 'active' : '' ?>" href="<?= url('community.php') ?>">Community Posts</a>
                        <a class="<?= in_array($currentPage, ['my-wall.php', 'community-post-form.php'], true) ? 'active' : '' ?>" href="<?= url('my-wall.php') ?>">My Wall</a>
                    </div>
                </div>
                <div class="nav-dropdown <?= in_array($currentPage, ['culinary-resources.php', 'educational-resources.php'], true) ? 'active' : '' ?>">
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="admin-resources-nav-menu">
                        Resources <span aria-hidden="true">&#9662;</span>
                    </button>
                    <div class="nav-dropdown-menu" id="admin-resources-nav-menu">
                        <a class="<?= $currentPage === 'culinary-resources.php' ? 'active' : '' ?>" href="<?= url('culinary-resources.php') ?>">Culinary Resources</a>
                        <a class="<?= $currentPage === 'educational-resources.php' ? 'active' : '' ?>" href="<?= url('educational-resources.php') ?>">Educational Resources</a>
                    </div>
                </div>
                <a class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= url('contact.php') ?>">Contact Us</a>
            <?php elseif (is_logged_in()): ?>
                <a class="<?= in_array($currentPage, ['member.php', 'index.php'], true) ? 'active' : '' ?>" href="<?= url('member.php') ?>">My Home</a>
                <a class="<?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= url('about.php') ?>">About Us</a>
                <a class="<?= in_array($currentPage, ['recipes.php', 'recipe.php'], true) ? 'active' : '' ?>" href="<?= url('recipes.php') ?>">Recipe Collection</a>
                <div class="nav-dropdown <?= in_array($currentPage, ['community.php', 'community-post.php', 'my-wall.php', 'community-post-form.php'], true) ? 'active' : '' ?>">
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="community-nav-menu">
                        Community Cookbook <span aria-hidden="true">&#9662;</span>
                    </button>
                    <div class="nav-dropdown-menu" id="community-nav-menu">
                        <a class="<?= in_array($currentPage, ['community.php', 'community-post.php'], true) ? 'active' : '' ?>" href="<?= url('community.php') ?>">Community Posts</a>
                        <a class="<?= in_array($currentPage, ['my-wall.php', 'community-post-form.php'], true) ? 'active' : '' ?>" href="<?= url('my-wall.php') ?>">My Wall</a>
                    </div>
                </div>
                <div class="nav-dropdown <?= in_array($currentPage, ['culinary-resources.php', 'educational-resources.php'], true) ? 'active' : '' ?>">
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="resources-nav-menu">
                        Resources <span aria-hidden="true">&#9662;</span>
                    </button>
                    <div class="nav-dropdown-menu" id="resources-nav-menu">
                        <a class="<?= $currentPage === 'culinary-resources.php' ? 'active' : '' ?>" href="<?= url('culinary-resources.php') ?>">Culinary Resources</a>
                        <a class="<?= $currentPage === 'educational-resources.php' ? 'active' : '' ?>" href="<?= url('educational-resources.php') ?>">Educational Resources</a>
                    </div>
                </div>
                <a class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= url('contact.php') ?>">Contact Us</a>
            <?php else: ?>
                <a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>">Home</a>
                <a class="<?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= url('about.php') ?>">About Us</a>
                <a class="<?= in_array($currentPage, ['recipes.php', 'recipe.php'], true) ? 'active' : '' ?>" href="<?= url('recipes.php') ?>">Recipe Collection</a>
                <div class="nav-dropdown <?= in_array($currentPage, ['culinary-resources.php', 'educational-resources.php'], true) ? 'active' : '' ?>">
                    <button class="nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="visitor-resources-nav-menu">
                        Resources <span aria-hidden="true">&#9662;</span>
                    </button>
                    <div class="nav-dropdown-menu" id="visitor-resources-nav-menu">
                        <a class="<?= $currentPage === 'culinary-resources.php' ? 'active' : '' ?>" href="<?= url('culinary-resources.php') ?>">Culinary Resources</a>
                        <a class="<?= $currentPage === 'educational-resources.php' ? 'active' : '' ?>" href="<?= url('educational-resources.php') ?>">Educational Resources</a>
                    </div>
                </div>
                <a class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= url('contact.php') ?>">Contact Us</a>
            <?php endif; ?>
        </div>

        <div class="nav-actions <?= $user ? 'has-user' : '' ?>">
            <?php if ($user): ?>
                <span class="nav-user"><?= is_admin() ? 'Admin' : 'Member' ?> &middot; <?= e($user['first_name']) ?></span>
                <a class="text-link" href="<?= url('profile.php') ?>">Profile</a>
                <?php if (is_admin()): ?>
                    <a class="button button-small button-admin" href="<?= url('admin.php') ?>">Admin Dashboard &rarr;</a>
                <?php endif; ?>
                <form action="<?= url('logout.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button class="button button-small button-outline" type="submit">Log out</button>
                </form>
            <?php else: ?>
                <a class="text-link" href="<?= url('login.php') ?>" data-open-auth="login">Log in</a>
                <a class="button button-small" href="<?= url('register.php') ?>" data-open-auth="join">Join us</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?> container" role="status">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>

<main id="main-content">

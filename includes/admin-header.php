<?php
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = $pageTitle ?? 'Admin Dashboard';
$currentPage = basename($_SERVER['PHP_SELF']);
$flash = get_flash();
$user = current_user();

// Live counts for sidebar badges
try {
    $sidebarCounts = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM community_posts WHERE status = 'pending') AS pending_posts,
            (SELECT COUNT(*) FROM contact_messages WHERE reply_text IS NULL) AS unreplied_messages"
    )->fetch() ?: ['pending_posts' => 0, 'unreplied_messages' => 0];
} catch (Exception $e) {
    $sidebarCounts = ['pending_posts' => 0, 'unreplied_messages' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle) ?> &middot; FoodFusion Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css?v=' . filemtime(__DIR__ . '/../assets/css/admin.css')) ?>">
</head>
<body data-base-url="<?= BASE_URL ?>" class="admin-body-shell">
<a class="skip-link" href="#admin-main-content">Skip to admin content</a>

<div class="admin-wrapper">
    <!-- Mobile Backdrop Overlay -->
    <div class="admin-sidebar-overlay" id="admin-sidebar-overlay" aria-hidden="true"></div>

    <!-- Admin Sidebar -->
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin Navigation">
        <div class="admin-sidebar-header">
            <a class="admin-brand" href="<?= url('admin.php') ?>">
                <span class="admin-brand-icon">F</span>
                <div>
                    <span class="admin-brand-title">FoodFusion</span>
                    <span class="admin-brand-badge">Control Center</span>
                </div>
            </a>
            <button class="admin-sidebar-close" id="admin-sidebar-close" type="button" aria-label="Close sidebar">&times;</button>
        </div>

        <nav class="admin-sidebar-nav">
            <div class="admin-nav-section-title">Core Management</div>
            <a class="admin-nav-link <?= $currentPage === 'admin.php' ? 'is-active' : '' ?>" href="<?= url('admin.php') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard Overview</span>
            </a>

            <div class="admin-nav-section-title">Content Publishing</div>
            <a class="admin-nav-link <?= $currentPage === 'manage-recipes.php' ? 'is-active' : '' ?>" href="<?= url('manage-recipes.php') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <span>Manage Recipes</span>
            </a>

            <a class="admin-nav-link <?= $currentPage === 'manage-resources.php' ? 'is-active' : '' ?>" href="<?= url('manage-resources.php') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                <span>Manage Resources</span>
            </a>

            <div class="admin-nav-section-title">Community &amp; Users</div>
            <a class="admin-nav-link <?= $currentPage === 'admin.php' ? '' : '' ?>" href="<?= url('admin.php#community-posts') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Post Moderation</span>
                <?php if ((int)$sidebarCounts['pending_posts'] > 0): ?>
                    <span class="admin-badge admin-badge-warning"><?= (int)$sidebarCounts['pending_posts'] ?></span>
                <?php endif; ?>
            </a>

            <a class="admin-nav-link" href="<?= url('admin.php#events') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Cooking Events</span>
            </a>

            <a class="admin-nav-link" href="<?= url('admin.php#users') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Member Accounts</span>
            </a>

            <a class="admin-nav-link" href="<?= url('admin.php#messages') ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                <span>Feedback Inbox</span>
                <?php if ((int)$sidebarCounts['unreplied_messages'] > 0): ?>
                    <span class="admin-badge admin-badge-danger"><?= (int)$sidebarCounts['unreplied_messages'] ?></span>
                <?php endif; ?>
            </a>

            <div class="admin-nav-section-title">Site View</div>
            <a class="admin-nav-link" href="<?= url('index.php') ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <span>View Live Site</span>
                <span style="margin-left: auto; font-size: 0.75rem; opacity: 0.5;">↗</span>
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <div class="admin-user-avatar">
                <?= e(strtoupper(substr($user['first_name'] ?? 'A', 0, 1))) ?>
            </div>
            <div class="admin-user-info">
                <span class="admin-user-name"><?= e(($user['first_name'] ?? 'Admin') . ' ' . ($user['last_name'] ?? '')) ?></span>
                <span class="admin-user-role">Administrator</span>
            </div>
            <form action="<?= url('logout.php') ?>" method="post" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="admin-logout-btn" title="Log Out" aria-label="Log Out">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Admin Sticky Topbar -->
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="admin-toggle-btn" id="admin-sidebar-toggle" type="button" aria-label="Toggle navigation">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>

                <nav class="admin-breadcrumbs" aria-label="Breadcrumb">
                    <a href="<?= url('admin.php') ?>">Admin Portal</a>
                    <span class="separator">/</span>
                    <span class="current"><?= e($pageTitle) ?></span>
                </nav>
            </div>

            <div class="admin-topbar-right">
                <a class="admin-btn-action admin-btn-secondary" href="<?= url('index.php') ?>" target="_blank">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                    <span>View Site</span>
                </a>
                <a class="admin-btn-action admin-btn-primary" href="<?= url('manage-recipes.php#recipe-form') ?>">
                    <span>+ New Recipe</span>
                </a>
            </div>
        </header>

        <!-- Flash Notice Messages -->
        <?php if ($flash): ?>
            <div style="padding: 16px 32px 0;">
                <div class="flash flash-<?= e($flash['type']) ?>" role="status" style="margin-bottom: 0;">
                    <?= e($flash['message']) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Main Body -->
        <main class="admin-body" id="admin-main-content">

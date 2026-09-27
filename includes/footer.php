<?php $user = current_user(); ?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand brand-light" href="<?= url(home_page()) ?>">
                <span class="brand-mark">F</span>
                <span>FoodFusion</span>
            </a>
            <p>Bringing curious home cooks together, one shared recipe at a time.</p>
        </div>
        <div>
            <h2>Explore</h2>
            <a href="<?= url('recipes.php') ?>">Recipe collection</a>
            <a href="<?= url('community.php') ?>">Community cookbook</a>
            <a href="<?= url('culinary-resources.php') ?>">Culinary resources</a>
            <a href="<?= url('educational-resources.php') ?>">Educational resources</a>
        </div>
        <div>
            <h2>Information</h2>
            <a href="<?= url('privacy.php') ?>">Privacy policy</a>
            <a href="<?= url('cookies.php') ?>">Cookie information</a>
            <a href="<?= url('contact.php') ?>">Contact us</a>
        </div>
        <div>
            <h2>Follow us</h2>
            <div class="social-links">
                <a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Follow FoodFusion on Facebook" class="social-btn social-facebook">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    <span>Facebook</span>
                </a>
                <a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Follow FoodFusion on Instagram" class="social-btn social-instagram">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    <span>Instagram</span>
                </a>
                <a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="Follow FoodFusion on YouTube" class="social-btn social-youtube">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    <span>YouTube</span>
                </a>
                <a href="https://www.tiktok.com/" target="_blank" rel="noopener" aria-label="Follow FoodFusion on TikTok" class="social-btn social-tiktok">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-1.01-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                    <span>TikTok</span>
                </a>
            </div>
        </div>
    </div>
    <p class="footer-bottom">&copy; <?= date('Y') ?> FoodFusion. Student demonstration project.</p>
</footer>

<section class="cookie-banner" id="cookie-banner" aria-label="Cookie notice" hidden>
    <div>
        <strong>A small helping of cookies</strong>
        <p>We use one essential cookie to remember your preference and keep your session working.</p>
    </div>
    <div class="cookie-actions">
        <a href="<?= url('cookies.php') ?>">Learn more</a>
        <button class="button button-small" id="accept-cookies" type="button">Accept</button>
    </div>
</section>

<?php if (!$user): ?>
<?php if (!in_array($currentPage, ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php'], true)): ?>
<div class="modal" id="auth-modal" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title" hidden>
    <div class="modal-panel auth-modal-panel">
        <button class="modal-close" type="button" data-close-auth aria-label="Close dialog">&times;</button>
        
        <!-- Tab navigation -->
        <div class="auth-tabs" role="tablist" aria-label="Account Access">
            <button class="auth-tab-btn is-active" type="button" role="tab" id="tab-join" aria-selected="true" aria-controls="panel-join" data-auth-tab="join">
                Join Us (Register)
            </button>
            <button class="auth-tab-btn" type="button" role="tab" id="tab-login" aria-selected="false" aria-controls="panel-login" data-auth-tab="login">
                Log In
            </button>
        </div>

        <!-- Join Us Tab Panel -->
        <div class="auth-tab-panel" id="panel-join" role="tabpanel" aria-labelledby="tab-join">
            <p class="eyebrow" style="margin-top: 15px;">Become part of the table</p>
            <h2 id="auth-modal-title" style="margin-bottom: 6px;">Join FoodFusion</h2>
            <p style="margin-bottom: 20px;">Create a free account to share recipes, comment and save favourites.</p>

            <form class="stack-form" action="<?= url('register.php') ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="return_to" value="<?= e($currentPage) ?>">
                <div class="form-row">
                    <label>First name
                        <input type="text" name="first_name" maxlength="50" required autocomplete="given-name">
                    </label>
                    <label>Last name
                        <input type="text" name="last_name" maxlength="50" required autocomplete="family-name">
                    </label>
                </div>
                <label>Email address
                    <input type="email" name="email" maxlength="100" required autocomplete="email">
                </label>
                <label>Password
                    <input type="password" name="password" minlength="8" required autocomplete="new-password">
                    <small>Use at least 8 characters, including a number.</small>
                </label>
                <label>Confirm password
                    <input type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
                </label>
                <button class="button button-block" type="submit">Create my account</button>
                <p style="text-align: center; margin-top: 12px; font-size: .85rem;">
                    Already have an account? <button type="button" class="text-link-button" data-switch-auth="login">Log in here</button>.
                </p>
                <button class="button button-outline" type="button" data-close-auth style="width: 100%; margin-top: 6px;">Continue as visitor</button>
            </form>
        </div>

        <!-- Log In Tab Panel -->
        <div class="auth-tab-panel" id="panel-login" role="tabpanel" aria-labelledby="tab-login" hidden>
            <p class="eyebrow" style="margin-top: 15px;">Welcome to the table</p>
            <h2 style="margin-bottom: 6px;">Log in to FoodFusion</h2>
            <p style="margin-bottom: 20px;">Save your favourite recipes and share your cooking stories.</p>

            <form class="stack-form" action="<?= url('login.php') ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <label>Email address
                    <input type="email" name="email" maxlength="100" required autocomplete="email">
                </label>
                <label>Password
                    <input type="password" name="password" required autocomplete="current-password">
                </label>
                <button class="button button-block" type="submit">Log in</button>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                    <a href="<?= url('forgot_password.php') ?>" style="font-size: .85rem;">Forgot password?</a>
                </div>
                <p style="text-align: center; margin-top: 12px; font-size: .85rem;">
                    New to FoodFusion? <button type="button" class="text-link-button" data-switch-auth="join">Create an account</button>.
                </p>
                <button class="button button-outline" type="button" data-close-auth style="width: 100%; margin-top: 6px;">Continue as visitor</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<script src="<?= url('assets/js/main.js?v=' . filemtime(__DIR__ . '/../assets/js/main.js')) ?>"></script>
</body>
</html>

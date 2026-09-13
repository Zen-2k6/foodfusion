<?php $user = current_user(); ?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand brand-light" href="<?= url('index.php') ?>">
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
                <a href="https://www.facebook.com/" target="_blank" rel="noopener">Facebook</a>
                <a href="https://www.instagram.com/" target="_blank" rel="noopener">Instagram</a>
                <a href="https://www.youtube.com/" target="_blank" rel="noopener">YouTube</a>
                <a href="https://www.tiktok.com/" target="_blank" rel="noopener">TikTok</a>
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
<div class="modal" id="join-modal" role="dialog" aria-modal="true" aria-labelledby="join-title" hidden>
    <div class="modal-panel">
        <button class="modal-close" type="button" data-close-join aria-label="Close sign-up form">&times;</button>
        <p class="eyebrow">Become part of the table</p>
        <h2 id="join-title">Join FoodFusion</h2>
        <p>Create a free account to share recipes, comment and save favourites.</p>

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
        </form>
    </div>
</div>
<?php endif; ?>

<script src="<?= url('assets/js/main.js?v=' . filemtime(__DIR__ . '/../assets/js/main.js')) ?>"></script>
</body>
</html>

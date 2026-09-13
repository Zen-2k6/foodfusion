<?php
require_once __DIR__ . '/config/app.php';
$pageTitle = 'Cookie information';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero compact-hero"><div class="container narrow"><p class="eyebrow">Information</p><h1>Cookie information</h1><p>A plain-language explanation of the cookies used in this demonstration.</p></div></section>
<section class="section"><div class="container narrow prose policy-copy">
    <h2>Session cookie</h2><p>PHP creates an essential session cookie so the website can keep a member logged in and protect submitted forms. It contains a random session identifier rather than the member's password.</p>
    <h2>Cookie preference</h2><p>When “Accept” is selected in the cookie notice, a small preference cookie called <code>foodfusion_cookie_choice</code> is stored for 30 days. Its only purpose is to stop the notice appearing on every page.</p>
    <h2>No advertising cookies</h2><p>This student demonstration does not use advertising, analytics or behavioural tracking cookies.</p>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>


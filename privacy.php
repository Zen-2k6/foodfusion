<?php
require_once __DIR__ . '/config/app.php';
$pageTitle = 'Privacy policy';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero compact-hero"><div class="container narrow"><p class="eyebrow">Information</p><h1>Privacy policy</h1><p>How this classroom demonstration handles submitted information.</p></div></section>
<section class="section"><div class="container narrow prose policy-copy">
    <h2>Information collected</h2><p>FoodFusion stores the name, email address and encrypted password entered during registration. It also stores recipes, comments, interactions and contact messages that a visitor chooses to submit.</p>
    <h2>How information is used</h2><p>The information is used only to demonstrate account management and community features to the project assessor. It is not used for advertising or shared with another organisation.</p>
    <h2>Security</h2><p>Passwords are processed using PHP's password hashing functions. Database operations use prepared statements, forms use CSRF tokens, and output is escaped before display.</p>
    <h2>Classroom demonstration</h2><p>This website runs locally in XAMPP and is not intended for public or production deployment. Demonstration data can be removed by deleting the local FoodFusion database.</p>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>


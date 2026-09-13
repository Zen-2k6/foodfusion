<?php
require_once __DIR__ . '/config/app.php';
$pageTitle = 'About us';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero about-hero">
    <div class="container">
        <p class="eyebrow light">Our story</p>
        <h1>Food should feel joyful,<br>not complicated.</h1>
        <p>We celebrate practical skills, cultural curiosity and the small moments that happen around a shared table.</p>
    </div>
</section>

<section class="section">
    <div class="container split-layout">
        <div>
            <p class="eyebrow">Our philosophy</p>
            <h2>Everyone has something worth bringing to the table.</h2>
        </div>
        <div class="prose">
            <p>FoodFusion began with a simple belief: home cooking becomes more rewarding when knowledge is shared openly. A beginner's question, a family technique and a clever kitchen shortcut can all help another cook grow in confidence.</p>
            <p>Our collection brings together approachable recipes from around the world. We encourage members to respect the cultures behind each dish, credit their inspiration and adapt recipes thoughtfully.</p>
        </div>
    </div>
</section>

<section class="section section-tint">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="eyebrow">What guides us</p>
                <h2>Our values</h2>
            </div>
        </div>
        <div class="value-grid">
            <article><span>01</span><h3>Curiosity</h3><p>We stay open to unfamiliar ingredients, techniques and food traditions.</p></article>
            <article><span>02</span><h3>Generosity</h3><p>We share useful knowledge clearly and welcome questions from every skill level.</p></article>
            <article><span>03</span><h3>Respect</h3><p>We value the people, cultures and personal stories connected to recipes.</p></article>
            <article><span>04</span><h3>Sustainability</h3><p>We support thoughtful choices that reduce waste in our kitchens and communities.</p></article>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-heading">
            <div><p class="eyebrow">The people behind the platform</p><h2>Meet our small team</h2></div>
        </div>
        <div class="team-grid">
            <article><div class="initial-avatar coral">AM</div><h3>Amina Malik</h3><p class="role">Community Lead</p><p>Amina keeps FoodFusion welcoming and helps members turn kitchen ideas into conversations.</p></article>
            <article><div class="initial-avatar gold">JL</div><h3>Jonah Lee</h3><p class="role">Recipe Editor</p><p>Jonah tests instructions for clarity and loves making global flavours approachable.</p></article>
            <article><div class="initial-avatar green">SK</div><h3>Sofia Khan</h3><p class="role">Learning Coordinator</p><p>Sofia creates practical resources that help new cooks build confidence one skill at a time.</p></article>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>


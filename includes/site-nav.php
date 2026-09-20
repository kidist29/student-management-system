<?php
/** Public site header/nav. Expects $activePublic to be one of: home|about|contact */
$activePublic = $activePublic ?? '';
?>
<header class="site-header">
    <div class="container">
        <a href="<?= BASE_URL ?>index.php" class="site-brand">
            <div class="mark"><?= h(INSTITUTION_INITIAL) ?></div>
            <span class="name"><?= h(SYSTEM_NAME) ?></span>
        </a>
        <nav class="site-nav" id="siteNav">
            <a href="<?= BASE_URL ?>index.php" class="<?= $activePublic === 'home' ? 'active' : '' ?>">Home</a>
            <a href="<?= BASE_URL ?>pages/about.php" class="<?= $activePublic === 'about' ? 'active' : '' ?>">About</a>
            <a href="<?= BASE_URL ?>pages/contact.php" class="<?= $activePublic === 'contact' ? 'active' : '' ?>">Contact</a>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary btn-sm">Admin Login</a>
            <?php require __DIR__ . '/theme-toggle.php'; ?>
        </nav>
        <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation"><i class="fa-solid fa-bars"></i></button>
    </div>
</header>

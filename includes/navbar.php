<?php
/**
 * Admin topbar. Expects $pageTitle to already be set.
 */
?>
<header class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title"><?= h($pageTitle ?? '') ?></div>
    </div>
    <div class="topbar-right">
        <?php require __DIR__ . '/theme-toggle.php'; ?>
        <div class="topbar-user">
            <img class="avatar-sm" src="<?= h(initialsAvatar($_SESSION['user_name'] ?? 'Admin', '#1b2a4a', '#f5e9cc')) ?>" alt="">
            <div class="info">
                <strong><?= h($_SESSION['user_name'] ?? 'Admin') ?></strong>
                <span><?= h(['super_admin' => 'Super Admin', 'admin' => 'Registrar', 'teacher' => 'Teacher', 'student' => 'Student'][currentRole()] ?? 'User') ?></span>
            </div>
        </div>
    </div>
</header>

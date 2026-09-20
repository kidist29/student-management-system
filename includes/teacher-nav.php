<?php
/** Teacher portal sidebar. Expects $activeNav to be one of: dashboard | courses */
$activeNav = $activeNav ?? '';
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="mark"><?= h(INSTITUTION_INITIAL) ?></div>
        <div>
            <div class="name"><?= h(SYSTEM_NAME) ?></div>
            <div class="sub">TEACHER PORTAL</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>teacher/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i>Dashboard</a>
        <a href="<?= BASE_URL ?>teacher/courses.php" class="<?= $activeNav === 'courses' ? 'active' : '' ?>"><i class="fa-solid fa-book"></i>My Courses</a>

        <div class="sidebar-section-label">Site</div>
        <a href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-globe"></i>View Public Site</a>
        <a href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a>
    </nav>
    <div class="sidebar-footer">&copy; <?= date('Y') ?> <?= h(INSTITUTION_NAME) ?></div>
</aside>

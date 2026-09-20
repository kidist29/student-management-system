<?php
/** Student portal sidebar. Expects $activeNav to be one of: dashboard | courses | grades | transcript */
$activeNav = $activeNav ?? '';
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="mark"><?= h(INSTITUTION_INITIAL) ?></div>
        <div>
            <div class="name"><?= h(SYSTEM_NAME) ?></div>
            <div class="sub">STUDENT PORTAL</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>student/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i>Dashboard</a>
        <a href="<?= BASE_URL ?>student/courses.php" class="<?= $activeNav === 'courses' ? 'active' : '' ?>"><i class="fa-solid fa-book"></i>My Courses</a>
        <a href="<?= BASE_URL ?>student/grades.php" class="<?= $activeNav === 'grades' ? 'active' : '' ?>"><i class="fa-solid fa-award"></i>My Grades</a>
        <a href="<?= BASE_URL ?>student/transcript.php" class="<?= $activeNav === 'transcript' ? 'active' : '' ?>"><i class="fa-solid fa-file-lines"></i>My Transcript</a>

        <div class="sidebar-section-label">Site</div>
        <a href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-globe"></i>View Public Site</a>
        <a href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a>
    </nav>
    <div class="sidebar-footer">&copy; <?= date('Y') ?> <?= h(INSTITUTION_NAME) ?></div>
</aside>

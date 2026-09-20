<?php
/**
 * Admin sidebar. Expects $activeNav to be set to one of:
 * dashboard | students | teachers | departments | courses
 */
$activeNav = $activeNav ?? '';
$nav = function (string $key, string $href, string $icon, string $label) use ($activeNav) {
    $cls = $activeNav === $key ? 'active' : '';
    echo '<a href="' . BASE_URL . h($href) . '" class="' . $cls . '"><i class="fa-solid ' . h($icon) . '"></i>' . h($label) . '</a>';
};
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="mark"><?= h(INSTITUTION_INITIAL) ?></div>
        <div>
            <div class="name"><?= h(SYSTEM_NAME) ?></div>
            <div class="sub">ADMIN PANEL</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Overview</div>
        <?php $nav('dashboard', 'admin/dashboard.php', 'fa-gauge-high', 'Dashboard'); ?>

        <div class="sidebar-section-label">Records</div>
        <?php $nav('students', 'admin/students/index.php', 'fa-user-graduate', 'Students'); ?>
        <?php $nav('teachers', 'admin/teachers/index.php', 'fa-chalkboard-user', 'Teachers'); ?>
        <?php $nav('departments', 'admin/departments/index.php', 'fa-building-columns', 'Departments'); ?>
        <?php $nav('courses', 'admin/courses/index.php', 'fa-book', 'Courses'); ?>

        <div class="sidebar-section-label">Academics</div>
        <?php $nav('enrollments', 'admin/enrollments/index.php', 'fa-clipboard-list', 'Enrollments'); ?>
        <?php $nav('grades', 'admin/grades/index.php', 'fa-award', 'Grades'); ?>
        <?php $nav('transcript', 'admin/transcript/index.php', 'fa-file-lines', 'Transcript'); ?>

        <?php if (hasRole('super_admin')): ?>
        <div class="sidebar-section-label">Administration</div>
        <?php $nav('users', 'admin/users/index.php', 'fa-user-shield', 'Users & Roles'); ?>
        <?php $nav('settings', 'admin/settings/index.php', 'fa-gear', 'Settings'); ?>
        <?php endif; ?>

        <div class="sidebar-section-label">Site</div>
        <?php $unreadMsgCount = (int) getDB()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn(); ?>
        <a href="<?= BASE_URL ?>admin/messages/index.php" class="<?= $activeNav === 'messages' ? 'active' : '' ?>">
            <i class="fa-solid fa-envelope"></i>Messages
            <?php if ($unreadMsgCount): ?><span class="badge badge-active" style="margin-left:auto;"><?= $unreadMsgCount ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-globe"></i>View Public Site</a>
        <a href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i>Log Out</a>
    </nav>
    <div class="sidebar-footer">&copy; <?= date('Y') ?> <?= h(INSTITUTION_NAME) ?></div>
</aside>

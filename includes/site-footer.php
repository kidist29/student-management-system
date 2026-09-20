<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="site-brand" style="margin-bottom:14px;">
                    <div class="mark"><?= h(INSTITUTION_INITIAL) ?></div>
                    <span class="name" style="color:#fff;"><?= h(SYSTEM_NAME) ?></span>
                </div>
                <p style="color:rgba(255,255,255,0.55);font-size:0.88rem;max-width:32ch;margin-bottom:16px;">
                    A single, secure record for every student, teacher, department, course, enrollment,
                    and academic record on campus.
                </p>
                <div class="footer-social">
                    <a href="#" title="Facebook (placeholder)" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" title="X / Twitter (placeholder)" aria-label="X (Twitter)"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" title="LinkedIn (placeholder)" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
            <div>
                <h4>Navigate</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>pages/about.php">About</a></li>
                    <li><a href="<?= BASE_URL ?>pages/contact.php">Contact</a></li>
                    <li><a href="<?= BASE_URL ?>auth/login.php">Admin Login</a></li>
                </ul>
            </div>
            <div>
                <h4>System</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>admin/enrollments/index.php">Enrollment</a></li>
                    <li><a href="<?= BASE_URL ?>admin/grades/index.php">Grades</a></li>
                    <li><a href="<?= BASE_URL ?>admin/transcript/index.php">Transcript</a></li>
                </ul>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    <li><?= h(INSTITUTION_EMAIL) ?></li>
                    <li><?= h(INSTITUTION_PHONE) ?></li>
                    <li><?= h(INSTITUTION_ADDRESS) ?></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= h(INSTITUTION_NAME) ?>. All rights reserved.</span>
            <span>Built with PHP, MySQL &amp; vanilla JavaScript</span>
        </div>
    </div>
</footer>

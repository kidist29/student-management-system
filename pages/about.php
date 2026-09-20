<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'About';
$activePublic = 'about';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/site-nav.php';
?>

<section class="section" id="main-content">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <?= publicImage('about_lecture_hall') ?>
            </div>
            <div>
                <div class="section-head" style="margin-bottom:20px;">
                    <div class="eyebrow">ABOUT THE SYSTEM</div>
                    <h2>Built for the way a registrar's office actually works</h2>
                </div>
                <p>
                    <?= h(SYSTEM_NAME) ?> is a web-based platform designed to simplify and organize
                    student, teacher, department, course, enrollment, and academic-record management.
                    It replaces scattered spreadsheets and paper files with one secure, searchable
                    record for everyone and everything on campus — giving the registrar's office an
                    efficient, centralized system for managing academic information from admission
                    through graduation.
                </p>
                <p>
                    Every entry — from a new student's admission date to a course's final grade —
                    lives in one MySQL database, protected behind a real login, so the whole office
                    works from the same up-to-date information instead of three different spreadsheets.
                </p>

                <h3 style="font-size:1rem;margin-bottom:12px;">Main Benefits</h3>
                <ul class="benefit-list">
                    <li><i class="fa-solid fa-circle-check"></i><span><strong>One source of truth</strong> — no more reconciling spreadsheets before a meeting.</span></li>
                    <li><i class="fa-solid fa-circle-check"></i><span><strong>Fast to search</strong> — find any student, teacher, or course by name, ID, or email in seconds.</span></li>
                    <li><i class="fa-solid fa-circle-check"></i><span><strong>Status at a glance</strong> — Active, Inactive, and Graduated students are always clearly labeled.</span></li>
                    <li><i class="fa-solid fa-circle-check"></i><span><strong>Secure by default</strong> — hashed passwords, protected admin pages, and validated file uploads.</span></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">WHO USES IT</div>
            <h2>Built for the registrar's office and academic leadership</h2>
        </div>
        <div class="feature-grid feature-grid-3">
                <h3>Registrar &amp; Admin Staff</h3>
                <p>The primary users — adding and updating student, teacher, and course records day to day.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-building-columns"></i></div>
                <h3>Department Heads</h3>
                <p>Review live enrollment, teacher, and course counts for their department without asking the office for a report.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-chart-line"></i></div>
                <h3>College Leadership</h3>
                <p>Get an accurate, real-time picture of enrollment and academic performance from the dashboard.</p>
            </div>
        </div>
        <p style="margin-top:20px;font-size:0.85rem;color:var(--ink-400);max-width:60ch;">
            The current version is an administrative system with a single secure login for office
            staff — it does not yet include separate self-service logins for teachers or students.
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">HOW IT HELPS</div>
            <h2>What changes for the registrar's office</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-clock"></i></div>
                <h3>Less time on paperwork</h3>
                <p>A record that used to mean updating three files now takes one form, in one place.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h3>Fewer data-entry errors</h3>
                <p>Server-side validation, duplicate checks, and dropdowns instead of free text cut down on mistakes.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <h3>A cleaner academic record</h3>
                <p>Enrollment, grades, and GPA are calculated consistently every time — never by hand, never inconsistent.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <h3>Answers in seconds</h3>
                <p>"Which students are in this course this semester?" is a search box away, not an afternoon of cross-referencing.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">KEY FEATURES</div>
            <h2>Everything connects — student to course to grade to transcript</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-user-graduate"></i></div>
                <h3>Students, Teachers &amp; Departments</h3>
                <p>Full profiles with photos, search, filters, and pagination across every list.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-book"></i></div>
                <h3>Courses</h3>
                <p>Course codes, credit hours, and semester, linked to the department and teacher responsible for them.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <h3>Enrollment</h3>
                <p>Register a student into a course for a term, with duplicate enrollment blocked automatically.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-file-lines"></i></div>
                <h3>Grades &amp; Transcripts</h3>
                <p>Record grades, calculate GPA automatically, and print an official, A4-formatted transcript.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">UNDER THE HOOD</div>
            <h2>Technologies used</h2>
        </div>
        <div class="tech-pill-row">
            <span class="tech-pill"><i class="fa-brands fa-html5"></i> HTML5</span>
            <span class="tech-pill"><i class="fa-brands fa-css3-alt"></i> CSS3</span>
            <span class="tech-pill"><i class="fa-brands fa-js"></i> Vanilla JavaScript</span>
            <span class="tech-pill"><i class="fa-brands fa-php"></i> PHP 8+</span>
            <span class="tech-pill"><i class="fa-solid fa-database"></i> MySQL</span>
            <span class="tech-pill"><i class="fa-solid fa-shield"></i> PDO Prepared Statements</span>
            <span class="tech-pill"><i class="fa-solid fa-chart-column"></i> Chart.js</span>
            <span class="tech-pill"><i class="fa-solid fa-icons"></i> Font Awesome</span>
        </div>
        <p style="margin-top:16px;font-size:0.85rem;color:var(--ink-400);">
            No frontend framework, no build step — it runs anywhere PHP and MySQL do, including a
            standard XAMPP install.
        </p>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>See it from the inside</h2>
                <p>Sign in with the admin account to explore the dashboard and every module.</p>
            </div>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-gold"><i class="fa-solid fa-right-to-bracket"></i> Admin Login</a>
        </div>
    </div>
</section>

<?php
require __DIR__ . '/../includes/site-footer.php';
require __DIR__ . '/../includes/footer.php';

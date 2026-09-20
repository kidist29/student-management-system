<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

// Real numbers, not marketing copy — pulled live so the public site never overstates itself.
// One round trip instead of four separate COUNT(*) queries.
$db = getDB();
$statsRow = $db->query("
    SELECT
        (SELECT COUNT(*) FROM students) AS students,
        (SELECT COUNT(*) FROM teachers) AS teachers,
        (SELECT COUNT(*) FROM departments) AS departments,
        (SELECT COUNT(*) FROM courses WHERE status = 'Active') AS courses
")->fetch();
$publicStats = array_map('intval', $statsRow);

$pageTitle = 'Home';
$activePublic = 'home';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/site-nav.php';
?>

<section class="hero" id="main-content">
    <div class="container">
        <div>
            <div class="hero-eyebrow">RIVERSIDE COLLEGE · REGISTRAR'S OFFICE</div>
            <h1>One record for every student, from admission to graduation.</h1>
            <p class="lead">
                <?= h(SYSTEM_NAME) ?> keeps enrollment, teaching staff, departments
                and courses in one secure, up-to-date place — so your office spends less time on
                paperwork and more time on students.
            </p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary">
                    <i class="fa-solid fa-right-to-bracket"></i> Admin Login
                </a>
                <a href="<?= BASE_URL ?>pages/about.php" class="btn btn-outline">Learn More</a>
            </div>
        </div>
        <div class="hero-image-wrap">
            <div class="hero-image">
                <?= publicImage('home_hero') ?>
            </div>
            <div class="floating-badge">
                <div class="fb-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="fb-title"><?= number_format($publicStats['students']) ?> records</div>
                    <div class="fb-sub">kept accurate, in real time</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">WHAT IT DOES</div>
            <h2>Everything the registrar's office needs, nothing it doesn't</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-user-graduate"></i></div>
                <h3>Student Records</h3>
                <p>Full academic profiles — enrollment, program, status and contact details — searchable in seconds.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h3>Teaching Staff</h3>
                <p>Track every instructor's department, qualifications and course load in one directory.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-building-columns"></i></div>
                <h3>Departments</h3>
                <p>Organize the college into departments, each with a head, description and status.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-book"></i></div>
                <h3>Courses</h3>
                <p>Manage course codes, credit hours and semester assignments alongside teaching staff.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">ACADEMIC MANAGEMENT</div>
            <h2>From enrollment to an official transcript, connected end to end</h2>
        </div>
        <div class="feature-grid feature-grid-3">
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <h3>Enrollment</h3>
                <p>Register a student into a course for a given term — duplicate enrollment is blocked automatically.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-award"></i></div>
                <h3>Grades &amp; GPA</h3>
                <p>Record a letter grade and the GPA is calculated instantly, using one consistent grading scale.</p>
            </div>
            <div class="feature-card">
                <div class="icon"><i class="fa-solid fa-file-lines"></i></div>
                <h3>Official Transcript</h3>
                <p>A clean, printable, A4-formatted transcript with per-semester and cumulative GPA — one click away.</p>
            </div>
        </div>
    </div>
</section>

<section class="stats-strip">
    <div class="container">
        <div class="grid grid-4">
            <div><div class="num"><?= number_format($publicStats['students']) ?></div><div class="lbl">STUDENTS ENROLLED</div></div>
            <div><div class="num"><?= number_format($publicStats['teachers']) ?></div><div class="lbl">TEACHING STAFF</div></div>
            <div><div class="num"><?= number_format($publicStats['departments']) ?></div><div class="lbl">DEPARTMENTS</div></div>
            <div><div class="num"><?= number_format($publicStats['courses']) ?></div><div class="lbl">ACTIVE COURSES</div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">CAMPUS LIFE</div>
            <h2>A look around <?= h(INSTITUTION_NAME) ?></h2>
        </div>
        <div class="carousel" data-carousel data-autoplay="4500">
            <div class="carousel-track">
                <div class="carousel-slide">
                    <?= publicImage('carousel_lecture_hall') ?>
                    <div class="carousel-caption"><h3>Lecture Halls</h3><p>Modern classrooms across every department.</p></div>
                </div>
                <div class="carousel-slide">
                    <?= publicImage('carousel_campus_grounds') ?>
                    <div class="carousel-caption"><h3>Campus Grounds</h3><p>A walkable campus connecting every faculty.</p></div>
                </div>
                <div class="carousel-slide">
                    <?= publicImage('carousel_library') ?>
                    <div class="carousel-caption"><h3>Library &amp; Study Spaces</h3><p>Quiet corners and group study rooms for every year.</p></div>
                </div>
                <div class="carousel-slide">
                    <?= publicImage('carousel_graduation') ?>
                    <div class="carousel-caption"><h3>Graduation Day</h3><p>Every record in this system leads here.</p></div>
                </div>
            </div>
            <button type="button" class="carousel-btn prev" aria-label="Previous slide"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" class="carousel-btn next" aria-label="Next slide"><i class="fa-solid fa-chevron-right"></i></button>
            <div class="carousel-dots"></div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">A GLIMPSE</div>
            <h2>Around the departments</h2>
        </div>
        <div class="mosaic-grid">
            <div class="mosaic-item mosaic-main">
                <?= publicImage('mosaic_cs_lab') ?>
            </div>
            <div class="mosaic-item">
                <?= publicImage('mosaic_engineering_lab') ?>
            </div>
            <div class="mosaic-item">
                <?= publicImage('mosaic_business_seminar') ?>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">HOW IT WORKS</div>
            <h2>From login to a finished record in three steps</h2>
        </div>
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-num">1</div>
                <h3>Sign in securely</h3>
                <p>The admin office logs in with a protected account — every page beyond login checks that session first.</p>
            </div>
            <div class="step-card">
                <div class="step-num">2</div>
                <h3>Find or add a record</h3>
                <p>Search any student, teacher, department or course by name or ID, or add a new one with a guided form.</p>
            </div>
            <div class="step-card">
                <div class="step-num">3</div>
                <h3>It's saved, instantly</h3>
                <p>Changes write straight to the database and show up across the dashboard right away — no syncing, no delays.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="quote-block">
            <blockquote>
                &ldquo;Before this, admission season meant three spreadsheets and a lot of hoping they
                matched. Now everyone in the office is looking at the same record.&rdquo;
            </blockquote>
            <div class="quote-author">
                <?= publicImage('testimonial_registrar') ?>
                <div>
                    <div class="name">Office of the Registrar</div>
                    <div class="role"><?= h(INSTITUTION_NAME) ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>Ready to see it in action?</h2>
                <p>Sign in to the admin dashboard to manage students, teachers, departments and courses.</p>
            </div>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-gold">
                <i class="fa-solid fa-right-to-bracket"></i> Go to Admin Login
            </a>
        </div>
    </div>
</section>

<?php
require __DIR__ . '/includes/site-footer.php';
require __DIR__ . '/includes/footer.php';

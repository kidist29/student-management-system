<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('super_admin', 'admin');

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
$db = getDB();

// One round trip instead of six separate COUNT(*) queries.
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM students) AS total_students,
        (SELECT COUNT(*) FROM teachers) AS total_teachers,
        (SELECT COUNT(*) FROM departments) AS total_departments,
        (SELECT COUNT(*) FROM courses) AS total_courses,
        (SELECT COUNT(*) FROM enrollments) AS total_enrollments,
        (SELECT COUNT(*) FROM students WHERE status = 'Active') AS active_students
")->fetch();

$totalStudents    = (int) $stats['total_students'];
$totalTeachers    = (int) $stats['total_teachers'];
$totalDepartments = (int) $stats['total_departments'];
$totalCourses     = (int) $stats['total_courses'];
$totalEnrollments = (int) $stats['total_enrollments'];
$activeStudents   = (int) $stats['active_students'];

// University-wide GPA across every recorded grade.
$gpaRows = $db->query('
    SELECT c.credit_hours, g.grade_point
    FROM grades g
    JOIN enrollments e ON e.id = g.enrollment_id
    JOIN courses c ON c.id = e.course_id
')->fetchAll();
$universityGpa = calculateGpa($gpaRows);

// Enrollment by admission year
$enrollmentRows = $db->query("
    SELECT YEAR(admission_date) AS yr, COUNT(*) AS total
    FROM students
    WHERE admission_date IS NOT NULL
    GROUP BY YEAR(admission_date)
    ORDER BY yr ASC
")->fetchAll();
$enrollLabels = array_map(fn($r) => (string) $r['yr'], $enrollmentRows);
$enrollValues = array_map(fn($r) => (int) $r['total'], $enrollmentRows);

// Gender distribution
$genderRows = $db->query("SELECT gender, COUNT(*) AS total FROM students GROUP BY gender")->fetchAll();
$genderLabels = array_map(fn($r) => $r['gender'], $genderRows);
$genderValues = array_map(fn($r) => (int) $r['total'], $genderRows);

// Course enrollments by semester (registrations overview)
$termRows = $db->query("
    SELECT semester, COUNT(*) AS total FROM enrollments GROUP BY semester
    ORDER BY FIELD(semester, '1st Semester', '2nd Semester', 'Summer')
")->fetchAll();
$termLabels = array_map(fn($r) => $r['semester'], $termRows);
$termValues = array_map(fn($r) => (int) $r['total'], $termRows);

// Department summary
$departmentSummary = $db->query('
    SELECT d.department_name,
        (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) AS student_count,
        (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.id) AS teacher_count,
        (SELECT COUNT(*) FROM courses c WHERE c.department_id = d.id) AS course_count
    FROM departments d
    ORDER BY student_count DESC
    LIMIT 6
')->fetchAll();

// Recent students
$recentStudents = $db->query("
    SELECT s.id, s.student_id, s.first_name, s.last_name, s.photo, s.status, s.created_at, d.department_name
    FROM students s
    LEFT JOIN departments d ON d.id = s.department_id
    ORDER BY s.created_at DESC, s.id DESC
    LIMIT 5
")->fetchAll();

// Recent activity feed (merged across tables, including new registrations)
$recentActivity = $db->query("
    (SELECT 'student' AS type, CONCAT(first_name, ' ', last_name) AS name, created_at FROM students)
    UNION ALL
    (SELECT 'teacher' AS type, CONCAT(first_name, ' ', last_name) AS name, created_at FROM teachers)
    UNION ALL
    (SELECT 'course' AS type, course_name AS name, created_at FROM courses)
    UNION ALL
    (SELECT 'department' AS type, department_name AS name, created_at FROM departments)
    UNION ALL
    (SELECT 'enrollment' AS type,
        CONCAT(s.first_name, ' ', s.last_name, ' enrolled in ', c.course_code) AS name, e.created_at
     FROM enrollments e
     JOIN students s ON s.id = e.student_id
     JOIN courses c ON c.id = e.course_id)
    ORDER BY created_at DESC
    LIMIT 8
")->fetchAll();

$activityMeta = [
    'student'    => ['icon' => 'fa-user-graduate', 'cls' => 'navy',  'verb' => 'Student enrolled'],
    'teacher'    => ['icon' => 'fa-chalkboard-user', 'cls' => 'gold', 'verb' => 'Teacher added'],
    'course'     => ['icon' => 'fa-book',           'cls' => 'green', 'verb' => 'Course created'],
    'department' => ['icon' => 'fa-building-columns','cls' => 'blue', 'verb' => 'Department added'],
    'enrollment' => ['icon' => 'fa-clipboard-list',  'cls' => 'gold', 'verb' => 'Course registration'],
];

require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <h1>Welcome back, <?= h(explode(' ', $_SESSION['user_name'] ?? 'Admin')[0]) ?></h1>
                </div>
                <a href="<?= BASE_URL ?>admin/students/create.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Student
                </a>
            </div>

            <div class="grid grid-4" style="margin-bottom:18px;">
                <div class="stat-card">
                    <div class="stat-icon navy"><i class="fa-solid fa-user-graduate"></i></div>
                    <div><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">TOTAL STUDENTS</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <div><div class="stat-value"><?= $totalTeachers ?></div><div class="stat-label">TOTAL TEACHERS</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-building-columns"></i></div>
                    <div><div class="stat-value"><?= $totalDepartments ?></div><div class="stat-label">DEPARTMENTS</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-book"></i></div>
                    <div><div class="stat-value"><?= $totalCourses ?></div><div class="stat-label">COURSES</div></div>
                </div>
            </div>

            <div class="grid grid-3" style="margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div><div class="stat-value"><?= $totalEnrollments ?></div><div class="stat-label">TOTAL ENROLLMENTS</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                    <div><div class="stat-value"><?= $activeStudents ?></div><div class="stat-label">ACTIVE STUDENTS</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon navy"><i class="fa-solid fa-award"></i></div>
                    <div><div class="stat-value"><?= number_format($universityGpa['gpa'], 2) ?></div><div class="stat-label">UNIVERSITY-WIDE GPA</div></div>
                </div>
            </div>

            <div class="card" style="margin-bottom:24px;">
                <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="<?= BASE_URL ?>admin/students/create.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-user-plus"></i> Add Student</a>
                    <a href="<?= BASE_URL ?>admin/teachers/create.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-chalkboard-user"></i> Add Teacher</a>
                    <a href="<?= BASE_URL ?>admin/courses/create.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-book"></i> Add Course</a>
                    <a href="<?= BASE_URL ?>admin/enrollments/create.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-clipboard-list"></i> Enroll Student</a>
                    <a href="<?= BASE_URL ?>admin/grades/create.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-award"></i> Record Grade</a>
                    <a href="<?= BASE_URL ?>admin/transcript/index.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-file-lines"></i> View Transcript</a>
                </div>
            </div>

            <div class="grid grid-2" style="margin-bottom:24px;">
                <div class="card">
                    <div class="card-header"><h3>Student Enrollment by Admission Year</h3></div>
                    <div class="card-body">
                        <?php if ($enrollLabels): ?>
                            <canvas id="enrollmentChart" height="140"></canvas>
                        <?php else: ?>
                            <p style="margin:0;">No admission-date data yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3>Gender Distribution</h3></div>
                    <div class="card-body">
                        <?php if ($genderLabels): ?>
                            <canvas id="genderChart" height="140"></canvas>
                        <?php else: ?>
                            <p style="margin:0;">No student data yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-2" style="margin-bottom:24px;">
                <div class="card">
                    <div class="card-header"><h3>Course Registrations by Semester</h3></div>
                    <div class="card-body">
                        <?php if ($termLabels): ?>
                            <canvas id="termChart" height="140"></canvas>
                        <?php else: ?>
                            <p style="margin:0;">No enrollments recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3>Department Summary</h3></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Department</th><th>Students</th><th>Teachers</th><th>Courses</th></tr></thead>
                            <tbody>
                            <?php if (!$departmentSummary): ?>
                                <tr><td colspan="4" style="text-align:center;color:var(--ink-400);">No departments yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($departmentSummary as $d): ?>
                                <tr>
                                    <td><?= h($d['department_name']) ?></td>
                                    <td><?= (int) $d['student_count'] ?></td>
                                    <td><?= (int) $d['teacher_count'] ?></td>
                                    <td><?= (int) $d['course_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Students</h3>
                        <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-ghost btn-sm">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Student</th><th>Department</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                            <?php if (!$recentStudents): ?>
                                <tr><td colspan="3">No students yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($recentStudents as $s): $fullName = $s['first_name'] . ' ' . $s['last_name']; ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>admin/students/view.php?id=<?= (int)$s['id'] ?>" style="display:flex;align-items:center;gap:10px;color:inherit;">
                                            <img class="avatar-sm" src="<?= h(photoUrl($s['photo'], $fullName)) ?>" alt="">
                                            <div>
                                                <div style="font-weight:600;"><?= h($fullName) ?></div>
                                                <div style="font-size:0.78rem;color:var(--ink-400);"><?= h($s['student_id']) ?></div>
                                            </div>
                                        </a>
                                    </td>
                                    <td><?= h($s['department_name'] ?? '—') ?></td>
                                    <td><?= statusBadge($s['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Recent Activity</h3></div>
                    <div class="card-body" style="display:grid;gap:16px;">
                        <?php if (!$recentActivity): ?>
                            <p style="margin:0;">No activity yet.</p>
                        <?php endif; ?>
                        <?php foreach ($recentActivity as $a): $meta = $activityMeta[$a['type']]; ?>
                            <div style="display:flex;gap:12px;align-items:flex-start;">
                                <div class="stat-icon <?= $meta['cls'] ?>" style="width:38px;height:38px;font-size:0.95rem;flex-shrink:0;">
                                    <i class="fa-solid <?= $meta['icon'] ?>"></i>
                                </div>
                                <div>
                                    <div style="font-size:0.9rem;"><strong><?= h($meta['verb']) ?>:</strong> <?= h($a['name']) ?></div>
                                    <div style="font-size:0.78rem;color:var(--ink-400);"><?= h(date('M j, Y g:i A', strtotime($a['created_at']))) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($enrollLabels || $genderLabels || $termLabels): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const navy = '#1b2a4a', gold = '#c89b3c', blue = '#33507f', ink = '#565f70', line = '#e4e1d8';
    const tooltipStyle = {
        backgroundColor: '#16233f',
        titleColor: '#f5e9cc',
        bodyColor: '#ffffff',
        borderColor: '#c89b3c',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 8,
        titleFont: { family: 'Inter', weight: '600', size: 12 },
        bodyFont: { family: 'Inter', size: 12 },
        displayColors: false
    };
    Chart.defaults.animation.duration = 500;
    Chart.defaults.animation.easing = 'easeOutQuart';

    <?php if ($enrollLabels): ?>
    new Chart(document.getElementById('enrollmentChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($enrollLabels) ?>,
            datasets: [{
                label: 'Students admitted',
                data: <?= json_encode($enrollValues) ?>,
                backgroundColor: navy,
                borderRadius: 6,
                maxBarThickness: 42
            }]
        },
        options: {
            plugins: { legend: { display: false }, tooltip: tooltipStyle },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, color: ink }, grid: { color: line } },
                x: { ticks: { color: ink }, grid: { display: false } }
            }
        }
    });
    <?php endif; ?>

    <?php if ($genderLabels): ?>
    new Chart(document.getElementById('genderChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($genderLabels) ?>,
            datasets: [{
                data: <?= json_encode($genderValues) ?>,
                backgroundColor: [navy, gold, blue],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom', labels: { color: ink, boxWidth: 12, padding: 16 } }, tooltip: tooltipStyle },
            cutout: '62%'
        }
    });
    <?php endif; ?>

    <?php if ($termLabels): ?>
    new Chart(document.getElementById('termChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($termLabels) ?>,
            datasets: [{
                label: 'Registrations',
                data: <?= json_encode($termValues) ?>,
                backgroundColor: gold,
                borderRadius: 6,
                maxBarThickness: 52
            }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false }, tooltip: tooltipStyle },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0, color: ink }, grid: { color: line } },
                y: { ticks: { color: ink }, grid: { display: false } }
            }
        }
    });
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>

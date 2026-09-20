<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (isLoggedIn()) {
    $homeUrl = roleHomeUrl(currentRole());
    if ($homeUrl !== 'auth/login.php') {
        redirect($homeUrl);
    }
    // Logged in (a user_id is set) but the role doesn't resolve to any
    // known dashboard — a stale/corrupted session (e.g. one created before
    // roles existed). Redirecting to roleHomeUrl() here would just send us
    // back to this same page and loop forever (ERR_TOO_MANY_REDIRECTS).
    // Clear it and show a fresh login form instead.
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}

$errors = [];
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $emailValue = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($emailValue === '' || $password === '') {
        $errors[] = 'Please enter both your email and password.';
    }

    if (!$errors) {
        // Simple in-memory throttle per session to slow down brute force.
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
        if ($_SESSION['login_attempts'] >= 10) {
            $errors[] = 'Too many attempts. Please wait a moment and try again.';
        }
    }

    if (!$errors) {
        $stmt = getDB()->prepare('SELECT id, full_name, email, password, role, teacher_id, student_id, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$emailValue]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['login_attempts']++;
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'Active') {
            $errors[] = 'This account has been deactivated. Contact the system owner.';
        } elseif ($user['role'] === 'teacher' && !$user['teacher_id']) {
            $errors[] = 'This teacher account is not linked to a teacher record. Contact a Super Admin.';
        } elseif ($user['role'] === 'student' && !$user['student_id']) {
            $errors[] = 'This student account is not linked to a student record. Contact a Super Admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id']         = $user['id'];
            $_SESSION['user_name']       = $user['full_name'];
            $_SESSION['user_email']      = $user['email'];
            $_SESSION['user_role']       = $user['role'];
            $_SESSION['user_teacher_id'] = $user['teacher_id'];
            $_SESSION['user_student_id'] = $user['student_id'];
            unset($_SESSION['login_attempts']);

            $upd = getDB()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $upd->execute([$user['id']]);

            setFlash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect(roleHomeUrl($user['role']));
        }
    }
}

$pageTitle = 'Sign In';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-shell">
    <div class="auth-visual">
        <a href="<?= BASE_URL ?>index.php" style="display:flex;align-items:center;gap:10px;">
            <div class="mark" style="width:40px;height:40px;background:#c89b3c;color:#0f1a30;border-radius:9px;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:700;"><?= h(INSTITUTION_INITIAL) ?></div>
            <span style="font-family:'Fraunces',serif;font-size:1.2rem;font-weight:600;"><?= h(SYSTEM_NAME) ?></span>
        </a>
        <blockquote>
            &ldquo;Good record-keeping is the quiet backbone of a good college.&rdquo;
            <div class="quote-by">— Office of the Registrar</div>
        </blockquote>
    </div>
    <div class="auth-form-wrap">
        <div class="auth-card">
            <h1>Sign in</h1>
            <p class="lead">One login for every role — admin, teacher, and student.</p>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span><?= h($error) ?></span></div>
            <?php endforeach; ?>

            <form method="post" action="" data-validate novalidate>
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="email">Email address <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= h($emailValue) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="req">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </button>
            </form>

            <div class="demo-hint">
                <strong>Demo accounts</strong><br>
                Super Admin: admin@sms.edu / Admin@123<br>
                Registrar: registrar@sms.edu / Registrar@123<br>
                Teacher: alemayehu.tesfaye@sms.edu / Teacher@123<br>
                Student: kidist.bekele@student.sms.edu / Student@123
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

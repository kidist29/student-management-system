<?php
/**
 * Session bootstrap, authentication guard, CSRF helpers, and small
 * utility functions shared by every protected admin page.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Reject any session ID the client supplies that the server never
    // generated itself — the standard defense against session fixation.
    ini_set('session.use_strict_mode', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? null) == 443;

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => $isHttps, // only marked Secure when actually served over HTTPS
    ]);

    // Regenerate the session ID periodically during a long-lived session
    // (not just at login) to shrink the window a hijacked session ID stays
    // useful, without logging the user out or losing their session data.
    if (empty($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 900) { // 15 minutes
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}

require_once __DIR__ . '/../config/database.php';

/** Extra context appended to every logged error, to make debugging from the log alone realistic. */
function errorLogContext(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? 'unknown URI';
    $user = isset($_SESSION['user_id']) ? 'user #' . $_SESSION['user_id'] . ' (' . ($_SESSION['user_role'] ?? '?') . ')' : 'guest';
    return " | $uri | $user";
}

set_exception_handler(function (Throwable $e): void {
    error_log('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . errorLogContext());
    renderErrorPage(500, 'Something went wrong', 'Please try again in a moment. If this keeps happening, let the system administrator know.');
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    // Respect @-suppressed errors and error_reporting() exclusions.
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log("PHP error [$severity]: $message in $file:$line" . errorLogContext());
    // Only fatal-class severities should stop the page; warnings/notices just get logged.
    if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        renderErrorPage(500, 'Something went wrong', 'Please try again in a moment. If this keeps happening, let the system administrator know.');
    }
    return true;
});

/** Redirect helper that always uses an absolute, base-url-aware path. */
function redirect(string $path): void
{
    $path = ltrim($path, '/');
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** True when an admin is currently logged in. */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Stop the request unless the user is authenticated with a recognized role.
 * A session with a user_id but no valid role is treated as stale (e.g. one
 * created before roles existed, or otherwise corrupted) — it's cleared
 * here rather than just bounced to login, which is what previously caused
 * an infinite redirect loop for anyone holding such a session.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('auth/login.php');
    }
    if (!in_array($_SESSION['user_role'] ?? null, ['super_admin', 'admin', 'teacher', 'student'], true)) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        session_start();
        redirect('auth/login.php');
    }
}

/** Escape a string for safe HTML output (basic XSS protection). */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Generate (or reuse) a CSRF token for the current session. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Render a hidden CSRF input field. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

/** Validate a submitted CSRF token; stops the request on mismatch. */
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Your session has expired. Please go back and try again.');
    }
}

/** Store a one-time flash message. */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear all pending flash messages. */
function getFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

require_once __DIR__ . '/permissions.php';

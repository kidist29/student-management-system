<?php
/**
 * Database connection (PDO + MySQL)
 * Adjust these values if your XAMPP MySQL credentials differ.
 */

// Never let a raw PHP error/warning/notice print to the browser, no matter
// what php.ini on the server defaults to — everything is logged instead
// (see the PHP error log path in your php.ini, typically
// C:\xampp\php\logs\php_error_log on Windows). The custom exception/error
// handler in includes/auth_check.php is the primary defense for anything
// that runs after it loads; these two lines cover the gap before that,
// and any error the custom handler doesn't intercept.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../includes/error_page.php';

define('DB_HOST', 'localhost');
define('DB_NAME', 'student_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Base URL of the app, used for redirects and asset links.
// If you deploy to a subfolder (e.g. http://localhost/student-management-system/)
// this is detected automatically, regardless of how deep the current page lives
// (works the same for index.php, auth/login.php, and admin/students/index.php).
define('BASE_URL', (function () {
    $projectRoot = str_replace('\\', '/', dirname(__DIR__)); // filesystem path of the project root
    $docRoot     = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    $relative = $docRoot !== '' && str_starts_with($projectRoot, $docRoot)
        ? substr($projectRoot, strlen($docRoot))
        : '';

    $relative = trim($relative, '/');
    return $relative === '' ? '/' : '/' . $relative . '/';
})());

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Never leak raw DB errors to the browser.
            error_log('Database connection failed: ' . $e->getMessage());
            renderErrorPage(500, 'We\'ll be right back', 'The system could not connect to its database. Please try again in a moment — if this keeps happening, let the system administrator know.');
        }
    }

    return $pdo;
}

/**
 * Institution branding, sourced from the `settings` table (editable by a
 * Super Admin on Settings page) with these as hard fallback defaults —
 * used if the table is missing (e.g. an older database not yet upgraded)
 * or unreachable. Defined as constants for convenience, but the actual
 * source of truth is the database, not this file.
 */
(function () {
    $defaults = [
        'institution_name'    => 'Riverside College',
        'institution_address' => 'Riverside Campus, Bole Road, Addis Ababa, Ethiopia',
        'institution_email'   => 'registrar@riverside.edu',
        'institution_phone'   => '+251 11 234 5678',
        'institution_hours'   => 'Monday – Friday, 8:30 AM – 5:00 PM',
    ];
    $values = $defaults;
    try {
        $rows = getDB()->query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($rows as $key => $value) {
            if (array_key_exists($key, $values)) {
                $values[$key] = $value;
            }
        }
    } catch (Throwable $e) {
        // settings table not present yet (older database) — silently use the defaults above.
    }

    define('INSTITUTION_NAME', $values['institution_name']);
    define('SYSTEM_NAME', INSTITUTION_NAME . ' SMS');
    define('INSTITUTION_ADDRESS', $values['institution_address']);
    define('INSTITUTION_INITIAL', mb_substr(INSTITUTION_NAME, 0, 1));
    define('INSTITUTION_EMAIL', $values['institution_email']);
    define('INSTITUTION_PHONE', $values['institution_phone']);
    define('INSTITUTION_HOURS', $values['institution_hours']);
})();

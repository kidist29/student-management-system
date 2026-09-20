<?php
require_once __DIR__ . '/includes/error_page.php';
// A bare require here (not the full auth_check.php) is intentional: a 404
// can be reached by anyone, unauthenticated, for a URL that doesn't exist,
// so this page must not depend on a session or a database row existing.
require_once __DIR__ . '/config/database.php';

renderErrorPage(
    404,
    'Page not found',
    "The page you're looking for doesn't exist, may have been moved, or the link may be out of date. Double-check the address, or head back to the home page."
);

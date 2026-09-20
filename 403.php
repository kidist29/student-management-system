<?php
require_once __DIR__ . '/includes/error_page.php';
require_once __DIR__ . '/config/database.php';

renderErrorPage(
    403,
    'Access denied',
    "You don't have permission to view that page or resource. If you believe this is a mistake, sign in with an account that has access, or contact a Super Admin."
);

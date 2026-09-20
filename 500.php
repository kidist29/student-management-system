<?php
// Deliberately minimal: a 500 can be reached in exactly the situation where
// nothing else about the app is trustworthy (e.g. the database is down),
// so this file loads as little as possible and never calls getDB().
require_once __DIR__ . '/includes/error_page.php';

renderErrorPage(
    500,
    'Something went wrong',
    "We hit an unexpected error on our end. Please try again in a moment — if this keeps happening, let the system administrator know."
);

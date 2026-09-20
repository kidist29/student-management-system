<?php
/**
 * Renders a styled, on-brand error page and stops execution.
 *
 * Deliberately has almost no dependencies — no database call, no session
 * requirement, and it tolerates SYSTEM_NAME/BASE_URL not being defined yet
 * (which matters because this same function is the one shown when the
 * database itself is unreachable, i.e. before config/database.php has
 * necessarily finished setting those up).
 */
function renderErrorPage(int $httpCode, string $title, string $message): void
{
    if (!headers_sent()) {
        http_response_code($httpCode);
    }

    $systemName = defined('SYSTEM_NAME') ? SYSTEM_NAME : 'Student Management System';
    $homeUrl = defined('BASE_URL') ? BASE_URL : '/';

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . htmlspecialchars($title) . ' &middot; ' . htmlspecialchars($systemName) . '</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">'
        . '<style>
            *{box-sizing:border-box;}
            body{font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f7f6f2;color:#1f2430;
                 display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:24px;}
            .box{max-width:440px;text-align:center;background:#fff;border:1px solid #e4e1d8;border-radius:16px;
                 padding:44px 36px;box-shadow:0 8px 24px -12px rgba(15,26,48,0.18);}
            .code{font-family:Fraunces,Georgia,serif;font-size:2.6rem;font-weight:700;color:#c89b3c;margin:0 0 6px;}
            h1{font-family:Fraunces,Georgia,serif;color:#1b2a4a;font-size:1.35rem;margin:0 0 12px;}
            p{color:#565f70;font-size:0.95rem;line-height:1.55;margin:0 0 24px;}
            a.btn{display:inline-block;background:#1b2a4a;color:#fff;text-decoration:none;font-weight:600;
                  font-size:0.9rem;padding:12px 22px;border-radius:6px;}
            a.btn:hover{background:#253a63;}
          </style></head><body>'
        . '<div class="box">'
        . '<div class="code">' . (int) $httpCode . '</div>'
        . '<h1>' . htmlspecialchars($title) . '</h1>'
        . '<p>' . htmlspecialchars($message) . '</p>'
        . '<a class="btn" href="' . htmlspecialchars($homeUrl) . '">Return to the home page</a>'
        . '</div></body></html>';
    exit;
}

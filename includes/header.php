<?php
/**
 * Shared <head> + opening <body>.
 * Expects (optionally) these variables set before include:
 *   $pageTitle    - string, e.g. "Dashboard"
 *   $bodyClass    - extra class(es) on <body>
 *   $pageDescription - string for <meta name="description">, falls back to a default
 */
$pageTitle = $pageTitle ?? 'Student Management System';
$bodyClass = $bodyClass ?? '';
$pageDescription = $pageDescription ?? (SYSTEM_NAME . ' — a secure, database-driven platform for managing students, teachers, departments, courses, enrollment, and academic records.');

// A small inline SVG monogram used as the favicon — no binary asset to manage or break.
// The whole thing is percent-encoded (not just the dynamic initial) so no
// literal quote characters remain that could break out of the href="" below.
$faviconSvg = rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
    . '<rect width="64" height="64" rx="14" fill="#0f1a30"/>'
    . '<text x="32" y="44" font-family="Georgia,serif" font-size="34" font-weight="700" '
    . 'fill="#c89b3c" text-anchor="middle">' . INSTITUTION_INITIAL . '</text></svg>'
);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<script>
  // Applied before anything renders, to avoid a flash of the wrong theme.
  (function () {
    try {
      var saved = localStorage.getItem('theme');
      if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    } catch (e) { /* localStorage unavailable (e.g. private mode) — default to light */ }
  })();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= h($pageDescription) ?>">
<title><?= h($pageTitle) ?> · <?= h(SYSTEM_NAME) ?></title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= $faviconSvg ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="<?= h($bodyClass) ?>">
<a href="#main-content" class="skip-link">Skip to main content</a>
<div id="toast-stack"></div>

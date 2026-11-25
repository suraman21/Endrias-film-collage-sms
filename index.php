<?php
/**
 * Endrias Film College — public site · front controller (root entry).
 *
 * This file REPLACES the former FKSS public landing page. It serves the
 * Endrias Film College website (the React site ported to PHP) at the site
 * root. The FKSS management application under /admin/* is a SEPARATE app and
 * is NOT affected by this change.
 *
 * Routing   : ?p=programs|about|faculty|showcase|careers|admissions  (default = Home)
 * Language  : ?lang=am|en  (persisted in a cookie) — see college/partials/lang.php
 * Assets    : /college/assets/*        Content data: college/partials/data.php
 * Frontend  : college/partials/* (head, footer) + college/pages/* (one per route)
 */

require __DIR__ . '/college/partials/lang.php';

$route = isset($_GET['p']) ? (string) $_GET['p'] : '';
$known = ['programs', 'about', 'faculty', 'showcase', 'careers', 'admissions'];
if (!in_array($route, $known, true)) {
    $route = '';
}

$titles = [
    ''           => 'Endrias Film College',
    'programs'   => 'Programs',
    'about'      => 'About & Accreditation',
    'faculty'    => 'Faculty',
    'showcase'   => 'Endi Award',
    'careers'    => 'Careers',
    'admissions' => 'Admissions',
];

$EFC_PAGE  = $route;
$EFC_TITLE = $titles[$route];

$pageFile = __DIR__ . '/college/pages/' . ($route === '' ? 'home' : $route) . '.php';
if (!is_file($pageFile)) {
    $pageFile = __DIR__ . '/college/pages/_placeholder.php';
}

require __DIR__ . '/college/partials/head.php';
require $pageFile;
require __DIR__ . '/college/partials/footer.php';

<?php
// Local development only: `php -S localhost:8000 dev-router.php` (mimics .htaccess rewrites)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists(__DIR__ . $path) && !is_dir(__DIR__ . $path)) return false;
$routes = [
    '#^/projects/?$#' => 'projects.php', '#^/thank-you/?$#' => 'thank-you.php', '#^/contact/?$#' => 'contact.php',
    '#^/about/?$#' => 'about.php', '#^/privacy-policy/?$#' => 'privacy.php', '#^/sitemap\.xml$#' => 'sitemap.php',
];
if (preg_match('#^/project/([a-z0-9\-]+)/?$#', $path, $m)) { $_GET['slug'] = $m[1]; $f = 'project.php'; }
else foreach ($routes as $re => $file) if (preg_match($re, $path)) { $f = $file; break; }
if ($path === '/admin' || $path === '/admin/') $f = 'admin/index.php';
$f ??= ($path === '/' ? 'index.php' : '404.php');
$_SERVER['SCRIPT_NAME'] = '/' . $f;
chdir(dirname(__DIR__ . '/' . $f));
require __DIR__ . '/' . $f;

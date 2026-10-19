<?php
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$origin = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$urls = [url(''), url('projects'), url('about'), url('contact')];
foreach (get_projects() as $p) $urls[] = project_url($p);
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) echo '  <url><loc>' . e($origin . $u) . "</loc></url>\n";
echo '</urlset>';

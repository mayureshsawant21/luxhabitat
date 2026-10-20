<?php
/**
 * Renders one PHP page to stdout as static HTML (used by build-static.php).
 * Usage: php tools/render-page.php <base> <data-dir> <script.php> [query-string]
 */
[, $base, $dataDir, $script, $query] = array_pad($argv, 5, '');
define('STATIC_BUILD', true);
define('DATA_DIR', $dataDir);
$root = dirname(__DIR__);
$_SERVER['SCRIPT_NAME'] = rtrim($base, '/') . '/' . $script;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
parse_str($query, $_GET);
chdir($root);
ob_start();
include $root . '/' . $script;
echo ob_get_clean();

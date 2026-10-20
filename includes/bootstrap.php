<?php
/**
 * LuxHabitat Mumbai — bootstrap
 * Loaded by every public page and every admin page.
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
if (!defined('DATA_DIR')) define('DATA_DIR', ROOT . '/data');
// true only while tools/build-static.php renders the GitHub Pages version
if (!defined('STATIC_BUILD')) define('STATIC_BUILD', false);
define('UPLOAD_DIR', ROOT . '/uploads');
define('DEFAULT_ADMIN_PASSWORD', 'luxhabitat123');

// Set to false if your host does not support .htaccess rewrites (links become project.php?slug=...)
define('PRETTY_URLS', true);

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('lxh_sess');
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/amenities.php';
require_once __DIR__ . '/lead-form.php';

// Base path (supports installing in a sub-folder, e.g. example.com/site/)
$__script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$__base = rtrim(dirname($__script), '/');
if (preg_match('#/admin$#', $__base)) {
    $__base = substr($__base, 0, -6);
}
define('BASE', $__base);

$pdo = db();
$SETTINGS = load_settings();

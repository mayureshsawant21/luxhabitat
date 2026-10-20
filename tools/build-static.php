<?php
/**
 * Builds the static GitHub Pages version of the website into the repository root:
 *   index.html, projects/, project/<slug>/, about/, contact/, privacy-policy/, thank-you/, 404.html
 *
 * Usage: php tools/build-static.php [base-path]
 *   base-path defaults to /luxhabitat (https://<user>.github.io/luxhabitat/). Use "" for a custom domain.
 *
 * Content comes from the demo data in includes/db.php. Forms work through site-config.js.
 */
$root = dirname(__DIR__);
$base = $argv[1] ?? '/luxhabitat';
$data = sys_get_temp_dir() . '/lxh-static-' . getmypid();
@mkdir($data, 0755, true);

// Create a fresh demo database and attach the sample brochure to the first project
define('STATIC_BUILD', true);
define('DATA_DIR', $data);
$_SERVER['SCRIPT_NAME'] = $base . '/index.php';
require $root . '/includes/bootstrap.php';
db()->exec("UPDATE projects SET brochure = 'assets/demo/sample-brochure.pdf' WHERE id = 1");
$slugs = db()->query('SELECT slug FROM projects WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);

$pages = [
    'index.html'                  => ['index.php', ''],
    'projects/index.html'         => ['projects.php', ''],
    'about/index.html'            => ['about.php', ''],
    'contact/index.html'          => ['contact.php', ''],
    'privacy-policy/index.html'   => ['privacy.php', ''],
    'thank-you/index.html'        => ['thank-you.php', ''],
    '404.html'                    => ['404.php', ''],
];
foreach ($slugs as $s) $pages["project/$s/index.html"] = ['project.php', 'slug=' . $s];

// Remove previously generated project folders
foreach (glob($root . '/project/*/index.html') ?: [] as $old) { unlink($old); @rmdir(dirname($old)); }

foreach ($pages as $out => [$script, $query]) {
    $cmd = sprintf('%s %s %s %s %s %s', escapeshellarg(PHP_BINARY), escapeshellarg($root . '/tools/render-page.php'),
        escapeshellarg($base), escapeshellarg($data), escapeshellarg($script), escapeshellarg($query));
    $html = shell_exec($cmd);
    if (!$html || !str_contains($html, '</html>')) { fwrite(STDERR, "Failed: $out\n"); exit(1); }
    $dest = $root . '/' . $out;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    file_put_contents($dest, $html);
    echo "  wrote $out\n";
}
touch($root . '/.nojekyll');
array_map('unlink', glob($data . '/*') ?: []);
@rmdir($data);
echo "Static site built for base path '$base'.\n";

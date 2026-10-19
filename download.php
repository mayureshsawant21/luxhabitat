<?php
/** Serves a project's e-brochure — only to visitors who submitted the lead form for that project. */
require __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$q = db()->prepare('SELECT slug, name, brochure FROM projects WHERE id = ? AND is_active = 1');
$q->execute([$id]);
$p = $q->fetch();

if (!$p || !$p['brochure']) {
    http_response_code(404);
    exit('Brochure not found.');
}
if (empty($_SESSION['unlocked'][$id])) {
    redirect(url('project/' . $p['slug']) . '#enquire');
}

$file = realpath(ROOT . '/' . ltrim($p['brochure'], '/'));
$dir  = realpath(UPLOAD_DIR . '/brochures');
if (!$file || !$dir || !str_starts_with($file, $dir) || !is_file($file)) {
    http_response_code(404);
    exit('Brochure not found.');
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$filename = slugify($p['name']) . '-brochure.' . $ext;
header('Content-Type: ' . ($ext === 'pdf' ? 'application/pdf' : 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file));
header('X-Content-Type-Options: nosniff');
readfile($file);

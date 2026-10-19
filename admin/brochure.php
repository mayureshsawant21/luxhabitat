<?php
/** Lets a logged-in admin view a project's brochure (the brochures folder is not publicly accessible). */
require __DIR__ . '/inc/auth.php';
require_admin();
$q = db()->prepare('SELECT brochure FROM projects WHERE id = ?');
$q->execute([(int)($_GET['id'] ?? 0)]);
$file = realpath(ROOT . '/' . (string)$q->fetchColumn());
$dir = realpath(UPLOAD_DIR . '/brochures');
if (!$file || !$dir || !str_starts_with($file, $dir) || !is_file($file)) { http_response_code(404); exit('No brochure.'); }
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file));
readfile($file);

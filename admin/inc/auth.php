<?php
/** Admin bootstrap: every admin page requires this first. */
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/upload.php';

header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store');

function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            exit(json_encode(['error' => 'Session expired. Please log in again.']));
        }
        redirect(BASE . '/admin/login.php');
    }
    // 8-hour idle timeout
    if (time() - ($_SESSION['admin_seen'] ?? 0) > 8 * 3600) {
        unset($_SESSION['admin']);
        redirect(BASE . '/admin/login.php?expired=1');
    }
    $_SESSION['admin_seen'] = time();
}

function require_post_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        exit('The upload was larger than the server allows (post_max_size = ' . ini_get('post_max_size') . '). Go back and upload fewer or smaller files at once.');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) {
        http_response_code(400);
        exit('Invalid request (security token mismatch). Please go back, refresh the page and try again.');
    }
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['admin_flash'] = [$msg, $type];
}

function admin_url(string $page = ''): string
{
    return BASE . '/admin/' . $page;
}

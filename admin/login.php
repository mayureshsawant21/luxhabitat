<?php
require __DIR__ . '/inc/auth.php';

if (is_admin()) redirect(admin_url());

$error = '';
$_SESSION['login_fails'] = array_filter($_SESSION['login_fails'] ?? [], fn($t) => $t > time() - 900);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (count($_SESSION['login_fails']) >= 5) {
        $error = 'Too many attempts. Please wait 15 minutes and try again.';
    } elseif (!csrf_check()) {
        $error = 'Your session expired. Please try again.';
    } elseif (password_verify((string)($_POST['password'] ?? ''), setting('admin_password'))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['admin_seen'] = time();
        $_SESSION['login_fails'] = [];
        redirect(admin_url());
    } else {
        $_SESSION['login_fails'][] = time();
        usleep(600000);
        $error = 'Incorrect password.';
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin login · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= BASE ?>/assets/img/favicon.svg" type="image/svg+xml">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/admin/assets/admin.css?v=1.0">
</head>
<body class="login">
  <form class="login__box" method="post" autocomplete="off">
    <?= csrf_field() ?>
    <div class="login__brand"><span>LuxHabitat</span><small>Mumbai · Admin</small></div>
    <?php if (isset($_GET['expired'])): ?><div class="flash flash--warn">Session expired — please log in again.</div><?php endif; ?>
    <?php if ($error): ?><div class="flash flash--err"><?= e($error) ?></div><?php endif; ?>
    <label class="f">
      <span>Password</span>
      <input type="password" name="password" required autofocus>
    </label>
    <button class="b b--gold b--block" type="submit">Log in</button>
    <a class="login__back" href="<?= BASE ?>/">← Back to website</a>
  </form>
</body>
</html>

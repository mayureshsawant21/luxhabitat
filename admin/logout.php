<?php
require __DIR__ . '/inc/auth.php';
unset($_SESSION['admin'], $_SESSION['admin_seen']);
session_regenerate_id(true);
redirect(admin_url('login.php'));

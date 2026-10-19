<?php
/** Admin layout helpers */

function admin_header(string $title, string $active = ''): void
{
    $newLeads = (int)db()->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn();
    $nav = [
        'index.php'     => ['Dashboard', '<path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/>'],
        'projects.php'  => ['Projects', '<path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>'],
        'leads.php'     => ['Leads', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>'],
        'banners.php'   => ['Home Banners', '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/>'],
        'amenities.php' => ['Amenities', '<path d="M12 3l2.4 5.6L20 9.3l-4.3 3.8L17 19l-5-3-5 3 1.3-5.9L4 9.3l5.6-.7z"/>'],
        'settings.php'  => ['Settings', '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>'],
    ];
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Admin · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="<?= BASE ?>/assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/admin/assets/admin.css?v=1.0">
<script>window.ADMIN = { base: <?= json_encode(BASE) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
</head>
<body>
<div class="app">
  <aside class="side" id="side">
    <a class="side__brand" href="<?= admin_url() ?>"><span>LuxHabitat</span><small>Admin</small></a>
    <nav>
      <?php foreach ($nav as $file => [$label, $icon]): ?>
        <a href="<?= admin_url($file) ?>" class="<?= $active === $file ? 'is-active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><?= $icon ?></svg>
          <?= e($label) ?>
          <?php if ($file === 'leads.php' && $newLeads): ?><em><?= $newLeads ?></em><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side__foot">
      <a href="<?= BASE ?>/" target="_blank">View website ↗</a>
      <a href="<?= admin_url('logout.php') ?>">Log out</a>
    </div>
  </aside>
  <div class="main">
    <header class="top">
      <button class="top__menu" onclick="document.body.classList.toggle('side-open')" aria-label="Menu">☰</button>
      <h1><?= e($title) ?></h1>
    </header>
    <div class="content">
    <?php if (!empty($_SESSION['admin_flash'])): [$m, $t] = $_SESSION['admin_flash']; unset($_SESSION['admin_flash']); ?>
      <div class="flash flash--<?= e($t) ?>"><?= e($m) ?></div>
    <?php endif;
}

function admin_footer(array $scripts = []): void
{
    ?>
    </div>
  </div>
</div>
<?php foreach ($scripts as $s): ?><script src="<?= e($s) ?>"></script><?php endforeach; ?>
<script src="<?= BASE ?>/admin/assets/admin.js?v=1.0"></script>
</body>
</html>
<?php
}

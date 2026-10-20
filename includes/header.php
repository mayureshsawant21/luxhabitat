<?php
/**
 * Expects (optional): $pageTitle, $pageDesc, $ogImage, $bodyClass, $solidHeader
 */
$siteName  = setting('site_name', 'LuxHabitat Mumbai');
$pageTitle = isset($pageTitle) && $pageTitle ? $pageTitle . ' | ' . $siteName : $siteName . ' — ' . setting('tagline');
$pageDesc  = $pageDesc ?? setting('tagline');
$bodyClass = $bodyClass ?? '';
$solidHeader = $solidHeader ?? false;
$menuProjects = array_slice(get_projects(), 0, 4);
$menuLocations = array_slice(array_keys(filter_options(get_projects())['locations']), 0, 6);
?><!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="theme-color" content="#0e0d0b">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:type" content="website">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<link rel="icon" href="<?= BASE ?>/assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css?v=1.0">
<script>
document.documentElement.classList.add('js');
try { if (sessionStorage.getItem('lxSeen')) document.documentElement.classList.add('no-preload'); } catch (e) {}
window.LX = { base: <?= json_encode(BASE) ?>, placeholder: <?= json_encode(placeholder()) ?> };</script>
<?php if (STATIC_BUILD):
    $lxProjects = [];
    foreach (get_projects() as $sp) {
        $lxProjects[$sp['id']] = ['name' => $sp['name'], 'url' => project_url($sp), 'brochure' => $sp['brochure'] ? img($sp['brochure']) : ''];
    } ?>
<script>window.LX.static = true; window.LX.projects = <?= json_encode($lxProjects, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>; window.LX.thanks = <?= json_encode(url('thank-you')) ?>;</script>
<script src="<?= BASE ?>/site-config.js"></script>
<?php endif; ?>
<?= setting('head_scripts') ?>
</head>
<body class="<?= e($bodyClass) ?>">

<div class="preloader" aria-hidden="true">
  <div class="preloader__inner">
    <span class="preloader__word">LuxHabitat</span>
    <span class="preloader__line"></span>
    <span class="preloader__sub">Mumbai</span>
  </div>
</div>

<div class="cursor" aria-hidden="true"><span></span></div>

<header class="site-header <?= $solidHeader ? 'is-solid' : '' ?>" id="siteHeader">
  <a href="<?= url('') ?>" class="logo" aria-label="<?= e($siteName) ?> — Home">
    <?php if (setting('logo')): ?>
      <img src="<?= e(img(setting('logo'))) ?>" alt="<?= e($siteName) ?>">
    <?php else: ?>
      <span class="logo__mark">LuxHabitat</span><span class="logo__city">Mumbai</span>
    <?php endif; ?>
  </a>
  <button class="burger" id="menuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="siteMenu">
    <span></span><span></span>
  </button>
</header>

<nav class="menu" id="siteMenu" aria-hidden="true">
  <div class="menu__bg"></div>
  <div class="menu__inner">
    <ul class="menu__links">
      <li><a href="<?= url('') ?>"><small>01</small>Home</a></li>
      <li><a href="<?= url('projects') ?>"><small>02</small>All Projects</a></li>
      <li><a href="<?= url('about') ?>"><small>03</small>About Us</a></li>
      <li><a href="<?= url('contact') ?>"><small>04</small>Contact</a></li>
    </ul>
    <aside class="menu__aside">
      <?php if ($menuLocations): ?>
      <div class="menu__block">
        <h6>Explore by location</h6>
        <div class="menu__chips">
          <?php foreach ($menuLocations as $loc): ?>
            <a href="<?= url('projects?location[]=' . rawurlencode($loc)) ?>"><?= e($loc) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="menu__block">
        <h6>Speak to an advisor</h6>
        <a class="menu__big" href="<?= e(tel_link()) ?>"><?= e(setting('phone')) ?></a>
        <a class="menu__mail" href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a>
      </div>
      <button class="btn btn--gold" data-enquire data-source="menu">Book a private consultation</button>
    </aside>
  </div>
</nav>

<main id="main">

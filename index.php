<?php
require __DIR__ . '/includes/bootstrap.php';

$banners  = db()->query('SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
$projects = get_projects();
$featured = array_values(array_filter($projects, fn($p) => $p['featured']));
if (count($featured) < 3) $featured = array_slice($projects, 0, 6);
$featured = array_slice($featured, 0, 6);
$opts = filter_options($projects);

$pageDesc  = setting('hero_subtitle');
$bodyClass = 'page-home';
$ogImage   = $banners ? img($banners[0]['image']) : '';
include __DIR__ . '/includes/header.php';
?>

<section class="hero" id="hero">
  <div class="hero__slides" data-slider data-interval="6500">
    <?php foreach ($banners ?: [['image' => '', 'title' => '', 'subtitle' => '', 'link' => '']] as $k => $b): ?>
      <figure class="hero__slide <?= $k === 0 ? 'is-active' : '' ?>">
        <img src="<?= e(img($b['image'])) ?>" alt="<?= e($b['title'] ?: setting('site_name')) ?>" <?= $k ? 'loading="lazy"' : 'fetchpriority="high"' ?> onerror="this.onerror=null;this.src=LX.placeholder">
        <figcaption class="hero__caption">
          <span class="hero__caption-title"><?= e($b['title']) ?></span>
          <span class="hero__caption-sub"><?= e($b['subtitle']) ?></span>
          <?php if (!empty($b['link'])): ?><a href="<?= e($b['link']) ?>" class="hero__caption-link">Discover &rarr;</a><?php endif; ?>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
  <div class="hero__shade"></div>

  <div class="hero__content container">
    <p class="eyebrow eyebrow--light hero__eyebrow"><?= e(setting('tagline')) ?></p>
    <h1 class="hero__title display" data-split><?= e(setting('hero_title')) ?></h1>
    <p class="hero__sub"><?= e(setting('hero_subtitle')) ?></p>

    <form class="search" action="<?= url('projects') ?>" method="get" role="search">
      <label class="search__field">
        <span>Location</span>
        <select name="location[]">
          <option value="">All locations</option>
          <?php foreach ($opts['locations'] as $loc => $n): ?><option value="<?= e($loc) ?>"><?= e($loc) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="search__field">
        <span>Configuration</span>
        <select name="config[]">
          <option value="">Any configuration</option>
          <?php foreach ($opts['configs'] as $c): ?><option value="<?= e($c) ?>"><?= e(config_label($c)) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label class="search__field">
        <span>Budget</span>
        <select name="budget">
          <option value="">Any budget</option>
          <?php foreach (BUDGETS as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v) ?></option><?php endforeach; ?>
        </select>
      </label>
      <button class="search__btn" type="submit">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <span>Search</span>
      </button>
    </form>
  </div>

  <div class="hero__ui container">
    <div class="hero__count"><b data-slide-current>01</b><i></i><span><?= sprintf('%02d', max(1, count($banners))) ?></span></div>
    <div class="hero__dots" data-slider-dots></div>
    <a href="#featured" class="hero__scroll" aria-label="Scroll to featured projects"><span></span>Scroll</a>
  </div>
</section>

<section class="section section--featured" id="featured">
  <div class="container">
    <div class="section__head">
      <div data-reveal>
        <p class="eyebrow">Featured residences</p>
        <h2 class="display">Handpicked homes,<br><em>exceptional addresses</em></h2>
      </div>
      <a href="<?= url('projects') ?>" class="btn btn--line" data-reveal>
        <span>Browse all projects</span><i aria-hidden="true">&rarr;</i>
      </a>
    </div>
    <div class="cards">
      <?php foreach ($featured as $i => $p) include __DIR__ . '/includes/project-card.php'; ?>
    </div>
    <div class="section__foot" data-reveal>
      <a href="<?= url('projects') ?>" class="btn btn--dark"><span>View all <?= count($projects) ?> projects</span></a>
    </div>
  </div>
</section>

<section class="marquee" aria-hidden="true">
  <div class="marquee__track">
    <?php for ($r = 0; $r < 2; $r++): foreach (array_keys($opts['locations']) ?: ['Mumbai'] as $loc): ?>
      <span><?= e($loc) ?></span><i>✦</i>
    <?php endforeach; endfor; ?>
  </div>
</section>

<section class="section section--why">
  <div class="container why">
    <div class="why__text" data-reveal>
      <p class="eyebrow">Why LuxHabitat</p>
      <h2 class="display">Advice you can trust,<br><em>access you can&rsquo;t get alone</em></h2>
      <?php foreach (array_filter(explode("\n", setting('about_text'))) as $para): ?><p><?= e($para) ?></p><?php endforeach; ?>
      <a href="<?= url('about') ?>" class="btn btn--line"><span>Our story</span><i aria-hidden="true">&rarr;</i></a>
    </div>
    <div class="why__stats">
      <?php for ($s = 1; $s <= 3; $s++): if (!setting("stat_{$s}_value")) continue; ?>
        <div class="stat" data-reveal style="--d: <?= $s - 1 ?>">
          <b data-count="<?= e(setting("stat_{$s}_value")) ?>"><?= e(setting("stat_{$s}_value")) ?></b>
          <span><?= e(setting("stat_{$s}_label")) ?></span>
        </div>
      <?php endfor; ?>
      <ul class="why__list" data-reveal>
        <li><b>01</b>Shortlists built around your brief — not inventory pressure</li>
        <li><b>02</b>Developer-direct pricing, exclusive offers &amp; zero brokerage</li>
        <li><b>03</b>Chauffeured site visits &amp; virtual walkthroughs</li>
        <li><b>04</b>Loan, legal &amp; registration support until possession</li>
      </ul>
    </div>
  </div>
</section>

<?php
// Locations with a cover image from one of their projects
$locTiles = [];
foreach ($projects as $p) {
    if (!$p['location'] || isset($locTiles[$p['location']])) continue;
    $locTiles[$p['location']] = ['img' => $p['thumbnail'] ?: $p['elevation'], 'n' => $opts['locations'][$p['location']]];
}
$locTiles = array_slice($locTiles, 0, 6, true);
if ($locTiles): ?>
<section class="section section--locations">
  <div class="container">
    <div class="section__head">
      <div data-reveal>
        <p class="eyebrow">Explore by neighbourhood</p>
        <h2 class="display">Mumbai, <em>your way</em></h2>
      </div>
    </div>
    <div class="locs">
      <?php $li = 0; foreach ($locTiles as $loc => $t): ?>
        <a class="loc" href="<?= url('projects?location[]=' . rawurlencode($loc)) ?>" data-reveal style="--d: <?= $li++ % 3 ?>">
          <img src="<?= e(img($t['img'])) ?>" alt="Projects in <?= e($loc) ?>" loading="lazy" onerror="this.onerror=null;this.src=LX.placeholder">
          <span class="loc__name"><?= e($loc) ?></span>
          <span class="loc__count"><?= (int)$t['n'] ?> project<?= $t['n'] > 1 ? 's' : '' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

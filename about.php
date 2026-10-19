<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle   = 'About Us';
$pageDesc    = 'LuxHabitat Mumbai — boutique real-estate advisory and authorised channel partner for Mumbai\'s leading developers.';
$bodyClass   = 'page-about';
$solidHeader = true;
include __DIR__ . '/includes/header.php';
$projects = get_projects();
$cover = $projects[0]['elevation'] ?? '';
?>
<section class="page-hero page-hero--compact">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>">Home</a><span>/</span>About</nav>
    <h1 class="display" data-split>Advisors, not agents</h1>
    <p class="page-hero__sub"><?= e(setting('tagline')) ?></p>
  </div>
</section>
<section class="section section--tight">
  <div class="container overview__grid">
    <div class="overview__media" data-reveal="clip">
      <img src="<?= e(img($cover)) ?>" alt="" loading="lazy" onerror="this.onerror=null;this.src=LX.placeholder">
    </div>
    <div class="overview__text">
      <p class="eyebrow" data-reveal>Our story</p>
      <h2 class="display" data-reveal>Every home has a story. <em>We help you find yours.</em></h2>
      <div class="prose" data-reveal>
        <?php foreach (array_filter(explode("\n", setting('about_text'))) as $para): ?><p><?= e($para) ?></p><?php endforeach; ?>
      </div>
      <dl class="facts" data-reveal>
        <?php for ($s = 1; $s <= 3; $s++): if (!setting("stat_{$s}_value")) continue; ?>
          <div><dt><?= e(setting("stat_{$s}_label")) ?></dt><dd><?= e(setting("stat_{$s}_value")) ?></dd></div>
        <?php endfor; ?>
        <?php if (setting('maharera_agent')): ?><div><dt>MahaRERA Agent No.</dt><dd><?= e(setting('maharera_agent')) ?></dd></div><?php endif; ?>
      </dl>
    </div>
  </div>
</section>
<section class="section section--dark">
  <div class="container">
    <div class="section__head"><div data-reveal><p class="eyebrow eyebrow--light">How we work</p><h2 class="display">A calmer way <em>to buy a home</em></h2></div></div>
    <div class="steps">
      <div data-reveal style="--d:0"><b>01</b><h3>Understand</h3><p>We begin with your brief — budget, commute, schools, lifestyle and timelines.</p></div>
      <div data-reveal style="--d:1"><b>02</b><h3>Shortlist</h3><p>A tight shortlist with honest pros and cons, price benchmarks and RERA checks.</p></div>
      <div data-reveal style="--d:2"><b>03</b><h3>Experience</h3><p>Chauffeured site visits, sample flats and virtual walkthroughs at your pace.</p></div>
      <div data-reveal style="--d:3"><b>04</b><h3>Close &amp; beyond</h3><p>Negotiation, loans, agreement and registration support — until you get the keys.</p></div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

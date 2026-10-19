<?php
/** One thank-you page for every form on the website. */
require __DIR__ . '/includes/bootstrap.php';

$name    = $_SESSION['thanks_name'] ?? '';
$src     = (string)($_GET['src'] ?? '');
$project = !empty($_GET['p']) ? get_project_by_slug((string)$_GET['p']) : null;
$dl      = (int)($_GET['dl'] ?? 0);
$canDl   = $project && $dl === (int)$project['id'] && !empty($_SESSION['unlocked'][$dl]) && $project['brochure'];
$dlUrl   = $canDl ? BASE . '/download.php?id=' . $dl : '';

$headline = match (true) {
    $canDl               => 'Your brochure is ready',
    $src === 'site-visit' => 'Your site visit is being arranged',
    $src === 'floor-plan' => 'Floor plans unlocked',
    default              => 'Thank you' . ($name ? ', ' . explode(' ', $name)[0] : ''),
};

$pageTitle   = 'Thank you';
$bodyClass   = 'page-thanks';
$solidHeader = true;
$hideFooterCta = true;
include __DIR__ . '/includes/header.php';
?>
<section class="thanks">
  <div class="container thanks__inner">
    <div class="thanks__mark" aria-hidden="true">
      <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="38" fill="none" stroke="currentColor" stroke-width="1"/><path d="M25 41l10 10 20-22" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <p class="eyebrow">Request received</p>
    <h1 class="display" data-split><?= e($headline) ?></h1>
    <p class="thanks__text">
      <?php if ($canDl): ?>
        Your download of the <?= e($project['name']) ?> e-brochure should begin automatically. If it doesn&rsquo;t, use the button below.
      <?php else: ?>
        Our advisor will call you shortly<?= $project ? ' with details on <strong>' . e($project['name']) . '</strong>' : '' ?>. For anything urgent, reach us directly on <a href="<?= e(tel_link()) ?>"><?= e(setting('phone')) ?></a>.
      <?php endif; ?>
    </p>
    <div class="thanks__actions">
      <?php if ($canDl): ?>
        <a class="btn btn--gold" href="<?= e($dlUrl) ?>" id="dlBtn" data-autodl>Download brochure</a>
      <?php endif; ?>
      <?php if ($project): ?>
        <a class="btn btn--line" href="<?= e(project_url($project)) ?><?= $src === 'floor-plan' ? '#floor-plans' : '' ?>"><span>Back to <?= e($project['name']) ?></span></a>
      <?php endif; ?>
      <a class="btn btn--<?= $project ? 'line' : 'dark' ?>" href="<?= url('projects') ?>"><span>Explore more projects</span></a>
    </div>
    <div class="thanks__next">
      <div><b>01</b><span>An advisor calls you within 30 minutes (10 AM – 7 PM)</span></div>
      <div><b>02</b><span>Receive price sheet, plans &amp; offers on WhatsApp</span></div>
      <div><b>03</b><span>Visit the site with complimentary pick-up &amp; drop</span></div>
    </div>
  </div>
</section>
<?= setting('thankyou_scripts') ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

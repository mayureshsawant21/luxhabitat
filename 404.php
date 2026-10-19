<?php
if (!defined('ROOT')) {
    require __DIR__ . '/includes/bootstrap.php';
    http_response_code(404);
}
$pageTitle = 'Page not found';
$bodyClass = 'page-thanks';
$solidHeader = true;
include __DIR__ . '/includes/header.php';
?>
<section class="thanks">
  <div class="container thanks__inner">
    <p class="eyebrow">Error 404</p>
    <h1 class="display" data-split>This address doesn&rsquo;t exist</h1>
    <p class="thanks__text">The page may have moved, or the project is no longer listed. Let us help you find the right one.</p>
    <div class="thanks__actions">
      <a class="btn btn--dark" href="<?= url('projects') ?>"><span>Browse all projects</span></a>
      <button class="btn btn--line" data-enquire data-source="404"><span>Talk to an advisor</span></button>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

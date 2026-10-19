<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle   = 'Contact';
$pageDesc    = 'Speak to a LuxHabitat Mumbai advisor for curated property recommendations, site visits and the best developer pricing.';
$bodyClass   = 'page-contact';
$solidHeader = true;
$hideFooterCta = true;
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero--compact">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>">Home</a><span>/</span>Contact</nav>
    <h1 class="display" data-split>Let&rsquo;s talk homes</h1>
    <p class="page-hero__sub">One conversation. A shortlist made only for you.</p>
  </div>
</section>
<section class="section section--tight">
  <div class="container contact__grid">
    <div class="contact__info" data-reveal>
      <div><small>Call</small><a href="<?= e(tel_link()) ?>"><?= e(setting('phone')) ?></a></div>
      <div><small>WhatsApp</small><a href="<?= e(wa_link('Hi, I am looking for a home in Mumbai.')) ?>" target="_blank" rel="noopener">Chat with an advisor</a></div>
      <div><small>Email</small><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></div>
      <div><small>Office</small><span><?= e(setting('address')) ?></span></div>
      <div><small>Hours</small><span><?= e(setting('office_hours')) ?></span></div>
    </div>
    <div class="contact__form" data-reveal>
      <h2 class="display">Tell us what you&rsquo;re looking for</h2>
      <?php lead_form(['source' => 'contact-page', 'button' => 'Send enquiry', 'message' => true, 'id' => 'contact']); ?>
    </div>
  </div>
</section>
<?php if (setting('address')): ?>
<section class="contact__map" data-reveal="clip">
  <iframe title="Office location" loading="lazy" src="https://maps.google.com/maps?q=<?= rawurlencode(setting('address')) ?>&amp;z=14&amp;output=embed"></iframe>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

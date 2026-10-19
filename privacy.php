<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle   = 'Privacy Policy';
$bodyClass   = 'page-legal';
$solidHeader = true;
include __DIR__ . '/includes/header.php';
$site = e(setting('site_name'));
?>
<section class="page-hero page-hero--compact">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>">Home</a><span>/</span>Privacy Policy</nav>
    <h1 class="display">Privacy Policy</h1>
  </div>
</section>
<section class="section section--tight">
  <div class="container prose prose--narrow">
    <p><?= $site ?> respects your privacy. This policy explains what we collect when you use this website and how we use it.</p>
    <h3>Information we collect</h3>
    <p>When you submit a form we collect your name, mobile number, email address (optional), your message, the project you enquired about and basic technical data such as the page you were on and campaign source.</p>
    <h3>How we use it</h3>
    <p>We use this information only to respond to your enquiry, share project details, arrange site visits and send relevant updates. We may share your details with the developer of the project you enquired about so they can assist you.</p>
    <h3>Communication consent</h3>
    <p>By submitting a form you authorise us and our partners to contact you via call, SMS, WhatsApp and email, even if your number is registered under DND/NDNC. You may opt out at any time by writing to <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a>.</p>
    <h3>Data security &amp; retention</h3>
    <p>We store your details securely and retain them only for as long as needed for the purposes above or as required by law. We never sell your personal data.</p>
    <h3>Cookies</h3>
    <p>We use essential cookies to keep the website working (for example, to unlock brochures you requested) and may use analytics cookies to improve our service.</p>
    <h3>Disclaimer</h3>
    <p><?= e(setting('disclaimer')) ?></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>

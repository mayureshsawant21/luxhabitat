<?php
$footLocations = array_slice(array_keys(filter_options(get_projects())['locations']), 0, 6);
$socials = array_filter([
    'Instagram' => setting('instagram'), 'Facebook' => setting('facebook'),
    'YouTube' => setting('youtube'), 'LinkedIn' => setting('linkedin'),
]);
?>
</main>

<?php if (empty($hideFooterCta)): ?>
<section class="cta-band">
  <div class="container cta-band__inner" data-reveal>
    <p class="eyebrow">Private advisory · Zero brokerage</p>
    <h2 class="display">Let&rsquo;s find your <em>next address</em></h2>
    <div class="cta-band__actions">
      <button class="btn btn--gold" data-enquire data-source="footer-cta">Schedule a call back</button>
      <a class="btn btn--ghost-light" href="<?= e(wa_link('Hi, I am looking for a home in Mumbai.')) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
    </div>
  </div>
</section>
<?php endif; ?>

<footer class="site-footer">
  <div class="container">
    <div class="footer__grid">
      <div class="footer__brand">
        <a href="<?= url('') ?>" class="logo logo--footer"><span class="logo__mark">LuxHabitat</span><span class="logo__city">Mumbai</span></a>
        <p><?= e(setting('tagline')) ?>. An authorised channel partner for Mumbai&rsquo;s leading developers.</p>
        <?php if (setting('maharera_agent')): ?>
          <p class="footer__rera">MahaRERA Agent Reg. No. <strong><?= e(setting('maharera_agent')) ?></strong></p>
        <?php endif; ?>
      </div>
      <div>
        <h6>Explore</h6>
        <ul>
          <li><a href="<?= url('projects') ?>">All Projects</a></li>
          <li><a href="<?= url('projects?status[]=New+Launch') ?>">New Launches</a></li>
          <li><a href="<?= url('projects?status[]=Ready+to+Move') ?>">Ready to Move</a></li>
          <li><a href="<?= url('about') ?>">About Us</a></li>
          <li><a href="<?= url('contact') ?>">Contact</a></li>
        </ul>
      </div>
      <div>
        <h6>Locations</h6>
        <ul>
          <?php foreach ($footLocations as $loc): ?>
            <li><a href="<?= url('projects?location[]=' . rawurlencode($loc)) ?>"><?= e($loc) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h6>Visit / Call</h6>
        <ul class="footer__contact">
          <li><?= e(setting('address')) ?></li>
          <li><a href="<?= e(tel_link()) ?>"><?= e(setting('phone')) ?></a></li>
          <li><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li>
          <li><?= e(setting('office_hours')) ?></li>
        </ul>
        <?php if ($socials): ?>
          <div class="footer__social">
            <?php foreach ($socials as $n => $l): ?><a href="<?= e($l) ?>" target="_blank" rel="noopener"><?= e($n) ?></a><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <p class="footer__disclaimer"><?= e(setting('disclaimer')) ?></p>
    <div class="footer__bottom">
      <span>&copy; <?= date('Y') ?> <?= e(setting('site_name')) ?>. All rights reserved.</span>
      <span><a href="<?= url('privacy-policy') ?>">Privacy Policy</a></span>
    </div>
  </div>
</footer>

<a class="wa-float" href="<?= e(wa_link('Hi, I would like to know more about your projects.')) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.5-.3z"/></svg>
</a>

<?php include __DIR__ . '/lead-modal.php'; ?>
<?php if (!empty($_SESSION['flash_error'])): ?><div class="toast" role="alert"><?= e($_SESSION['flash_error']) ?></div><?php unset($_SESSION['flash_error']); endif; ?>

<script src="<?= BASE ?>/assets/vendor/lenis.min.js" defer></script>
<script src="<?= BASE ?>/assets/js/app.js?v=1.0" defer></script>
</body>
</html>

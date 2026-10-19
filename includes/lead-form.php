<?php
/**
 * Reusable lead form. Every form posts to lead.php → saved in admin → redirect to the single thank-you page.
 */
if (!function_exists('lead_form')) {
    function lead_form(array $o = []): void
    {
        $o += ['source' => 'enquiry', 'project_id' => 0, 'project' => '', 'button' => 'Submit', 'message' => false, 'visit' => false, 'id' => ''];
        $uid = $o['id'] ?: 'f' . substr(md5(uniqid('', true)), 0, 6);
        ?>
        <form class="lead-form" method="post" action="<?= BASE ?>/lead.php" novalidate>
          <input type="hidden" name="source" value="<?= e($o['source']) ?>">
          <input type="hidden" name="project_id" value="<?= (int)$o['project_id'] ?>">
          <input type="hidden" name="project" value="<?= e($o['project']) ?>">
          <input type="hidden" name="page_url" value="">
          <input type="hidden" name="utm" value="">
          <div class="hp" aria-hidden="true"><label>Leave empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="field">
            <input type="text" name="name" id="<?= $uid ?>-name" required minlength="2" maxlength="80" autocomplete="name" placeholder=" ">
            <label for="<?= $uid ?>-name">Full name</label>
          </div>
          <div class="field field--phone">
            <span class="field__prefix">+91</span>
            <input type="tel" name="phone" id="<?= $uid ?>-phone" required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" autocomplete="tel-national" placeholder=" ">
            <label for="<?= $uid ?>-phone">Mobile number</label>
          </div>
          <div class="field">
            <input type="email" name="email" id="<?= $uid ?>-email" maxlength="120" autocomplete="email" placeholder=" ">
            <label for="<?= $uid ?>-email">Email (optional)</label>
          </div>
          <?php if ($o['visit']): ?>
          <div class="field">
            <input type="date" name="visit_date" id="<?= $uid ?>-visit" min="<?= date('Y-m-d') ?>" placeholder=" ">
            <label for="<?= $uid ?>-visit" class="is-fixed">Preferred site-visit date (optional)</label>
          </div>
          <?php endif; ?>
          <?php if ($o['message']): ?>
          <div class="field">
            <textarea name="message" id="<?= $uid ?>-msg" rows="3" maxlength="1000" placeholder=" "></textarea>
            <label for="<?= $uid ?>-msg">Your requirement (optional)</label>
          </div>
          <?php endif; ?>
          <label class="consent">
            <input type="checkbox" name="consent" value="1" checked required>
            <span>I authorise <?= e(setting('site_name')) ?> and its partners to contact me via call, SMS, WhatsApp &amp; email. This overrides DND/NDNC.</span>
          </label>
          <button type="submit" class="btn btn--gold btn--block"><span><?= e($o['button']) ?></span></button>
        </form>
        <?php
    }
}

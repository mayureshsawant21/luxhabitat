<?php
require __DIR__ . '/inc/auth.php';
require_admin();

$groups = [
    'General' => [
        'site_name' => ['Website name', 'text'],
        'tagline' => ['Tagline', 'text'],
        'maharera_agent' => ['Your MahaRERA agent registration no. (shown in footer — mandatory for channel partners)', 'text'],
    ],
    'Contact' => [
        'phone' => ['Phone (display)', 'text'],
        'whatsapp' => ['WhatsApp number with country code (e.g. 919820000000)', 'text'],
        'email' => ['Public email', 'email'],
        'notify_email' => ['Send new-lead alerts to (comma-separate multiple)', 'text'],
        'address' => ['Office address', 'text'],
        'office_hours' => ['Office hours', 'text'],
    ],
    'Home page' => [
        'hero_title' => ['Hero headline', 'text'],
        'hero_subtitle' => ['Hero sub-text', 'textarea'],
        'about_text' => ['About / Why us text (blank line = new paragraph)', 'textarea'],
        'stat_1_value' => ['Stat 1 value', 'text'], 'stat_1_label' => ['Stat 1 label', 'text'],
        'stat_2_value' => ['Stat 2 value', 'text'], 'stat_2_label' => ['Stat 2 label', 'text'],
        'stat_3_value' => ['Stat 3 value', 'text'], 'stat_3_label' => ['Stat 3 label', 'text'],
    ],
    'Social' => [
        'instagram' => ['Instagram URL', 'url'], 'facebook' => ['Facebook URL', 'url'],
        'youtube' => ['YouTube URL', 'url'], 'linkedin' => ['LinkedIn URL', 'url'],
    ],
    'Legal' => [
        'disclaimer' => ['Footer disclaimer', 'textarea'],
    ],
    'Integrations' => [
        'anthropic_api_key' => ['Anthropic API key — enables AI brochure reading (console.anthropic.com)', 'secret'],
        'webhook_url' => ['Lead webhook URL (https) — every lead is POSTed as JSON (Zapier, Make, Google Apps Script, CRM)', 'url'],
        'head_scripts' => ['Code inside <head> on every page (Google Analytics, Meta Pixel, Search Console…)', 'code'],
        'thankyou_scripts' => ['Code on the thank-you page only (Google Ads / Meta conversion events)', 'code'],
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    if (($_POST['action'] ?? '') === 'password') {
        $cur = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify($cur, setting('admin_password'))) flash('Current password is incorrect.', 'err');
        elseif (strlen($new) < 8) flash('New password must be at least 8 characters.', 'err');
        elseif ($new !== ($_POST['confirm'] ?? '')) flash('New passwords do not match.', 'err');
        else { save_setting('admin_password', password_hash($new, PASSWORD_DEFAULT)); flash('Password changed.'); }
        redirect(admin_url('settings.php#security'));
    }
    foreach ($groups as $fields) {
        foreach ($fields as $key => [$label, $type]) {
            if (!array_key_exists($key, $_POST)) continue;
            $v = trim((string)$_POST[$key]);
            if ($type === 'secret' && $v === '') continue;          // keep existing secret
            if ($type === 'secret' && $v === '-') $v = '';           // "-" clears it
            if ($type === 'url' && $v !== '' && !preg_match('#^https?://#', $v)) $v = 'https://' . $v;
            save_setting($key, $v);
        }
    }
    if (!empty($_POST['logo_remove'])) { delete_upload(setting('logo')); save_setting('logo', ''); }
    if ($f = files_list('logo_file')) { if ($s = save_image($f[0], 800)) { delete_upload(setting('logo')); save_setting('logo', $s); } }
    flash('Settings saved.');
    redirect(admin_url('settings.php'));
}

admin_header('Settings', 'settings.php');
?>
<form method="post" enctype="multipart/form-data" class="settings">
  <?= csrf_field() ?>
  <?php foreach ($groups as $g => $fields): ?>
    <section class="panel">
      <h2 class="panel__title"><?= e($g) ?></h2>
      <?php if ($g === 'General'): ?>
        <div class="row row--logo">
          <div class="logo-prev"><?php if (setting('logo')): ?><img src="<?= e(img(setting('logo'))) ?>" alt="Logo"><?php else: ?><span class="muted small">Text logo “LuxHabitat Mumbai” in use</span><?php endif; ?></div>
          <label class="f"><span>Logo image (PNG/SVG-like PNG, light version for dark banner)</span><input type="file" name="logo_file" accept="image/*"></label>
          <?php if (setting('logo')): ?><label class="cb"><input type="checkbox" name="logo_remove" value="1"><span>Use text logo</span></label><?php endif; ?>
        </div>
      <?php endif; ?>
      <?php foreach ($fields as $key => [$label, $type]): ?>
        <label class="f">
          <span><?= e($label) ?></span>
          <?php if ($type === 'textarea' || $type === 'code'): ?>
            <textarea name="<?= $key ?>" rows="<?= $type === 'code' ? 4 : 3 ?>" class="<?= $type === 'code' ? 'mono' : '' ?>"><?= e(setting($key)) ?></textarea>
          <?php elseif ($type === 'secret'): ?>
            <input type="password" name="<?= $key ?>" autocomplete="off" placeholder="<?= setting($key) ? '•••••••• saved (leave blank to keep, type - to remove)' : 'sk-ant-…' ?>">
          <?php else: ?>
            <input type="<?= $type === 'url' ? 'text' : $type ?>" name="<?= $key ?>" value="<?= e(setting($key)) ?>">
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
  <div class="savebar"><button class="b b--gold" type="submit">Save settings</button></div>
</form>

<form method="post" class="panel" id="security">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="password">
  <h2 class="panel__title">Admin password</h2>
  <div class="row">
    <label class="f"><span>Current password</span><input type="password" name="current" required autocomplete="current-password"></label>
    <label class="f"><span>New password</span><input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
    <label class="f"><span>Confirm new password</span><input type="password" name="confirm" required minlength="8" autocomplete="new-password"></label>
  </div>
  <button class="b" type="submit">Change password</button>
</form>
<?php admin_footer(); ?>

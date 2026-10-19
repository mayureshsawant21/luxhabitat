<?php
require __DIR__ . '/inc/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $t = fn($k) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, 200);

    if ($act === 'add' || $act === 'save') {
        $image = $t('image_keep');
        if ($f = files_list('image_file')) $image = save_image($f[0]) ?: $image;
        if ($t('image_url') && preg_match('#^https?://#', $t('image_url'))) $image = $t('image_url');
        if ($act === 'add') {
            if (!$image) { flash('Please upload an image (or paste an image URL).', 'err'); redirect(admin_url('banners.php')); }
            db()->prepare('INSERT INTO banners(image, title, subtitle, link, sort_order, is_active) VALUES (?,?,?,?,?,1)')
                ->execute([$image, $t('title'), $t('subtitle'), $t('link'), (int)($_POST['sort_order'] ?? 0)]);
            flash('Banner added.');
        } else {
            $old = db()->prepare('SELECT image FROM banners WHERE id = ?'); $old->execute([$id]); $oldImg = $old->fetchColumn();
            db()->prepare('UPDATE banners SET image = ?, title = ?, subtitle = ?, link = ?, sort_order = ?, is_active = ? WHERE id = ?')
                ->execute([$image ?: $oldImg, $t('title'), $t('subtitle'), $t('link'), (int)($_POST['sort_order'] ?? 0), !empty($_POST['is_active']) ? 1 : 0, $id]);
            if ($oldImg && $image && $oldImg !== $image) delete_upload($oldImg);
            flash('Banner saved.');
        }
    } elseif ($act === 'delete') {
        $old = db()->prepare('SELECT image FROM banners WHERE id = ?'); $old->execute([$id]);
        delete_upload($old->fetchColumn() ?: '');
        db()->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
        flash('Banner deleted.');
    }
    redirect(admin_url('banners.php'));
}

$banners = db()->query('SELECT * FROM banners ORDER BY sort_order, id')->fetchAll();
$projectLinks = array_map(fn($p) => ['name' => $p['name'], 'url' => project_url($p)], get_projects());
admin_header('Home banners', 'banners.php');
?>
<p class="muted">These images slide behind the search bar on the home page. Use wide landscape photos (at least 2000px wide). The big headline comes from <a href="<?= admin_url('settings.php') ?>">Settings → Home page</a>; the title/subtitle below show as a small caption on each slide.</p>

<div class="banners">
  <?php foreach ($banners as $b): ?>
    <form method="post" enctype="multipart/form-data" class="panel banner <?= $b['is_active'] ? '' : 'is-off' ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $b['id'] ?>">
      <input type="hidden" name="image_keep" value="<?= e($b['image']) ?>">
      <div class="banner__img"><img src="<?= e(img($b['image'])) ?>" alt="" data-preview="image_file" onerror="this.src='<?= placeholder() ?>'"></div>
      <div class="banner__fields">
        <div class="row">
          <label class="f"><span>Caption title</span><input name="title" value="<?= e($b['title']) ?>"></label>
          <label class="f"><span>Caption subtitle</span><input name="subtitle" value="<?= e($b['subtitle']) ?>"></label>
        </div>
        <div class="row">
          <label class="f f--2"><span>Link (optional)</span><input name="link" value="<?= e($b['link']) ?>" list="projLinks"></label>
          <label class="f"><span>Order</span><input type="number" name="sort_order" value="<?= (int)$b['sort_order'] ?>"></label>
        </div>
        <div class="row">
          <label class="f"><span>Replace image</span><input type="file" name="image_file" accept="image/*" data-preview-input></label>
          <label class="f"><span>…or image URL</span><input name="image_url"></label>
        </div>
        <div class="banner__actions">
          <label class="switch"><input type="checkbox" name="is_active" value="1" <?= $b['is_active'] ? 'checked' : '' ?>><span>Show</span></label>
          <button class="b b--sm b--gold" name="action" value="save">Save</button>
          <button class="b b--sm b--danger" name="action" value="delete" onclick="return confirm('Delete this banner?')">Delete</button>
        </div>
      </div>
    </form>
  <?php endforeach; ?>
</div>

<form method="post" enctype="multipart/form-data" class="panel">
  <?= csrf_field() ?>
  <h2 class="panel__title">Add a banner</h2>
  <div class="row">
    <label class="f"><span>Image *</span><input type="file" name="image_file" accept="image/*"></label>
    <label class="f"><span>…or image URL</span><input name="image_url" placeholder="https://"></label>
  </div>
  <div class="row">
    <label class="f"><span>Caption title</span><input name="title"></label>
    <label class="f"><span>Caption subtitle</span><input name="subtitle"></label>
    <label class="f"><span>Link (optional)</span><input name="link" list="projLinks"></label>
    <label class="f"><span>Order</span><input type="number" name="sort_order" value="<?= count($banners) + 1 ?>"></label>
  </div>
  <button class="b b--gold" name="action" value="add">Add banner</button>
</form>
<datalist id="projLinks"><?php foreach ($projectLinks as $pl): ?><option value="<?= e($pl['url']) ?>"><?= e($pl['name']) ?></option><?php endforeach; ?></datalist>
<?php admin_footer(); ?>

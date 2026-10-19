<?php
require __DIR__ . '/inc/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $cat = trim((string)($_POST['category'] ?? '')) ?: 'Other';
        $n = 0;
        foreach (preg_split('/\r?\n/', (string)($_POST['names'] ?? '')) as $name) {
            $name = mb_substr(trim($name), 0, 60);
            if ($name === '') continue;
            $n += db()->prepare('INSERT OR IGNORE INTO amenities(name, category, sort_order) VALUES (?, ?, 500)')->execute([$name, $cat]) ? 1 : 0;
        }
        flash($n . ' amenit' . ($n === 1 ? 'y' : 'ies') . ' added.');
    } elseif ($act === 'save') {
        foreach ((array)($_POST['a'] ?? []) as $id => $row) {
            $name = mb_substr(trim((string)($row['name'] ?? '')), 0, 60);
            $cat = mb_substr(trim((string)($row['category'] ?? '')), 0, 60) ?: 'Other';
            if ($name === '') continue;
            $old = db()->prepare('SELECT name FROM amenities WHERE id = ?'); $old->execute([(int)$id]);
            $oldName = $old->fetchColumn();
            try {
                db()->prepare('UPDATE amenities SET name = ?, category = ? WHERE id = ?')->execute([$name, $cat, (int)$id]);
            } catch (PDOException $e) { continue; } // duplicate name
            if ($oldName && $oldName !== $name) {
                // Rename inside every project that uses it
                foreach (db()->query('SELECT id, amenities FROM projects')->fetchAll() as $p) {
                    $list = jd($p['amenities']);
                    if (($k = array_search($oldName, $list, true)) !== false) {
                        $list[$k] = $name;
                        db()->prepare('UPDATE projects SET amenities = ? WHERE id = ?')->execute([json_encode($list, JSON_UNESCAPED_UNICODE), $p['id']]);
                    }
                }
            }
        }
        flash('Amenities saved.');
    } elseif ($act === 'delete') {
        db()->prepare('DELETE FROM amenities WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('Amenity removed from the master list (projects that already use it keep it).');
    }
    redirect(admin_url('amenities.php'));
}

$all = all_amenities();
$byCat = [];
foreach ($all as $a) $byCat[$a['category']][] = $a;
$cats = array_keys($byCat);
admin_header('Amenities', 'amenities.php');
?>
<p class="muted">This master list appears as checkboxes on every project. The website picks an icon automatically from the name.</p>

<form method="post" class="panel">
  <?= csrf_field() ?>
  <h2 class="panel__title">Add amenities</h2>
  <div class="row">
    <label class="f"><span>Category</span><input name="category" list="amenCats" value="Lifestyle"></label>
    <label class="f f--2"><span>Names (one per line)</span><textarea name="names" rows="3" placeholder="e.g. Wine Cellar"></textarea></label>
  </div>
  <button class="b b--gold" name="action" value="add">Add</button>
</form>

<form method="post" class="panel">
  <?= csrf_field() ?>
  <div class="panel__head"><h2>Master list · <?= count($all) ?></h2><button class="b b--sm b--gold" name="action" value="save">Save changes</button></div>
  <?php foreach ($byCat as $cat => $items): ?>
    <h3><?= e($cat) ?></h3>
    <div class="amen-edit">
      <?php foreach ($items as $a): ?>
        <div class="amen-edit__row">
          <span class="amen-edit__icon"><?= amenity_icon($a['name']) ?></span>
          <input name="a[<?= $a['id'] ?>][name]" value="<?= e($a['name']) ?>">
          <input name="a[<?= $a['id'] ?>][category]" value="<?= e($a['category']) ?>" list="amenCats" class="amen-edit__cat">
          <button class="x" form="del<?= $a['id'] ?>" onclick="return confirm('Remove “<?= e(addslashes($a['name'])) ?>” from the master list?')" aria-label="Delete">×</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</form>
<?php foreach ($all as $a): ?><form method="post" id="del<?= $a['id'] ?>"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"></form><?php endforeach; ?>
<datalist id="amenCats"><?php foreach ($cats as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
<?php admin_footer(); ?>

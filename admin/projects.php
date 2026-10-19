<?php
require __DIR__ . '/inc/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'toggle_active') {
        db()->prepare('UPDATE projects SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
    } elseif ($act === 'toggle_featured') {
        db()->prepare('UPDATE projects SET featured = 1 - featured WHERE id = ?')->execute([$id]);
    } elseif ($act === 'delete') {
        $q = db()->prepare('SELECT * FROM projects WHERE id = ?');
        $q->execute([$id]);
        if ($p = $q->fetch()) {
            $p = hydrate($p);
            foreach (array_merge([$p['elevation'], $p['thumbnail'], $p['brochure']], $p['banners'], $p['gallery'], array_column($p['floor_plans'], 'image')) as $f) delete_upload($f);
            db()->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
            flash('Project “' . $p['name'] . '” deleted.');
        }
    } elseif ($act === 'duplicate') {
        $q = db()->prepare('SELECT * FROM projects WHERE id = ?');
        $q->execute([$id]);
        if ($p = $q->fetch()) {
            unset($p['id']);
            $p['name'] .= ' (copy)';
            $p['slug'] = unique_slug($p['name']);
            $p['is_active'] = 0;
            $p['brochure'] = '';
            $p['created_at'] = $p['updated_at'] = date('Y-m-d H:i:s');
            $cols = array_keys($p);
            db()->prepare('INSERT INTO projects(' . implode(',', $cols) . ') VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')')->execute(array_values($p));
            flash('Project duplicated as a hidden draft. Edit it and switch it on when ready.');
            redirect(admin_url('project-edit.php?id=' . db()->lastInsertId()));
        }
    } elseif ($act === 'order') {
        foreach ((array)($_POST['order'] ?? []) as $pid => $o) {
            db()->prepare('UPDATE projects SET sort_order = ? WHERE id = ?')->execute([(int)$o, (int)$pid]);
        }
        flash('Display order saved.');
    }
    redirect(admin_url('projects.php'));
}

$projects = get_projects(false);
$leadCounts = db()->query('SELECT project_id, COUNT(*) FROM leads WHERE project_id IS NOT NULL GROUP BY project_id')->fetchAll(PDO::FETCH_KEY_PAIR);

admin_header('Projects', 'projects.php');
?>
<div class="toolbar">
  <p class="muted"><?= count($projects) ?> project<?= count($projects) === 1 ? '' : 's' ?> · Featured projects appear on the home page.</p>
  <a class="b b--gold" href="<?= admin_url('project-edit.php') ?>">+ Add new project</a>
</div>

<form method="post" class="panel panel--flush">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="order">
  <table class="tbl tbl--projects">
    <thead><tr><th></th><th>Project</th><th>Location</th><th>Starting</th><th>Leads</th><th>Featured</th><th>Live</th><th>Order</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($projects as $p): ?>
      <tr class="<?= $p['is_active'] ? '' : 'is-off' ?>">
        <td><img class="thumb" src="<?= e(img($p['thumbnail'] ?: $p['elevation'])) ?>" alt="" onerror="this.src='<?= placeholder() ?>'"></td>
        <td>
          <a href="<?= admin_url('project-edit.php?id=' . $p['id']) ?>" class="strong"><?= e($p['name']) ?></a>
          <div class="muted small"><?= e(config_summary($p['configurations'])) ?> · <?= e($p['status']) ?><?= $p['brochure'] ? ' · 📄 brochure' : '' ?></div>
        </td>
        <td><?= e($p['location']) ?></td>
        <td><?= e(fmt_price((int)$p['starting_price'])) ?></td>
        <td><a href="<?= admin_url('leads.php?project=' . $p['id']) ?>"><?= (int)($leadCounts[$p['id']] ?? 0) ?></a></td>
        <td><button class="tog <?= $p['featured'] ? 'is-on' : '' ?>" form="act" name="id" value="<?= $p['id'] ?>" onclick="document.getElementById('actType').value='toggle_featured'" title="Toggle featured"></button></td>
        <td><button class="tog <?= $p['is_active'] ? 'is-on' : '' ?>" form="act" name="id" value="<?= $p['id'] ?>" onclick="document.getElementById('actType').value='toggle_active'" title="Show / hide on website"></button></td>
        <td><input class="order" type="number" name="order[<?= $p['id'] ?>]" value="<?= (int)$p['sort_order'] ?>"></td>
        <td class="actions">
          <a class="b b--sm" href="<?= admin_url('project-edit.php?id=' . $p['id']) ?>">Edit</a>
          <a class="b b--sm b--ghost" href="<?= e(project_url($p)) ?>" target="_blank">View</a>
          <button class="b b--sm b--ghost" form="act" name="id" value="<?= $p['id'] ?>" onclick="document.getElementById('actType').value='duplicate'">Duplicate</button>
          <button class="b b--sm b--danger" form="act" name="id" value="<?= $p['id'] ?>" onclick="if(!confirm('Delete <?= e(addslashes($p['name'])) ?> permanently? Its images and brochure will be removed.'))return false;document.getElementById('actType').value='delete'">Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$projects): ?><tr><td colspan="9" class="muted center">No projects yet — add your first one.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <?php if ($projects): ?><div class="panel__foot"><button class="b b--sm" type="submit">Save display order</button></div><?php endif; ?>
</form>
<form method="post" id="act"><?= csrf_field() ?><input type="hidden" name="action" id="actType"></form>
<?php admin_footer(); ?>

<?php
require __DIR__ . '/inc/auth.php';
require_admin();

$pdo = db();
$stats = [
    'Active projects' => $pdo->query('SELECT COUNT(*) FROM projects WHERE is_active = 1')->fetchColumn(),
    'New leads'       => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn(),
    'Leads today'     => (function () { $q = db()->prepare('SELECT COUNT(*) FROM leads WHERE created_at >= ?'); $q->execute([date('Y-m-d 00:00:00')]); return $q->fetchColumn(); })(),
    'Last 30 days'    => (function () { $q = db()->prepare('SELECT COUNT(*) FROM leads WHERE created_at >= ?'); $q->execute([date('Y-m-d H:i:s', strtotime('-30 days'))]); return $q->fetchColumn(); })(),
];
$recent = $pdo->query('SELECT * FROM leads ORDER BY id DESC LIMIT 8')->fetchAll();
$q = $pdo->prepare("SELECT COALESCE(NULLIF(project, ''), 'General') AS p, COUNT(*) AS n FROM leads WHERE created_at >= ? GROUP BY p ORDER BY n DESC LIMIT 6");
$q->execute([date('Y-m-d H:i:s', strtotime('-30 days'))]);
$byProject = $q->fetchAll();
$maxP = max(array_column($byProject, 'n') ?: [1]);

admin_header('Dashboard', 'index.php');
?>

<div class="stats">
  <?php foreach ($stats as $k => $v): ?>
    <div class="stat"><b><?= (int)$v ?></b><span><?= e($k) ?></span></div>
  <?php endforeach; ?>
</div>

<div class="grid2">
  <section class="panel">
    <div class="panel__head"><h2>Recent leads</h2><a href="<?= admin_url('leads.php') ?>" class="b b--sm">View all</a></div>
    <?php if ($recent): ?>
    <table class="tbl">
      <thead><tr><th>Name</th><th>Phone</th><th>Project</th><th>Source</th><th>When</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $l): ?>
        <tr>
          <td><?= e($l['name']) ?> <?= $l['status'] === 'new' ? '<span class="badge badge--new">new</span>' : '' ?></td>
          <td><a href="tel:+91<?= e($l['phone']) ?>"><?= e($l['phone']) ?></a></td>
          <td><?= e($l['project'] ?: '—') ?></td>
          <td><span class="badge"><?= e($l['source']) ?></span></td>
          <td class="muted"><?= e(date('d M, h:i A', strtotime($l['created_at']))) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><p class="muted">No leads yet. They will appear here as soon as someone fills a form.</p><?php endif; ?>
  </section>

  <section class="panel">
    <div class="panel__head"><h2>Leads by project · 30 days</h2></div>
    <?php if ($byProject): foreach ($byProject as $r): ?>
      <div class="bar"><span><?= e($r['p']) ?></span><i style="--w: <?= round($r['n'] / $maxP * 100) ?>%"></i><b><?= (int)$r['n'] ?></b></div>
    <?php endforeach; else: ?><p class="muted">No data yet.</p><?php endif; ?>
    <div class="quick">
      <a class="b b--gold" href="<?= admin_url('project-edit.php') ?>">+ Add new project</a>
      <a class="b" href="<?= admin_url('leads.php?export=1') ?>">Export leads (CSV)</a>
    </div>
  </section>
</div>
<?php admin_footer(); ?>

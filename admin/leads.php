<?php
require __DIR__ . '/inc/auth.php';
require_admin();

const LEAD_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'site-visit' => 'Site visit', 'negotiation' => 'Negotiation', 'booked' => 'Booked', 'lost' => 'Not interested'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'update' && isset(LEAD_STATUSES[$_POST['status'] ?? ''])) {
        db()->prepare('UPDATE leads SET status = ?, notes = ? WHERE id = ?')->execute([$_POST['status'], mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 2000), $id]);
        flash('Lead updated.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
        flash('Lead deleted.');
    }
    redirect(admin_url('leads.php?' . http_build_query(array_intersect_key($_GET, array_flip(['q', 'project', 'source', 'status', 'from', 'to', 'page'])))));
}

// Filters
$where = []; $args = [];
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') { $where[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%"); }
if (!empty($_GET['project'])) { $where[] = 'project_id = ?'; $args[] = (int)$_GET['project']; }
if (!empty($_GET['source'])) { $where[] = 'source = ?'; $args[] = (string)$_GET['source']; }
if (!empty($_GET['status'])) { $where[] = 'status = ?'; $args[] = (string)$_GET['status']; }
if (!empty($_GET['from'])) { $where[] = 'created_at >= ?'; $args[] = $_GET['from'] . ' 00:00:00'; }
if (!empty($_GET['to'])) { $where[] = 'created_at <= ?'; $args[] = $_GET['to'] . ' 23:59:59'; }
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// CSV export (respects filters)
if (!empty($_GET['export'])) {
    $st = db()->prepare('SELECT id, created_at, name, phone, email, project, source, status, visit_date, message, notes, page_url, utm FROM leads' . $sqlWhere . ' ORDER BY id DESC');
    $st->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel-friendly UTF-8
    fputcsv($out, ['ID', 'Date', 'Name', 'Phone', 'Email', 'Project', 'Source', 'Status', 'Site visit', 'Message', 'Notes', 'Page', 'UTM']);
    while ($r = $st->fetch(PDO::FETCH_NUM)) {
        // Neutralise spreadsheet formulas
        $r = array_map(fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v, $r);
        fputcsv($out, $r);
    }
    exit;
}

$per = 50;
$page = max(1, (int)($_GET['page'] ?? 1));
$cnt = db()->prepare('SELECT COUNT(*) FROM leads' . $sqlWhere);
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$st = db()->prepare('SELECT * FROM leads' . $sqlWhere . ' ORDER BY id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per));
$st->execute($args);
$leads = $st->fetchAll();

$projects = db()->query('SELECT id, name FROM projects ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
$sources = db()->query('SELECT DISTINCT source FROM leads ORDER BY source')->fetchAll(PDO::FETCH_COLUMN);
$qs = $_GET; unset($qs['page']);

admin_header('Leads', 'leads.php');
?>
<form class="filters-bar panel" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, phone or email">
  <select name="project"><option value="">All projects</option><?php foreach ($projects as $pid => $pn): ?><option value="<?= $pid ?>" <?= (string)$pid === (string)($_GET['project'] ?? '') ? 'selected' : '' ?>><?= e($pn) ?></option><?php endforeach; ?></select>
  <select name="source"><option value="">All sources</option><?php foreach ($sources as $s): ?><option <?= $s === ($_GET['source'] ?? '') ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
  <select name="status"><option value="">All statuses</option><?php foreach (LEAD_STATUSES as $k => $v): ?><option value="<?= $k ?>" <?= $k === ($_GET['status'] ?? '') ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <input type="date" name="from" value="<?= e($_GET['from'] ?? '') ?>" title="From">
  <input type="date" name="to" value="<?= e($_GET['to'] ?? '') ?>" title="To">
  <button class="b b--sm" type="submit">Filter</button>
  <a class="b b--sm b--ghost" href="<?= admin_url('leads.php') ?>">Reset</a>
  <a class="b b--sm b--gold" href="<?= admin_url('leads.php?' . http_build_query($qs + ['export' => 1])) ?>">⬇ Export CSV</a>
</form>

<p class="muted"><?= $total ?> lead<?= $total === 1 ? '' : 's' ?></p>

<div class="leads">
  <?php foreach ($leads as $l): ?>
    <details class="lead panel">
      <summary>
        <span class="lead__name"><?= e($l['name']) ?><?= $l['status'] === 'new' ? ' <span class="badge badge--new">new</span>' : '' ?></span>
        <a class="lead__phone" href="tel:+91<?= e($l['phone']) ?>" onclick="event.stopPropagation()">+91 <?= e($l['phone']) ?></a>
        <span class="lead__proj"><?= e($l['project'] ?: 'General') ?></span>
        <span class="badge"><?= e($l['source']) ?></span>
        <span class="badge badge--<?= e($l['status']) ?>"><?= e(LEAD_STATUSES[$l['status']] ?? $l['status']) ?></span>
        <span class="muted small"><?= e(date('d M Y, h:i A', strtotime($l['created_at']))) ?></span>
      </summary>
      <div class="lead__body">
        <dl>
          <?php if ($l['email']): ?><dt>Email</dt><dd><a href="mailto:<?= e($l['email']) ?>"><?= e($l['email']) ?></a></dd><?php endif; ?>
          <?php if ($l['visit_date']): ?><dt>Site visit</dt><dd><?= e(date('d M Y', strtotime($l['visit_date']))) ?></dd><?php endif; ?>
          <?php if ($l['message']): ?><dt>Message</dt><dd><?= nl2br(e($l['message'])) ?></dd><?php endif; ?>
          <?php if ($l['page_url']): ?><dt>Page</dt><dd class="small"><?= e($l['page_url']) ?></dd><?php endif; ?>
          <?php if ($l['utm']): ?><dt>Campaign</dt><dd class="small"><?= e($l['utm']) ?></dd><?php endif; ?>
        </dl>
        <div class="lead__actions">
          <a class="b b--sm" href="https://wa.me/91<?= e($l['phone']) ?>?text=<?= rawurlencode('Hi ' . explode(' ', $l['name'])[0] . ', thank you for your interest' . ($l['project'] ? ' in ' . $l['project'] : '') . '. This is ' . setting('site_name') . '.') ?>" target="_blank">WhatsApp</a>
        </div>
        <form method="post" class="lead__form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $l['id'] ?>">
          <select name="status"><?php foreach (LEAD_STATUSES as $k => $v): ?><option value="<?= $k ?>" <?= $k === $l['status'] ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
          <textarea name="notes" rows="2" placeholder="Follow-up notes…"><?= e($l['notes']) ?></textarea>
          <button class="b b--sm b--gold" name="action" value="update">Save</button>
          <button class="b b--sm b--danger" name="action" value="delete" onclick="return confirm('Delete this lead?')">Delete</button>
        </form>
      </div>
    </details>
  <?php endforeach; ?>
  <?php if (!$leads): ?><div class="panel muted center">No leads match these filters.</div><?php endif; ?>
</div>

<?php if ($total > $per): ?>
  <nav class="pager">
    <?php for ($i = 1; $i <= (int)ceil($total / $per); $i++): ?>
      <a class="<?= $i === $page ? 'is-active' : '' ?>" href="<?= admin_url('leads.php?' . http_build_query($qs + ['page' => $i])) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
<?php admin_footer(); ?>

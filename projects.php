<?php
require __DIR__ . '/includes/bootstrap.php';

$all     = get_projects();
$opts    = filter_options($all);
$f       = read_filters();
$results = apply_filters($all, $f);

$active = [];
foreach ($f['location'] as $v) $active[] = ['location[]', $v, $v];
foreach ($f['config'] as $v)   $active[] = ['config[]', $v, config_label($v)];
foreach ($f['status'] as $v)   $active[] = ['status[]', $v, $v];
if ($f['budget'] && isset(BUDGETS[$f['budget']])) $active[] = ['budget', $f['budget'], BUDGETS[$f['budget']]];
if ($f['carpet'] && isset(CARPETS[$f['carpet']])) $active[] = ['carpet', $f['carpet'], CARPETS[$f['carpet']]];
if ($f['q']) $active[] = ['q', $f['q'], '“' . $f['q'] . '”'];

$pageTitle   = 'All Projects' . ($f['location'] ? ' in ' . implode(', ', $f['location']) : '');
$pageDesc    = 'Browse luxury residential projects across Mumbai — filter by location, configuration, budget and carpet area.';
$bodyClass   = 'page-projects';
$solidHeader = true;
include __DIR__ . '/includes/header.php';

$checked = fn($k, $v) => in_array($v, $f[$k], true) ? 'checked' : '';
?>

<section class="page-hero page-hero--compact">
  <div class="container">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>">Home</a><span>/</span>All Projects</nav>
    <h1 class="display" data-split>The Collection</h1>
    <p class="page-hero__sub"><?= count($all) ?> curated residences across Mumbai &amp; MMR</p>
  </div>
</section>

<section class="section section--listing">
  <div class="container listing">
    <form class="filters" id="filters" action="<?= url('projects') ?>" method="get">
      <div class="filters__head">
        <h2>Refine</h2>
        <a href="<?= url('projects') ?>" class="filters__reset">Clear all</a>
        <button type="button" class="filters__close" data-filters-close aria-label="Close filters">&times;</button>
      </div>

      <label class="filters__search">
        <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Search project or developer">
      </label>

      <details class="fgroup" open>
        <summary>Location</summary>
        <div class="fgroup__body">
          <?php foreach ($opts['locations'] as $loc => $n): ?>
            <label class="check"><input type="checkbox" name="location[]" value="<?= e($loc) ?>" <?= $checked('location', $loc) ?>><span><?= e($loc) ?></span><em><?= $n ?></em></label>
          <?php endforeach; ?>
        </div>
      </details>

      <details class="fgroup" open>
        <summary>Configuration</summary>
        <div class="fgroup__body fgroup__body--pills">
          <?php foreach ($opts['configs'] as $c): ?>
            <label class="pill"><input type="checkbox" name="config[]" value="<?= e($c) ?>" <?= $checked('config', $c) ?>><span><?= e(config_label($c)) ?></span></label>
          <?php endforeach; ?>
        </div>
      </details>

      <details class="fgroup" open>
        <summary>Budget</summary>
        <div class="fgroup__body">
          <label class="check check--radio"><input type="radio" name="budget" value="" <?= !$f['budget'] ? 'checked' : '' ?>><span>Any budget</span></label>
          <?php foreach (BUDGETS as $k => $v): ?>
            <label class="check check--radio"><input type="radio" name="budget" value="<?= e($k) ?>" <?= $f['budget'] === $k ? 'checked' : '' ?>><span><?= e($v) ?></span></label>
          <?php endforeach; ?>
        </div>
      </details>

      <details class="fgroup" open>
        <summary>Carpet area</summary>
        <div class="fgroup__body">
          <label class="check check--radio"><input type="radio" name="carpet" value="" <?= !$f['carpet'] ? 'checked' : '' ?>><span>Any size</span></label>
          <?php foreach (CARPETS as $k => $v): ?>
            <label class="check check--radio"><input type="radio" name="carpet" value="<?= e($k) ?>" <?= $f['carpet'] === $k ? 'checked' : '' ?>><span><?= e($v) ?></span></label>
          <?php endforeach; ?>
        </div>
      </details>

      <details class="fgroup">
        <summary>Project status</summary>
        <div class="fgroup__body fgroup__body--pills">
          <?php foreach (STATUSES as $s): ?>
            <label class="pill"><input type="checkbox" name="status[]" value="<?= e($s) ?>" <?= $checked('status', $s) ?>><span><?= e($s) ?></span></label>
          <?php endforeach; ?>
        </div>
      </details>

      <button type="submit" class="btn btn--dark btn--block filters__apply">Show results</button>
    </form>

    <div class="results" id="results">
      <div class="results__bar">
        <button type="button" class="btn btn--line btn--sm results__filter-btn" data-filters-open>
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
          Filters<?= $active ? ' (' . count($active) . ')' : '' ?>
        </button>
        <p class="results__count"><b><?= count($results) ?></b> project<?= count($results) === 1 ? '' : 's' ?> found</p>
        <label class="sort">
          <span>Sort by</span>
          <select name="sort" form="filters" data-sort>
            <?php foreach (SORTS as $k => $v): ?><option value="<?= e($k) ?>" <?= $f['sort'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>

      <?php if ($active): ?>
        <div class="chips">
          <?php foreach ($active as [$k, $v, $label]):
              $q = $_GET;
              if (str_ends_with($k, '[]')) {
                  $kk = substr($k, 0, -2);
                  $q[$kk] = array_values(array_diff((array)($q[$kk] ?? []), [$v]));
              } else {
                  unset($q[$k]);
              } ?>
            <a class="chip" href="<?= url('projects' . ($q ? '?' . http_build_query($q) : '')) ?>"><?= e($label) ?> <span aria-hidden="true">&times;</span></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($results): ?>
        <div class="cards cards--listing">
          <?php foreach ($results as $i => $p) include __DIR__ . '/includes/project-card.php'; ?>
        </div>
      <?php else: ?>
        <div class="empty">
          <h3 class="display">No exact matches — yet.</h3>
          <p>Our advisors have access to off-market and pre-launch inventory. Tell us what you&rsquo;re looking for.</p>
          <div class="empty__actions">
            <button class="btn btn--gold" data-enquire data-source="no-results">Get a personalised shortlist</button>
            <a href="<?= url('projects') ?>" class="btn btn--line"><span>Clear filters</span></a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="filters-backdrop" data-filters-close></div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

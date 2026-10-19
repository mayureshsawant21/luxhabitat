<?php
require __DIR__ . '/inc/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p = null;
if ($id) {
    $q = db()->prepare('SELECT * FROM projects WHERE id = ?');
    $q->execute([$id]);
    $p = $q->fetch();
    if (!$p) { flash('Project not found.', 'err'); redirect(admin_url('projects.php')); }
    $p = hydrate($p);
}
$isNew = !$p;
$p = $p ?? hydrate([
    'id' => 0, 'slug' => '', 'name' => '', 'developer' => '', 'location' => '', 'city' => 'Mumbai', 'address' => '',
    'status' => 'Under Construction', 'possession' => '', 'rera' => '', 'land_parcel' => '', 'towers' => '', 'floors' => '',
    'total_units' => '', 'tagline' => '', 'short_desc' => '', 'overview' => '', 'highlights' => '[]', 'starting_price' => 0,
    'configurations' => '[]', 'amenities' => '[]', 'connectivity' => '[]', 'faqs' => '[]', 'elevation' => '', 'thumbnail' => '',
    'banners' => '[]', 'gallery' => '[]', 'floor_plans' => '[]', 'video_url' => '', 'map_query' => '', 'brochure' => '',
    'featured' => 0, 'is_active' => 1, 'sort_order' => 0, 'meta_title' => '', 'meta_desc' => '',
]);

/* ------------------------------ Save ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf();
    $t = fn($k, $max = 500) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
    $lines = fn($k) => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($_POST[$k] ?? ''))), 'strlen'));
    $old = $p;

    $name = $t('name', 150);
    if ($name === '') {
        flash('Project name is required.', 'err');
        redirect(admin_url('project-edit.php' . ($id ? '?id=' . $id : '')));
    }

    $amount = (float)str_replace(',', '', $t('price_amount', 20));
    $unit = $_POST['price_unit'] ?? 'cr';
    $price = (int)round($amount * ($unit === 'cr' ? 10000000 : ($unit === 'l' ? 100000 : 1)));

    // Configurations
    $configs = [];
    foreach ((array)($_POST['cfg_type'] ?? []) as $i => $type) {
        $type = trim((string)$type);
        if ($type === '') continue;
        $configs[] = [
            'type' => mb_substr($type, 0, 60),
            'carpet_min' => (int)preg_replace('/\D/', '', (string)($_POST['cfg_min'][$i] ?? '')),
            'carpet_max' => (int)preg_replace('/\D/', '', (string)($_POST['cfg_max'][$i] ?? '')),
            'price' => mb_substr(trim((string)($_POST['cfg_price'][$i] ?? '')), 0, 60),
        ];
    }

    // Amenities (checked + new custom ones, which are also added to the master list)
    $amen = array_values(array_unique(array_filter(array_map('trim', (array)($_POST['amen'] ?? [])), 'strlen')));
    $ins = db()->prepare("INSERT OR IGNORE INTO amenities(name, category, sort_order) VALUES(?, 'Other', 999)");
    foreach ($lines('amen_custom') as $a) {
        $a = mb_substr($a, 0, 60);
        $ins->execute([$a]);
        if (!in_array($a, $amen, true)) $amen[] = $a;
    }

    // Connectivity & FAQs
    $conn = [];
    foreach ((array)($_POST['conn_place'] ?? []) as $i => $place) {
        $place = trim((string)$place);
        if ($place === '') continue;
        $conn[] = ['category' => trim((string)($_POST['conn_cat'][$i] ?? '')) ?: 'Nearby', 'place' => mb_substr($place, 0, 100), 'distance' => mb_substr(trim((string)($_POST['conn_dist'][$i] ?? '')), 0, 40)];
    }
    $faqs = [];
    foreach ((array)($_POST['faq_q'] ?? []) as $i => $qq) {
        $qq = trim((string)$qq);
        if ($qq === '') continue;
        $faqs[] = ['q' => mb_substr($qq, 0, 250), 'a' => mb_substr(trim((string)($_POST['faq_a'][$i] ?? '')), 0, 2000)];
    }

    // Media
    $keepList = function (string $k): array {
        return array_values(array_filter(array_map('trim', (array)($_POST[$k] ?? [])), 'strlen'));
    };
    $urlList = function (string $k) use ($lines): array {
        return array_values(array_filter($lines($k), fn($u) => preg_match('#^https?://#', $u)));
    };

    $elevation = $t('elevation_keep');
    if ($f = files_list('elevation_file')) $elevation = save_image($f[0]) ?: $elevation;
    if ($t('elevation_url') && preg_match('#^https?://#', $t('elevation_url'))) $elevation = $t('elevation_url');

    $thumb = $t('thumbnail_keep');
    if ($f = files_list('thumbnail_file')) $thumb = save_image($f[0], 1200) ?: $thumb;

    $banners = $keepList('banners_keep');
    foreach (files_list('banners_files') as $f) if ($s = save_image($f)) $banners[] = $s;
    $banners = array_merge($banners, $urlList('banners_urls'));

    $gallery = $keepList('gallery_keep');
    foreach (files_list('gallery_files') as $f) if ($s = save_image($f, 2000)) $gallery[] = $s;
    $gallery = array_merge($gallery, $urlList('gallery_urls'));

    $plans = [];
    foreach ((array)($_POST['fp_img'] ?? []) as $i => $img) {
        if (trim((string)$img) === '') continue;
        $plans[] = ['image' => trim((string)$img), 'title' => trim((string)($_POST['fp_title'][$i] ?? '')) ?: 'Floor plan'];
    }
    foreach (files_list('fp_files') as $f) {
        if ($s = save_image($f, 2000)) {
            $plans[] = ['image' => $s, 'title' => ucwords(str_replace(['-', '_'], ' ', pathinfo($f['name'], PATHINFO_FILENAME)))];
        }
    }

    // Brochure: new upload > smart-fill upload > keep > remove
    $brochure = $old['brochure'];
    if (!empty($_POST['brochure_remove'])) $brochure = '';
    $pre = $t('brochure_uploaded');
    if ($pre && preg_match('#^uploads/brochures/[a-z0-9\-]+\.pdf$#', $pre) && is_file(ROOT . '/' . $pre)) $brochure = $pre;
    if ($f = files_list('brochure_file')) {
        $s = save_brochure($f[0]);
        if ($s) $brochure = $s; else flash('Brochure must be a PDF file.', 'err');
    }

    $slugIn = slugify($t('slug') ?: $name);
    $data = [
        'slug' => unique_slug($slugIn, $id),
        'name' => $name,
        'developer' => $t('developer', 120), 'location' => $t('location', 80), 'city' => $t('city', 60), 'address' => $t('address', 300),
        'status' => in_array($_POST['status'] ?? '', STATUSES, true) ? $_POST['status'] : 'Under Construction',
        'possession' => $t('possession', 60), 'rera' => $t('rera', 200), 'land_parcel' => $t('land_parcel', 60),
        'towers' => $t('towers', 60), 'floors' => $t('floors', 60), 'total_units' => $t('total_units', 60),
        'tagline' => $t('tagline', 200), 'short_desc' => $t('short_desc', 400), 'overview' => $t('overview', 8000),
        'highlights' => json_encode($lines('highlights'), JSON_UNESCAPED_UNICODE),
        'starting_price' => $price,
        'configurations' => json_encode($configs, JSON_UNESCAPED_UNICODE),
        'amenities' => json_encode($amen, JSON_UNESCAPED_UNICODE),
        'connectivity' => json_encode($conn, JSON_UNESCAPED_UNICODE),
        'faqs' => json_encode($faqs, JSON_UNESCAPED_UNICODE),
        'elevation' => $elevation, 'thumbnail' => $thumb,
        'banners' => json_encode($banners, JSON_UNESCAPED_SLASHES),
        'gallery' => json_encode($gallery, JSON_UNESCAPED_SLASHES),
        'floor_plans' => json_encode($plans, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'video_url' => $t('video_url', 300), 'map_query' => $t('map_query', 300),
        'brochure' => $brochure,
        'featured' => !empty($_POST['featured']) ? 1 : 0,
        'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'meta_title' => $t('meta_title', 160), 'meta_desc' => $t('meta_desc', 320),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($isNew) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $cols = array_keys($data);
        db()->prepare('INSERT INTO projects(' . implode(',', $cols) . ') VALUES (' . rtrim(str_repeat('?,', count($cols)), ',') . ')')->execute(array_values($data));
        $id = (int)db()->lastInsertId();
    } else {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($data)));
        db()->prepare("UPDATE projects SET $set WHERE id = ?")->execute([...array_values($data), $id]);
        // Clean up files that were removed
        $before = array_merge([$old['elevation'], $old['thumbnail'], $old['brochure']], $old['banners'], $old['gallery'], array_column($old['floor_plans'], 'image'));
        $after  = array_merge([$elevation, $thumb, $brochure], $banners, $gallery, array_column($plans, 'image'));
        foreach (array_diff($before, $after) as $gone) delete_upload($gone);
    }
    // Remove smart-fill PDFs that were uploaded but never saved to a project (older than a day)
    $used = db()->query("SELECT brochure FROM projects WHERE brochure != ''")->fetchAll(PDO::FETCH_COLUMN);
    foreach (glob(UPLOAD_DIR . '/brochures/*.pdf') ?: [] as $pdf) {
        if (!in_array('uploads/brochures/' . basename($pdf), $used, true) && filemtime($pdf) < time() - 86400) @unlink($pdf);
    }
    if (empty($_SESSION['admin_flash'])) flash('Project saved. ' . ($data['is_active'] ? 'It is live on the website.' : 'It is hidden from the website.'));
    redirect(admin_url('project-edit.php?id=' . $id));
}

/* ------------------------------ Form ------------------------------ */
$amenByCat = [];
foreach (all_amenities() as $a) $amenByCat[$a['category']][] = $a['name'];
$selectedAmen = $p['amenities'];
// Keep project-specific amenities that are no longer in the master list
$known = array_merge(...array_values($amenByCat ?: [[]]));
foreach ($selectedAmen as $a) if (!in_array($a, $known, true)) $amenByCat['Other'][] = $a;

$priceAmount = ''; $priceUnit = 'cr';
if ($p['starting_price'] >= 10000000) { $priceAmount = rtrim(rtrim(number_format($p['starting_price'] / 10000000, 2, '.', ''), '0'), '.'); }
elseif ($p['starting_price'] > 0) { $priceAmount = rtrim(rtrim(number_format($p['starting_price'] / 100000, 2, '.', ''), '0'), '.'); $priceUnit = 'l'; }

$configs = $p['configurations'] ?: [['type' => '', 'carpet_min' => '', 'carpet_max' => '', 'price' => '']];
$conn = $p['connectivity'] ?: [['category' => 'Transit', 'place' => '', 'distance' => '']];
$faqs = $p['faqs'];
$aiReady = setting('anthropic_api_key') !== '';

admin_header($isNew ? 'Add new project' : 'Edit: ' . $p['name'], 'projects.php');
?>
<form method="post" enctype="multipart/form-data" class="editor" id="projectForm">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
  <input type="hidden" name="brochure_uploaded" id="brochureUploaded" value="">

  <div class="editor__main">

    <!-- Smart fill -->
    <section class="panel smart" id="smart">
      <div class="smart__head">
        <div>
          <h2>✦ Smart fill from e-brochure</h2>
          <p class="muted">Upload the developer’s PDF brochure — we’ll read it and auto-fill the details, configurations, amenities and connectivity below. The PDF is also attached as this project’s downloadable brochure (unlocked for visitors only after they fill the lead form).</p>
        </div>
        <span class="badge <?= $aiReady ? 'badge--ok' : '' ?>"><?= $aiReady ? 'AI extraction on' : 'Basic extraction' ?></span>
      </div>
      <label class="drop" id="brochureDrop">
        <input type="file" accept="application/pdf" id="brochureScan">
        <strong>Drop the brochure PDF here or click to choose</strong>
        <span class="muted small">Up to <?= e(ini_get('upload_max_filesize')) ?> · <?= $aiReady ? 'Claude AI reads text and images, including scanned pages' : 'Reads text-based PDFs. Add an Anthropic API key in Settings for AI extraction (incl. scanned brochures).' ?></span>
      </label>
      <div class="smart__status" id="smartStatus" hidden>
        <div class="progress"><i id="smartBar"></i></div>
        <p id="smartMsg">Reading brochure…</p>
      </div>
      <?php if ($p['brochure']): ?>
        <p class="small muted">Current brochure: <a href="<?= e(BASE . '/admin/brochure.php?id=' . $p['id']) ?>" target="_blank">📄 <?= e(basename($p['brochure'])) ?></a></p>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2 class="panel__title">Basic details</h2>
      <div class="row">
        <label class="f f--2"><span>Project name *</span><input name="name" required value="<?= e($p['name']) ?>" data-fill="name"></label>
        <label class="f"><span>Developer</span><input name="developer" value="<?= e($p['developer']) ?>" data-fill="developer"></label>
      </div>
      <div class="row">
        <label class="f"><span>Location / micro-market</span><input name="location" list="locList" value="<?= e($p['location']) ?>" placeholder="e.g. Worli" data-fill="location"></label>
        <label class="f"><span>City</span><input name="city" value="<?= e($p['city']) ?>" data-fill="city"></label>
        <label class="f"><span>Status</span>
          <select name="status" data-fill="status"><?php foreach (STATUSES as $s): ?><option <?= $p['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
        </label>
      </div>
      <label class="f"><span>Full address</span><input name="address" value="<?= e($p['address']) ?>" data-fill="address"></label>
      <div class="row">
        <label class="f"><span>Possession</span><input name="possession" value="<?= e($p['possession']) ?>" placeholder="e.g. December 2028" data-fill="possession"></label>
        <label class="f f--2"><span>MahaRERA number(s)</span><input name="rera" value="<?= e($p['rera']) ?>" placeholder="P51800000000" data-fill="rera"></label>
      </div>
      <div class="row">
        <label class="f"><span>Land parcel</span><input name="land_parcel" value="<?= e($p['land_parcel']) ?>" placeholder="e.g. 4.5 Acres" data-fill="land_parcel"></label>
        <label class="f"><span>Towers</span><input name="towers" value="<?= e($p['towers']) ?>" data-fill="towers"></label>
        <label class="f"><span>Storeys</span><input name="floors" value="<?= e($p['floors']) ?>" placeholder="e.g. G + 45" data-fill="floors"></label>
        <label class="f"><span>Total units</span><input name="total_units" value="<?= e($p['total_units']) ?>" data-fill="total_units"></label>
      </div>
      <div class="row">
        <label class="f"><span>Starting price</span>
          <span class="price">
            <input name="price_amount" value="<?= e($priceAmount) ?>" inputmode="decimal" placeholder="Leave empty for “Price on Request”" data-fill="price_amount">
            <select name="price_unit" data-fill="price_unit"><option value="cr" <?= $priceUnit === 'cr' ? 'selected' : '' ?>>Crore</option><option value="l" <?= $priceUnit === 'l' ? 'selected' : '' ?>>Lakh</option></select>
          </span>
        </label>
        <label class="f"><span>URL slug</span><input name="slug" value="<?= e($p['slug']) ?>" placeholder="auto from name"></label>
      </div>
      <datalist id="locList"><?php foreach (array_keys(filter_options(get_projects(false))['locations']) as $l): ?><option value="<?= e($l) ?>"><?php endforeach; ?></datalist>
    </section>

    <section class="panel">
      <h2 class="panel__title">Overview</h2>
      <label class="f"><span>Tagline (shown as overview heading)</span><input name="tagline" value="<?= e($p['tagline']) ?>" data-fill="tagline"></label>
      <label class="f"><span>Short description (cards, SEO)</span><textarea name="short_desc" rows="2" data-fill="short_desc"><?= e($p['short_desc']) ?></textarea></label>
      <label class="f"><span>Project overview (blank line = new paragraph)</span><textarea name="overview" rows="7" data-fill="overview"><?= e($p['overview']) ?></textarea></label>
      <label class="f"><span>Key highlights (one per line)</span><textarea name="highlights" rows="5" data-fill="highlights"><?= e(implode("\n", $p['highlights'])) ?></textarea></label>
    </section>

    <section class="panel">
      <h2 class="panel__title">Configurations <small class="muted">RERA carpet area in sq.ft. Leave price empty to show “Price on Request”.</small></h2>
      <div class="rep" id="cfgRep" data-fill="configurations">
        <div class="rep__head rep__row--cfg"><span>Configuration</span><span>Carpet min</span><span>Carpet max</span><span>Price (optional)</span><span></span></div>
        <?php foreach ($configs as $c): ?>
          <div class="rep__row rep__row--cfg">
            <input name="cfg_type[]" value="<?= e($c['type']) ?>" placeholder="e.g. 3 BHK">
            <input name="cfg_min[]" value="<?= e($c['carpet_min'] ?: '') ?>" inputmode="numeric" placeholder="1100">
            <input name="cfg_max[]" value="<?= e($c['carpet_max'] ?: '') ?>" inputmode="numeric" placeholder="1250">
            <input name="cfg_price[]" value="<?= e($c['price'] ?? '') ?>" placeholder="On Request">
            <button type="button" class="x" data-rep-remove aria-label="Remove">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="b b--sm" data-rep-add="cfgTpl" data-rep-target="cfgRep">+ Add configuration</button>
      <template id="cfgTpl"><div class="rep__row rep__row--cfg"><input name="cfg_type[]" placeholder="e.g. 3 BHK"><input name="cfg_min[]" inputmode="numeric" placeholder="1100"><input name="cfg_max[]" inputmode="numeric" placeholder="1250"><input name="cfg_price[]" placeholder="On Request"><button type="button" class="x" data-rep-remove aria-label="Remove">×</button></div></template>
    </section>

    <section class="panel">
      <h2 class="panel__title">Amenities <small class="muted" id="amenCount"><?= count($selectedAmen) ?> selected</small></h2>
      <div class="amen-tools">
        <input type="search" id="amenSearch" placeholder="Search amenities…">
        <button type="button" class="b b--sm b--ghost" data-amen-all="1">Select all</button>
        <button type="button" class="b b--sm b--ghost" data-amen-all="0">Clear</button>
      </div>
      <div class="amen" id="amenBox">
        <?php foreach ($amenByCat as $cat => $names): ?>
          <fieldset class="amen__cat">
            <legend><?= e($cat) ?> <button type="button" class="linkbtn" data-amen-cat>toggle all</button></legend>
            <?php foreach ($names as $n): ?>
              <label class="cb"><input type="checkbox" name="amen[]" value="<?= e($n) ?>" <?= in_array($n, $selectedAmen, true) ? 'checked' : '' ?>><span><?= e($n) ?></span></label>
            <?php endforeach; ?>
          </fieldset>
        <?php endforeach; ?>
      </div>
      <label class="f"><span>Other amenities not in the list (one per line — they’ll be added to the master list)</span><textarea name="amen_custom" rows="2" id="amenCustom"></textarea></label>
    </section>

    <section class="panel">
      <h2 class="panel__title">Media</h2>
      <div class="media-grid">
        <div>
          <h3>Elevation image <small class="muted">left of overview</small></h3>
          <input type="hidden" name="elevation_keep" value="<?= e($p['elevation']) ?>">
          <div class="single-img"><img src="<?= e(img($p['elevation'])) ?>" alt="" data-preview="elevation_file" onerror="this.src='<?= placeholder() ?>'"></div>
          <input type="file" name="elevation_file" accept="image/*" data-preview-input>
          <input class="mt" name="elevation_url" placeholder="…or paste image URL">
        </div>
        <div>
          <h3>Card thumbnail <small class="muted">optional, defaults to elevation</small></h3>
          <input type="hidden" name="thumbnail_keep" value="<?= e($p['thumbnail']) ?>">
          <div class="single-img"><img src="<?= e(img($p['thumbnail'] ?: $p['elevation'])) ?>" alt="" data-preview="thumbnail_file" onerror="this.src='<?= placeholder() ?>'"></div>
          <input type="file" name="thumbnail_file" accept="image/*" data-preview-input>
        </div>
      </div>

      <?php foreach (['banners' => ['Sliding banners', 'Top of project page · landscape, 2400px wide recommended'], 'gallery' => ['Gallery', 'Drag to reorder']] as $key => [$label, $hint]): ?>
        <h3><?= $label ?> <small class="muted"><?= $hint ?></small></h3>
        <div class="tiles" data-sortable>
          <?php foreach ($p[$key] as $img): ?>
            <div class="tile" draggable="true">
              <img src="<?= e(img($img)) ?>" alt="" onerror="this.src='<?= placeholder() ?>'">
              <input type="hidden" name="<?= $key ?>_keep[]" value="<?= e($img) ?>">
              <button type="button" class="x" data-tile-remove aria-label="Remove">×</button>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="row row--media">
          <label class="f"><span>Upload images</span><input type="file" name="<?= $key ?>_files[]" accept="image/*" multiple data-multi-preview></label>
          <label class="f"><span>…or image URLs (one per line)</span><textarea name="<?= $key ?>_urls" rows="2"></textarea></label>
        </div>
      <?php endforeach; ?>

      <h3>Floor plans <small class="muted">blurred until the visitor submits the enquiry form</small></h3>
      <div class="tiles tiles--plans" data-sortable>
        <?php foreach ($p['floor_plans'] as $fp): ?>
          <div class="tile" draggable="true">
            <img src="<?= e(img($fp['image'])) ?>" alt="">
            <input type="hidden" name="fp_img[]" value="<?= e($fp['image']) ?>">
            <input name="fp_title[]" value="<?= e($fp['title']) ?>" class="tile__title">
            <button type="button" class="x" data-tile-remove aria-label="Remove">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <label class="f"><span>Upload floor plans (file name becomes the title — edit after saving)</span><input type="file" name="fp_files[]" accept="image/*" multiple data-multi-preview></label>

      <div class="row">
        <label class="f"><span>Walkthrough video (YouTube link)</span><input name="video_url" value="<?= e($p['video_url']) ?>" placeholder="https://youtu.be/…"></label>
      </div>
    </section>

    <section class="panel">
      <h2 class="panel__title">Connectivity &amp; map</h2>
      <label class="f"><span>Map search (address or “lat,lng”) — used for the map and “Get directions”</span><input name="map_query" value="<?= e($p['map_query']) ?>" placeholder="e.g. Project Name, Worli, Mumbai or 19.0176,72.8162" data-fill="map_query"></label>
      <div class="rep" id="connRep" data-fill="connectivity">
        <div class="rep__head rep__row--conn"><span>Category</span><span>Place</span><span>Distance / time</span><span></span></div>
        <?php foreach ($conn as $c): ?>
          <div class="rep__row rep__row--conn">
            <input name="conn_cat[]" value="<?= e($c['category']) ?>" list="connCats">
            <input name="conn_place[]" value="<?= e($c['place']) ?>" placeholder="e.g. Worli Metro Station">
            <input name="conn_dist[]" value="<?= e($c['distance']) ?>" placeholder="5 mins / 1.2 km">
            <button type="button" class="x" data-rep-remove aria-label="Remove">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="b b--sm" data-rep-add="connTpl" data-rep-target="connRep">+ Add place</button>
      <template id="connTpl"><div class="rep__row rep__row--conn"><input name="conn_cat[]" list="connCats" placeholder="Transit"><input name="conn_place[]" placeholder="Place"><input name="conn_dist[]" placeholder="5 mins"><button type="button" class="x" data-rep-remove aria-label="Remove">×</button></div></template>
      <datalist id="connCats"><option>Transit</option><option>Airport</option><option>Business</option><option>Education</option><option>Healthcare</option><option>Lifestyle</option><option>Nearby</option></datalist>
    </section>

    <section class="panel">
      <h2 class="panel__title">FAQs <small class="muted">optional — great for SEO</small></h2>
      <div class="rep" id="faqRep">
        <?php foreach ($faqs as $fq): ?>
          <div class="rep__row rep__row--faq">
            <input name="faq_q[]" value="<?= e($fq['q']) ?>" placeholder="Question">
            <textarea name="faq_a[]" rows="2" placeholder="Answer"><?= e($fq['a']) ?></textarea>
            <button type="button" class="x" data-rep-remove aria-label="Remove">×</button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="b b--sm" data-rep-add="faqTpl" data-rep-target="faqRep">+ Add FAQ</button>
      <template id="faqTpl"><div class="rep__row rep__row--faq"><input name="faq_q[]" placeholder="Question"><textarea name="faq_a[]" rows="2" placeholder="Answer"></textarea><button type="button" class="x" data-rep-remove aria-label="Remove">×</button></div></template>
    </section>

    <section class="panel">
      <h2 class="panel__title">SEO</h2>
      <label class="f"><span>Meta title <small class="muted">(defaults to “Name — configs in location”)</small></span><input name="meta_title" value="<?= e($p['meta_title']) ?>" maxlength="160"></label>
      <label class="f"><span>Meta description</span><textarea name="meta_desc" rows="2" maxlength="320"><?= e($p['meta_desc']) ?></textarea></label>
    </section>
  </div>

  <aside class="editor__side">
    <div class="panel sticky">
      <h2 class="panel__title">Publish</h2>
      <label class="switch"><input type="checkbox" name="is_active" value="1" <?= $p['is_active'] ? 'checked' : '' ?>><span>Live on website</span></label>
      <label class="switch"><input type="checkbox" name="featured" value="1" <?= $p['featured'] ? 'checked' : '' ?>><span>Featured on home page</span></label>
      <label class="f"><span>Display order (lower first)</span><input type="number" name="sort_order" value="<?= (int)$p['sort_order'] ?>"></label>

      <h3>E-brochure (PDF)</h3>
      <?php if ($p['brochure']): ?>
        <p class="small"><a href="<?= e(BASE . '/admin/brochure.php?id=' . $p['id']) ?>" target="_blank">📄 View current</a></p>
        <label class="cb"><input type="checkbox" name="brochure_remove" value="1"><span>Remove brochure</span></label>
      <?php endif; ?>
      <p class="small muted" id="brochureNote" hidden></p>
      <input type="file" name="brochure_file" accept="application/pdf">

      <button class="b b--gold b--block" type="submit">Save project</button>
      <?php if (!$isNew): ?>
        <a class="b b--ghost b--block" href="<?= e(project_url($p)) ?>" target="_blank">View on website ↗</a>
      <?php endif; ?>
      <a class="b b--ghost b--block" href="<?= admin_url('projects.php') ?>">← All projects</a>
    </div>
  </aside>
</form>
<?php admin_footer([BASE . '/assets/vendor/pdf.min.js']); ?>

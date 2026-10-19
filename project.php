<?php
require __DIR__ . '/includes/bootstrap.php';

$p = get_project_by_slug((string)($_GET['slug'] ?? ''));
if (!$p) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$banners   = $p['banners'] ?: array_filter([$p['elevation']]);
$gallery   = $p['gallery'];
$configs   = $p['configurations'];
$amenities = $p['amenities'];
$conn      = $p['connectivity'];
$faqs      = array_values(array_filter($p['faqs'], fn($x) => !empty($x['q'])));
$plans     = $p['floor_plans'];
$video     = youtube_embed($p['video_url']);
$mapQ      = $p['map_query'] ?: ($p['name'] . ', ' . $p['address'] . ', ' . $p['city']);
$plansOpen = !empty($_SESSION['unlocked'][$p['id']]);
$hasBrochure = $p['brochure'] !== '';
[$cMin, $cMax] = project_carpet_range($configs);

// Similar: same location first, then the rest
$similar = array_filter(get_projects(), fn($x) => $x['id'] !== $p['id']);
usort($similar, fn($a, $b) => ($b['location'] === $p['location']) <=> ($a['location'] === $p['location']));
$similar = array_slice($similar, 0, 3);

$facts = array_filter([
    'Developer'      => $p['developer'],
    'Status'         => $p['status'],
    'Possession'     => $p['possession'],
    'Land parcel'    => $p['land_parcel'],
    'Towers'         => $p['towers'],
    'Storeys'        => $p['floors'],
    'Residences'     => $p['total_units'],
    'Carpet area'    => $cMax ? fmt_area($cMin, $cMax) : '',
    'MahaRERA No.'   => $p['rera'],
]);

$connGroups = [];
foreach ($conn as $c) {
    if (empty($c['place'])) continue;
    $connGroups[$c['category'] ?: 'Nearby'][] = $c;
}

$pageTitle   = $p['meta_title'] ?: $p['name'] . ' — ' . config_summary($configs) . ' in ' . $p['location'];
$pageDesc    = $p['meta_desc'] ?: ($p['short_desc'] ?: $p['tagline']);
$ogImage     = img($banners[0] ?? $p['elevation']);
$bodyClass   = 'page-project';
include __DIR__ . '/includes/header.php';

$projData = ['id' => (int)$p['id'], 'name' => $p['name'], 'image' => img($p['thumbnail'] ?: $p['elevation'])];
$sections = array_filter([
    'overview'  => 'Overview',
    'amenities' => $amenities ? 'Amenities' : '',
    'configurations' => $configs ? 'Configurations' : '',
    'floor-plans' => $plans ? 'Floor Plans' : '',
    'gallery'   => $gallery ? 'Gallery' : '',
    'location'  => 'Location',
    'enquire'   => 'Enquire',
]);
?>
<script>window.LX.project = <?= json_encode($projData) ?>;</script>

<!-- 1. Sliding banners -->
<section class="phero">
  <div class="phero__slides" data-slider data-interval="5500">
    <?php foreach ($banners ?: [''] as $k => $b): ?>
      <figure class="hero__slide <?= $k === 0 ? 'is-active' : '' ?>">
        <img src="<?= e(img($b)) ?>" alt="<?= e($p['name']) ?> — view <?= $k + 1 ?>" <?= $k ? 'loading="lazy"' : 'fetchpriority="high"' ?> onerror="this.onerror=null;this.src=LX.placeholder">
      </figure>
    <?php endforeach; ?>
  </div>
  <div class="hero__shade"></div>
  <div class="phero__content container">
    <nav class="crumbs crumbs--light" aria-label="Breadcrumb"><a href="<?= url('') ?>">Home</a><span>/</span><a href="<?= url('projects') ?>">Projects</a><span>/</span><?= e($p['name']) ?></nav>
    <div class="phero__tags">
      <span class="tag"><?= e($p['status']) ?></span>
      <?php if ($p['possession']): ?><span class="tag tag--ghost">Possession: <?= e($p['possession']) ?></span><?php endif; ?>
    </div>
    <h1 class="display phero__title" data-split><?= e($p['name']) ?></h1>
    <p class="phero__loc"><?= e($p['location']) ?><?= $p['city'] ? ', ' . e($p['city']) : '' ?><?= $p['developer'] ? ' &nbsp;·&nbsp; by ' . e($p['developer']) : '' ?></p>
    <div class="phero__bar">
      <div><small>Configuration</small><b><?= e(config_summary($configs)) ?></b></div>
      <div><small>Starting price</small><b><?= e(fmt_price((int)$p['starting_price'])) ?><?= $p['starting_price'] > 0 ? '<sup>*</sup>' : '' ?></b></div>
      <?php if ($amenities): ?><div><small>Amenities</small><b><?= e(amenity_count_label(count($amenities))) ?></b></div><?php endif; ?>
      <div class="phero__actions">
        <button class="btn btn--gold" data-enquire data-source="enquiry" data-title="Enquire about <?= e($p['name']) ?>">Enquire now</button>
        <?php if ($hasBrochure): ?>
          <button class="btn btn--ghost-light" data-enquire data-source="brochure" data-title="Download e-brochure" data-text="Fill in your details to instantly download the official e-brochure of <?= e($p['name']) ?>." data-button="Download brochure">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v3h16v-3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            E-brochure
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="hero__ui container"><div class="hero__dots" data-slider-dots></div></div>
</section>

<nav class="subnav" id="subnav" aria-label="Project sections">
  <div class="container subnav__inner">
    <span class="subnav__name"><?= e($p['name']) ?></span>
    <ul>
      <?php foreach ($sections as $id => $label): ?><li><a href="#<?= $id ?>"><?= e($label) ?></a></li><?php endforeach; ?>
    </ul>
    <button class="btn btn--gold btn--sm" data-enquire data-source="enquiry">Enquire</button>
  </div>
</nav>

<!-- 2. Elevation (left) + Overview (right) -->
<section class="section overview" id="overview">
  <div class="container overview__grid">
    <div class="overview__media" data-reveal="clip">
      <img src="<?= e(img($p['elevation'])) ?>" alt="<?= e($p['name']) ?> elevation" loading="lazy" onerror="this.onerror=null;this.src=LX.placeholder">
      <span class="overview__note">Artistic impression</span>
    </div>
    <div class="overview__text">
      <p class="eyebrow" data-reveal>Project overview</p>
      <h2 class="display" data-reveal><?= e($p['tagline'] ?: $p['name']) ?></h2>
      <div class="prose" data-reveal>
        <?php foreach (array_filter(preg_split("/\n\s*\n|\r\n\s*\r\n/", $p['overview'])) as $para): ?><p><?= nl2br(e(trim($para))) ?></p><?php endforeach; ?>
      </div>
      <?php if ($facts): ?>
        <dl class="facts" data-reveal>
          <?php foreach ($facts as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
        </dl>
      <?php endif; ?>
      <?php if ($p['highlights']): ?>
        <ul class="highlights" data-reveal>
          <?php foreach ($p['highlights'] as $h): ?><li><?= e($h) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <div class="overview__actions" data-reveal>
        <button class="btn btn--dark" data-enquire data-source="site-visit" data-title="Book a site visit" data-text="Choose a convenient time — we'll arrange a complimentary chauffeured visit." data-button="Book site visit">Book a site visit</button>
        <?php if ($hasBrochure): ?>
          <button class="btn btn--line" data-enquire data-source="brochure" data-title="Download e-brochure" data-text="Fill in your details to instantly download the official e-brochure." data-button="Download brochure"><span>Download brochure</span><i aria-hidden="true">&darr;</i></button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($amenities): ?>
<!-- 3. Amenities -->
<section class="section section--dark amenities" id="amenities">
  <div class="container">
    <div class="section__head">
      <div data-reveal>
        <p class="eyebrow eyebrow--light">Lifestyle amenities</p>
        <h2 class="display"><?= e(amenity_count_label(count($amenities))) ?>, <em>curated for you</em></h2>
      </div>
    </div>
    <ul class="amen-grid <?= count($amenities) > 12 ? 'is-collapsed' : '' ?>" id="amenGrid">
      <?php foreach ($amenities as $i => $a): ?>
        <li data-reveal style="--d: <?= $i % 4 ?>"><?= amenity_icon($a) ?><span><?= e($a) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if (count($amenities) > 12): ?>
      <div class="section__foot"><button class="btn btn--ghost-light" data-amen-toggle>View all <?= count($amenities) ?> amenities</button></div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($configs): ?>
<!-- 4. Configurations -->
<section class="section configs" id="configurations">
  <div class="container">
    <div class="section__head">
      <div data-reveal>
        <p class="eyebrow">Residences</p>
        <h2 class="display">Configurations &amp; <em>pricing</em></h2>
      </div>
      <p class="section__aside" data-reveal>All areas are RERA carpet areas. Prices vary by floor, view and inventory — request the latest price sheet.</p>
    </div>
    <div class="ctable" role="table" aria-label="Configurations">
      <div class="ctable__row ctable__row--head" role="row">
        <span role="columnheader">Configuration</span><span role="columnheader">Carpet area</span><span role="columnheader">Price</span><span role="columnheader"></span>
      </div>
      <?php foreach ($configs as $i => $c): ?>
        <div class="ctable__row" role="row" data-reveal style="--d: <?= $i % 4 ?>">
          <span role="cell" class="ctable__type"><?= e($c['type']) ?></span>
          <span role="cell"><?= e(fmt_area($c['carpet_min'] ?? 0, $c['carpet_max'] ?? 0)) ?></span>
          <span role="cell" class="ctable__price"><?= !empty($c['price']) ? e($c['price']) : 'Price on Request' ?></span>
          <span role="cell"><button class="btn btn--line btn--sm" data-enquire data-source="price" data-title="Price for <?= e($c['type']) ?>" data-text="Get the latest price sheet, payment plan and current offers for <?= e($c['type']) ?> at <?= e($p['name']) ?>." data-button="Get price details"><span>Get price</span></button></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($plans): ?>
<!-- 5. Floor plans (unlocked after enquiry) -->
<section class="section section--ivory plans" id="floor-plans">
  <div class="container">
    <div class="section__head">
      <div data-reveal>
        <p class="eyebrow">Layouts</p>
        <h2 class="display">Floor <em>plans</em></h2>
      </div>
    </div>
    <div class="plans__grid <?= $plansOpen ? 'is-open' : '' ?>">
      <?php foreach ($plans as $i => $fp): ?>
        <figure class="plan" data-reveal style="--d: <?= $i % 3 ?>">
          <?php if ($plansOpen): ?>
            <a href="<?= e(img($fp['image'])) ?>" data-lightbox="plans"><img src="<?= e(img($fp['image'])) ?>" alt="<?= e($fp['title']) ?>" loading="lazy"></a>
          <?php else: ?>
            <button class="plan__lock" data-enquire data-source="floor-plan" data-title="Unlock floor plans" data-text="Share your details to view all floor plans of <?= e($p['name']) ?>." data-button="Unlock floor plans">
              <img src="<?= e(img($fp['image'])) ?>" alt="" loading="lazy">
              <span>🔒 Unlock plan</span>
            </button>
          <?php endif; ?>
          <figcaption><?= e($fp['title']) ?></figcaption>
        </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($gallery): ?>
<!-- 6. Gallery -->
<section class="section gallery" id="gallery">
  <div class="container">
    <div class="section__head">
      <div data-reveal>
        <p class="eyebrow">Gallery</p>
        <h2 class="display">A glimpse <em>inside</em></h2>
      </div>
      <p class="section__aside" data-reveal><?= count($gallery) ?> images · tap to view</p>
    </div>
    <div class="gal">
      <?php foreach ($gallery as $i => $g): ?>
        <a href="<?= e(img($g)) ?>" class="gal__item" data-lightbox="gallery" data-reveal style="--d: <?= $i % 3 ?>">
          <img src="<?= e(img($g)) ?>" alt="<?= e($p['name']) ?> gallery image <?= $i + 1 ?>" loading="lazy" onerror="this.onerror=null;this.src=LX.placeholder">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($video): ?>
<section class="section section--tight video">
  <div class="container">
    <div class="video__frame" data-reveal>
      <iframe src="<?= e($video) ?>" title="<?= e($p['name']) ?> walkthrough" loading="lazy" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- 7. Connectivity + map -->
<section class="section section--ivory location" id="location">
  <div class="container location__grid">
    <div class="location__text">
      <p class="eyebrow" data-reveal>Location advantage</p>
      <h2 class="display" data-reveal>Connected to <em>everything</em></h2>
      <?php if ($p['address']): ?><p class="location__addr" data-reveal><?= e($p['address']) ?></p><?php endif; ?>
      <?php foreach ($connGroups as $cat => $items): ?>
        <div class="conn" data-reveal>
          <h4><?= e($cat) ?></h4>
          <ul>
            <?php foreach ($items as $c): ?><li><span><?= e($c['place']) ?></span><i></i><b><?= e($c['distance']) ?></b></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
      <a class="btn btn--dark" data-reveal href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= rawurlencode($mapQ) ?>" target="_blank" rel="noopener">
        <span>Get directions</span><i aria-hidden="true">&nearr;</i>
      </a>
    </div>
    <div class="location__map" data-reveal="clip">
      <iframe title="Map of <?= e($p['name']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
              src="https://maps.google.com/maps?q=<?= rawurlencode($mapQ) ?>&amp;z=15&amp;output=embed"></iframe>
    </div>
  </div>
</section>

<!-- EMI calculator -->
<section class="section section--tight emi">
  <div class="container emi__grid" data-reveal>
    <div>
      <p class="eyebrow">Plan your purchase</p>
      <h2 class="display">EMI <em>calculator</em></h2>
      <p class="muted">An indicative monthly instalment. Our advisors can connect you with leading banks for pre-approved home loans.</p>
    </div>
    <form class="emi__calc" data-emi oninput="return false">
      <label>Loan amount <output data-out="amt"></output><input type="range" name="amt" min="1000000" max="200000000" step="500000" value="<?= max(5000000, (int)round(($p['starting_price'] ?: 30000000) * 0.8 / 500000) * 500000) ?>"></label>
      <label>Interest rate <output data-out="rate"></output><input type="range" name="rate" min="6" max="13" step="0.05" value="8.5"></label>
      <label>Tenure <output data-out="yrs"></output><input type="range" name="yrs" min="5" max="30" step="1" value="20"></label>
      <div class="emi__result"><small>Monthly EMI</small><b data-out="emi">—</b></div>
    </form>
  </div>
</section>

<?php if ($faqs): ?>
<section class="section section--tight faqs">
  <div class="container faqs__grid">
    <div data-reveal>
      <p class="eyebrow">Good to know</p>
      <h2 class="display">Frequently <em>asked</em></h2>
    </div>
    <div class="faq-list" data-reveal>
      <?php foreach ($faqs as $q): ?>
        <details class="faq"><summary><?= e($q['q']) ?></summary><p><?= nl2br(e($q['a'] ?? '')) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- 8. Project contact form -->
<section class="section section--dark enquire" id="enquire">
  <div class="container enquire__grid">
    <div class="enquire__text" data-reveal>
      <p class="eyebrow eyebrow--light">Enquire</p>
      <h2 class="display">Interested in <em><?= e($p['name']) ?>?</em></h2>
      <p>Get the latest price sheet, inventory availability, payment plans and a complimentary site visit — directly from our advisory desk.</p>
      <ul class="enquire__list">
        <li>Best price, direct from developer</li>
        <li>Priority allotment on preferred floors &amp; views</li>
        <li>Home loan &amp; documentation assistance</li>
      </ul>
      <div class="enquire__contact">
        <a href="<?= e(tel_link()) ?>"><small>Call</small><?= e(setting('phone')) ?></a>
        <a href="<?= e(wa_link('Hi, I am interested in ' . $p['name'] . '.')) ?>" target="_blank" rel="noopener"><small>WhatsApp</small>Chat with us</a>
      </div>
    </div>
    <div class="enquire__form" data-reveal>
      <?php lead_form(['source' => 'project-form', 'project_id' => $p['id'], 'project' => $p['name'], 'button' => 'Request call back', 'visit' => true, 'message' => true, 'id' => 'proj']); ?>
    </div>
  </div>
</section>

<?php if ($p['rera'] || $p['developer']): ?>
<section class="rera">
  <div class="container rera__inner">
    <?php if ($p['rera']): ?><p><b>MahaRERA Registration:</b> <?= e($p['rera']) ?> · Available at <a href="https://maharera.maharashtra.gov.in" target="_blank" rel="noopener">maharera.maharashtra.gov.in</a></p><?php endif; ?>
    <?php if (setting('maharera_agent')): ?><p><b>Channel Partner MahaRERA No.:</b> <?= e(setting('maharera_agent')) ?></p><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($similar): ?>
<section class="section similar">
  <div class="container">
    <div class="section__head">
      <div data-reveal>
        <p class="eyebrow">You may also like</p>
        <h2 class="display">Similar <em>residences</em></h2>
      </div>
      <a href="<?= url('projects') ?>" class="btn btn--line" data-reveal><span>Browse all projects</span><i aria-hidden="true">&rarr;</i></a>
    </div>
    <div class="cards">
      <?php foreach (array_values($similar) as $i => $sp) { $p_backup = $p; $p = $sp; include __DIR__ . '/includes/project-card.php'; $p = $p_backup; } ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="mbar" aria-label="Quick actions">
  <a href="<?= e(tel_link()) ?>">Call</a>
  <a href="<?= e(wa_link('Hi, I am interested in ' . $p['name'] . '.')) ?>" target="_blank" rel="noopener">WhatsApp</a>
  <button data-enquire data-source="enquiry">Enquire</button>
</div>

<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Residence',
    'name' => $p['name'],
    'description' => $pageDesc,
    'image' => $ogImage,
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $p['address'], 'addressLocality' => $p['location'], 'addressRegion' => 'Maharashtra', 'addressCountry' => 'IN'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>

<?php $hideFooterCta = true; include __DIR__ . '/includes/footer.php'; ?>

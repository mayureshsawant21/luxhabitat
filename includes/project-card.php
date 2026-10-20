<?php
/** Renders a project card. Expects $p (hydrated project) and optional $i (index for stagger). */
$amenCount = count($p['amenities']);
$cardImg = $p['thumbnail'] ?: $p['elevation'] ?: ($p['gallery'][0] ?? '');
?>
<article class="card" data-reveal style="--d: <?= (int)(($i ?? 0) % 3) ?>"
  data-id="<?= (int)$p['id'] ?>" data-loc="<?= e($p['location']) ?>" data-status="<?= e($p['status']) ?>"
  data-cfg="<?= e(implode(' ', array_unique(array_map(fn($c) => config_key($c['type'] ?? ''), $p['configurations'])))) ?>"
  data-price="<?= (int)$p['starting_price'] ?>" data-featured="<?= (int)$p['featured'] ?>" data-order="<?= (int)$p['sort_order'] ?>"
  data-carpets="<?= e(implode(',', array_map(fn($c) => (int)($c['carpet_min'] ?? 0) . '-' . ((int)($c['carpet_max'] ?? 0) ?: (int)($c['carpet_min'] ?? 0)), $p['configurations']))) ?>"
  data-text="<?= e(strtolower($p['name'] . ' ' . $p['location'] . ' ' . $p['developer'] . ' ' . $p['city'])) ?>">
  <a href="<?= e(project_url($p)) ?>" class="card__link" aria-label="<?= e($p['name']) ?>">
    <div class="card__media">
      <img src="<?= e(img($cardImg)) ?>" alt="<?= e($p['name']) ?>, <?= e($p['location']) ?>" loading="lazy" onerror="this.onerror=null;this.src=LX.placeholder">
      <span class="card__status"><?= e($p['status']) ?></span>
      <?php if ($amenCount): ?><span class="card__amen"><?= e(amenity_count_label($amenCount)) ?></span><?php endif; ?>
    </div>
    <div class="card__body">
      <p class="card__loc">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z" fill="none" stroke="currentColor" stroke-width="1.4"/><circle cx="12" cy="9.5" r="2.4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
        <?= e($p['location']) ?><?= $p['city'] && $p['city'] !== $p['location'] ? ', ' . e($p['city']) : '' ?>
      </p>
      <h3 class="card__title"><?= e($p['name']) ?></h3>
      <div class="card__meta">
        <div><small>Configuration</small><span><?= e(config_summary($p['configurations'])) ?></span></div>
        <div><small>Starting</small><span class="card__price"><?= e(fmt_price((int)$p['starting_price'])) ?><?= $p['starting_price'] > 0 ? '<sup>*</sup>' : '' ?></span></div>
      </div>
      <span class="card__cta">View project <i aria-hidden="true">&rarr;</i></span>
    </div>
  </a>
</article>

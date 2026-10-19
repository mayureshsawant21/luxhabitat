<?php
/** Shared helpers */

function e($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function load_settings(): array
{
    $rows = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $rows ?: [];
}

function setting(string $key, string $default = ''): string
{
    global $SETTINGS;
    $v = $SETTINGS[$key] ?? '';
    return $v !== '' ? $v : $default;
}

function save_setting(string $key, string $value): void
{
    global $SETTINGS;
    db()->prepare('INSERT OR REPLACE INTO settings(key, value) VALUES(?, ?)')->execute([$key, $value]);
    $SETTINGS[$key] = $value;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if (!PRETTY_URLS) {
        $map = ['projects' => 'projects.php', 'thank-you' => 'thank-you.php', 'contact' => 'contact.php', 'about' => 'about.php', 'privacy-policy' => 'privacy.php'];
        if (preg_match('#^project/([^/?]+)(.*)$#', $path, $m)) {
            $path = 'project.php?slug=' . $m[1] . str_replace('?', '&', $m[2]);
        } else {
            foreach ($map as $k => $v) {
                if ($path === $k || str_starts_with($path, $k . '?')) { $path = $v . substr($path, strlen($k)); break; }
            }
        }
    }
    return BASE . '/' . $path;
}

function project_url(array $p): string
{
    return url('project/' . $p['slug']);
}

/** Resolve an image reference (uploaded path or absolute URL) to a usable src */
function img(?string $src, string $fallback = 'assets/img/placeholder.svg'): string
{
    $src = trim((string)$src);
    if ($src === '') return BASE . '/' . $fallback;
    if (preg_match('#^(https?:)?//#', $src)) return $src;
    return BASE . '/' . ltrim($src, '/');
}

function placeholder(): string
{
    return BASE . '/assets/img/placeholder.svg';
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'project';
}

function unique_slug(string $name, int $ignoreId = 0): string
{
    $base = slugify($name);
    $slug = $base;
    $i = 2;
    $q = db()->prepare('SELECT COUNT(*) FROM projects WHERE slug = ? AND id != ?');
    while (true) {
        $q->execute([$slug, $ignoreId]);
        if (!$q->fetchColumn()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

function jd(?string $json): array
{
    $v = json_decode((string)$json, true);
    return is_array($v) ? $v : [];
}

/** ₹ formatting in Indian units */
function fmt_price(int|float $inr, bool $short = false): string
{
    if ($inr <= 0) return 'Price on Request';
    if ($inr >= 10000000) {
        $v = $inr / 10000000;
        return '₹ ' . rtrim(rtrim(number_format($v, 2), '0'), '.') . ' Cr';
    }
    if ($inr >= 100000) {
        $v = $inr / 100000;
        return '₹ ' . rtrim(rtrim(number_format($v, 2), '0'), '.') . ' L';
    }
    return '₹ ' . number_format($inr);
}

function fmt_area($min, $max): string
{
    $min = (int)$min; $max = (int)$max;
    if (!$min && !$max) return 'On Request';
    if ($min && $max && $min !== $max) return number_format($min) . ' – ' . number_format($max) . ' sq.ft.';
    return number_format($min ?: $max) . ' sq.ft.';
}

/** Normalise a configuration label to a filter key: "2.5 BHK" → "2", "5 BHK Penthouse" → "5", "Studio" → "studio" */
function config_key(string $type): string
{
    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:bhk|bed|rk)/i', $type, $m)) {
        $n = (int)floor((float)$m[1]);
        return (string)min($n, 5);
    }
    $t = strtolower($type);
    foreach (['studio', 'penthouse', 'villa', 'duplex', 'office', 'shop', 'plot'] as $w) {
        if (str_contains($t, $w)) return $w;
    }
    return slugify($type);
}

function config_label(string $key): string
{
    if (ctype_digit($key)) return $key === '5' ? '5+ BHK' : $key . ' BHK';
    return ucfirst($key);
}

/** "2, 3 & 4 BHK" summary from configurations */
function config_summary(array $configs): string
{
    $nums = []; $others = [];
    foreach ($configs as $c) {
        $t = $c['type'] ?? '';
        if (preg_match('/(\d+(?:\.\d+)?)\s*BHK/i', $t, $m)) $nums[] = $m[1] + 0;
        elseif ($t) $others[] = $t;
    }
    $nums = array_values(array_unique($nums));
    sort($nums);
    $parts = [];
    if ($nums) {
        $last = array_pop($nums);
        $parts[] = ($nums ? implode(', ', $nums) . ' & ' : '') . $last . ' BHK';
    }
    $parts = array_merge($parts, array_unique($others));
    return implode(' · ', $parts) ?: 'On Request';
}

function project_carpet_range(array $configs): array
{
    $min = PHP_INT_MAX; $max = 0;
    foreach ($configs as $c) {
        $a = (int)($c['carpet_min'] ?? 0); $b = (int)($c['carpet_max'] ?? 0) ?: $a;
        if ($a) $min = min($min, $a);
        if ($b) $max = max($max, $b);
    }
    return [$min === PHP_INT_MAX ? 0 : $min, $max];
}

function amenity_count_label(int $n): string
{
    if ($n <= 0) return '';
    if ($n < 10) return $n . ' Amenities';
    return (int)(floor($n / 5) * 5) . '+ Amenities';
}

function hydrate(array $p): array
{
    foreach (['highlights', 'configurations', 'amenities', 'connectivity', 'faqs', 'banners', 'gallery', 'floor_plans'] as $k) {
        $p[$k] = jd($p[$k] ?? '[]');
    }
    return $p;
}

function get_projects(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM projects' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY featured DESC, sort_order ASC, id DESC';
    return array_map('hydrate', db()->query($sql)->fetchAll());
}

function get_project_by_slug(string $slug): ?array
{
    $q = db()->prepare('SELECT * FROM projects WHERE slug = ? AND is_active = 1');
    $q->execute([$slug]);
    $p = $q->fetch();
    return $p ? hydrate($p) : null;
}

/* ---------- Filters (used by search bar & all-projects page) ---------- */

const BUDGETS = [
    '0-1'   => 'Under ₹ 1 Cr',
    '1-2'   => '₹ 1 – 2 Cr',
    '2-5'   => '₹ 2 – 5 Cr',
    '5-10'  => '₹ 5 – 10 Cr',
    '10-0'  => '₹ 10 Cr +',
];

const CARPETS = [
    '0-500'     => 'Up to 500 sq.ft.',
    '500-800'   => '500 – 800 sq.ft.',
    '800-1200'  => '800 – 1,200 sq.ft.',
    '1200-2000' => '1,200 – 2,000 sq.ft.',
    '2000-0'    => '2,000 sq.ft. +',
];

const SORTS = [
    'featured'   => 'Featured',
    'price_asc'  => 'Price: Low to High',
    'price_desc' => 'Price: High to Low',
    'area_desc'  => 'Carpet Area: Largest',
    'newest'     => 'Newest First',
];

const STATUSES = ['New Launch', 'Under Construction', 'Ready to Move'];

function filter_options(array $projects): array
{
    $loc = []; $cfg = [];
    foreach ($projects as $p) {
        if ($p['location']) $loc[$p['location']] = ($loc[$p['location']] ?? 0) + 1;
        foreach ($p['configurations'] as $c) {
            if (!empty($c['type'])) $cfg[config_key($c['type'])] = true;
        }
    }
    ksort($loc);
    $cfg = array_keys($cfg);
    usort($cfg, function ($a, $b) {
        $na = ctype_digit($a); $nb = ctype_digit($b);
        if ($na && $nb) return (int)$a <=> (int)$b;
        if ($na) return -1;
        if ($nb) return 1;
        return strcmp($a, $b);
    });
    return ['locations' => $loc, 'configs' => $cfg];
}

function range_parts(string $r, int $mult): array
{
    [$a, $b] = array_pad(explode('-', $r), 2, '0');
    return [(float)$a * $mult, (float)$b * $mult];
}

function apply_filters(array $projects, array $f): array
{
    $out = array_filter($projects, function ($p) use ($f) {
        if (!empty($f['location']) && !in_array($p['location'], $f['location'], true)) return false;
        if (!empty($f['status']) && !in_array($p['status'], $f['status'], true)) return false;
        if (!empty($f['config'])) {
            $keys = array_map(fn($c) => config_key($c['type'] ?? ''), $p['configurations']);
            if (!array_intersect($f['config'], $keys)) return false;
        }
        if (!empty($f['budget']) && isset(BUDGETS[$f['budget']])) {
            [$min, $max] = range_parts($f['budget'], 10000000);
            $price = (int)$p['starting_price'];
            if ($price <= 0) return false;
            if ($price < $min) return false;
            if ($max > 0 && $price > $max) return false;
        }
        if (!empty($f['carpet']) && isset(CARPETS[$f['carpet']])) {
            [$min, $max] = range_parts($f['carpet'], 1);
            $ok = false;
            foreach ($p['configurations'] as $c) {
                $a = (int)($c['carpet_min'] ?? 0); $b = (int)($c['carpet_max'] ?? 0) ?: $a;
                if (!$a && !$b) continue;
                if ($b >= $min && ($max == 0 || $a <= $max)) { $ok = true; break; }
            }
            if (!$ok) return false;
        }
        if (!empty($f['q'])) {
            $hay = strtolower($p['name'] . ' ' . $p['location'] . ' ' . $p['developer'] . ' ' . $p['city']);
            if (!str_contains($hay, strtolower($f['q']))) return false;
        }
        return true;
    });

    $sort = $f['sort'] ?? 'featured';
    usort($out, function ($a, $b) use ($sort) {
        switch ($sort) {
            case 'price_asc':
                $pa = $a['starting_price'] ?: PHP_INT_MAX; $pb = $b['starting_price'] ?: PHP_INT_MAX;
                return $pa <=> $pb;
            case 'price_desc':
                return $b['starting_price'] <=> $a['starting_price'];
            case 'area_desc':
                return project_carpet_range($b['configurations'])[1] <=> project_carpet_range($a['configurations'])[1];
            case 'newest':
                return $b['id'] <=> $a['id'];
            default:
                return [$b['featured'], $a['sort_order']] <=> [$a['featured'], $b['sort_order']];
        }
    });
    return array_values($out);
}

function read_filters(): array
{
    $arr = function ($k) {
        $v = $_GET[$k] ?? [];
        if (!is_array($v)) $v = $v === '' ? [] : [$v];
        return array_values(array_filter(array_map('strval', $v), 'strlen'));
    };
    return [
        'location' => $arr('location'),
        'config'   => $arr('config'),
        'status'   => $arr('status'),
        'budget'   => (string)($_GET['budget'] ?? ''),
        'carpet'   => (string)($_GET['carpet'] ?? ''),
        'sort'     => isset(SORTS[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'featured',
        'q'        => trim((string)($_GET['q'] ?? '')),
    ];
}

/* ---------- Security ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function youtube_embed(string $url): string
{
    if (preg_match('#(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})#', $url, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
    }
    return '';
}

function wa_link(string $text = ''): string
{
    $n = preg_replace('/\D+/', '', setting('whatsapp'));
    return 'https://wa.me/' . $n . ($text ? '?text=' . rawurlencode($text) : '');
}

function tel_link(): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', setting('phone'));
}

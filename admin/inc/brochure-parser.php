<?php
/**
 * Brochure → project data.
 *  - extract_with_claude(): uses the Anthropic Messages API (if an API key is saved in Settings)
 *  - extract_basic(): rule-based fallback that works on the text pulled out of the PDF in the browser
 * Both return the same shape (see brochure_schema()).
 */

const MUMBAI_LOCALITIES = [
    'Worli', 'Lower Parel', 'Prabhadevi', 'Dadar', 'Mahim', 'Matunga', 'Wadala', 'Sewri', 'Byculla', 'Mahalaxmi', 'Tardeo', 'Malabar Hill',
    'Colaba', 'Cuffe Parade', 'Marine Lines', 'Bandra West', 'Bandra East', 'Bandra Kurla Complex', 'BKC', 'Khar West', 'Khar', 'Santacruz West',
    'Santacruz East', 'Santacruz', 'Vile Parle', 'Juhu', 'Andheri West', 'Andheri East', 'Andheri', 'Jogeshwari', 'Goregaon West', 'Goregaon East',
    'Goregaon', 'Malad West', 'Malad East', 'Malad', 'Kandivali West', 'Kandivali East', 'Kandivali', 'Borivali West', 'Borivali East', 'Borivali',
    'Dahisar', 'Mira Road', 'Bhayandar', 'Powai', 'Vikhroli', 'Ghatkopar West', 'Ghatkopar East', 'Ghatkopar', 'Kanjurmarg', 'Bhandup', 'Mulund West',
    'Mulund East', 'Mulund', 'Chembur', 'Kurla', 'Sion', 'Wadala East', 'Thane West', 'Thane East', 'Thane', 'Ghodbunder Road', 'Navi Mumbai',
    'Vashi', 'Nerul', 'Kharghar', 'Panvel', 'Airoli', 'Ghansoli', 'Belapur', 'Ulwe', 'Dombivli', 'Kalyan', 'Virar', 'Vasai', 'Naigaon',
];

function brochure_schema(): array
{
    $str = ['type' => 'string'];
    return [
        'type' => 'object',
        'properties' => [
            'name' => $str, 'developer' => $str, 'tagline' => $str, 'location' => $str, 'city' => $str, 'address' => $str,
            'status' => ['type' => 'string', 'enum' => ['New Launch', 'Under Construction', 'Ready to Move', '']],
            'possession' => $str, 'rera' => $str, 'land_parcel' => $str, 'towers' => $str, 'floors' => $str, 'total_units' => $str,
            'short_desc' => $str, 'overview' => $str,
            'highlights' => ['type' => 'array', 'items' => $str],
            'starting_price_inr' => ['type' => 'integer'],
            'configurations' => ['type' => 'array', 'items' => [
                'type' => 'object',
                'properties' => ['type' => $str, 'carpet_min' => ['type' => 'integer'], 'carpet_max' => ['type' => 'integer']],
                'required' => ['type', 'carpet_min', 'carpet_max'],
                'additionalProperties' => false,
            ]],
            'amenities' => ['type' => 'array', 'items' => $str],
            'other_amenities' => ['type' => 'array', 'items' => $str],
            'connectivity' => ['type' => 'array', 'items' => [
                'type' => 'object',
                'properties' => ['category' => $str, 'place' => $str, 'distance' => $str],
                'required' => ['category', 'place', 'distance'],
                'additionalProperties' => false,
            ]],
            'map_query' => $str,
        ],
        'required' => ['name', 'developer', 'tagline', 'location', 'city', 'address', 'status', 'possession', 'rera', 'land_parcel', 'towers',
            'floors', 'total_units', 'short_desc', 'overview', 'highlights', 'starting_price_inr', 'configurations', 'amenities',
            'other_amenities', 'connectivity', 'map_query'],
        'additionalProperties' => false,
    ];
}

/**
 * Calls Claude with either the PDF itself (preferred: reads images & scanned pages) or the extracted text.
 * Throws RuntimeException on failure so the caller can fall back to extract_basic().
 */
function extract_with_claude(string $apiKey, ?string $pdfPath, string $text, array $amenityNames): array
{
    $system = 'You extract structured data about a residential real-estate project from a developer\'s marketing brochure, '
        . 'for an Indian property listing website run by a channel partner. Use only facts stated or clearly shown in the brochure. '
        . 'When a field is not present, return an empty string, 0, or an empty list — never guess numbers, RERA IDs, prices or dates.';

    $instructions = "Extract the project details from this brochure.\n\n"
        . "Field guidance:\n"
        . "- location: the micro-market / neighbourhood (e.g. \"Worli\", \"Andheri West\"); city: e.g. \"Mumbai\", \"Thane\", \"Navi Mumbai\".\n"
        . "- status: one of New Launch, Under Construction, Ready to Move, or empty if unclear.\n"
        . "- rera: all MahaRERA registration numbers, comma-separated.\n"
        . "- floors: format like \"G + 45\" or \"2B + G + 40\" when given.\n"
        . "- configurations: one entry per unit type (e.g. \"2 BHK\", \"3 BHK Jodi\", \"4 BHK Duplex\"), with RERA carpet area range in sq.ft. "
        . "Convert sq.m to sq.ft (×10.764, rounded). Use 0 when an area is not given.\n"
        . "- starting_price_inr: the lowest stated all-inclusive or starting price in rupees as an integer (₹2.5 Cr = 25000000); 0 if no price is printed.\n"
        . "- tagline: the brochure's main headline or a faithful short version of it.\n"
        . "- short_desc: one sentence (max 25 words) for a listing card.\n"
        . "- overview: two polished paragraphs (separated by a blank line) describing the project, written for a luxury website, based only on brochure content.\n"
        . "- highlights: 4–8 short USPs from the brochure.\n"
        . "- amenities: choose every amenity the brochure mentions from this exact list (copy names exactly; map synonyms, e.g. \"gym\" → \"Gymnasium\"): "
        . implode(' | ', $amenityNames) . "\n"
        . "- other_amenities: amenities mentioned that have no equivalent in that list (short names).\n"
        . "- connectivity: nearby landmarks with travel time or distance as printed; category is one of Transit, Airport, Business, Education, Healthcare, Lifestyle, Nearby.\n"
        . "- map_query: the best Google Maps search string for the site (project name + area + city).";

    $content = [];
    if ($pdfPath) {
        $content[] = ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode((string)file_get_contents($pdfPath))]];
    } else {
        $content[] = ['type' => 'text', 'text' => "<brochure_text>\n" . $text . "\n</brochure_text>"];
    }
    $content[] = ['type' => 'text', 'text' => $instructions];

    $body = [
        'model' => 'claude-opus-5-5',
        'max_tokens' => 16000,
        'system' => $system,
        'messages' => [['role' => 'user', 'content' => $content]],
        'output_config' => ['format' => ['type' => 'json_schema', 'schema' => brochure_schema()]],
        // Re-runs the request on a suitable model server-side if the first model declines
        'fallbacks' => 'default',
    ];

    $call = function (array $body, bool $withFallback) use ($apiKey): array {
        if (!$withFallback) unset($body['fallbacks']);
        $headers = ['content-type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'];
        if ($withFallback) $headers[] = 'anthropic-beta: server-side-fallback-2026-07-01';
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 240,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('Could not reach the Anthropic API: ' . $err);
        return [$code, json_decode($raw, true) ?: []];
    };

    [$code, $res] = $call($body, true);
    if ($code === 400) {
        // e.g. an account without the fallback beta — retry once without it
        [$code, $res] = $call($body, false);
    }
    if ($code !== 200) {
        $msg = $res['error']['message'] ?? ('HTTP ' . $code);
        throw new RuntimeException('AI extraction failed: ' . $msg);
    }
    if (($res['stop_reason'] ?? '') === 'refusal') throw new RuntimeException('AI declined to process this document.');
    if (($res['stop_reason'] ?? '') === 'max_tokens') throw new RuntimeException('AI response was cut off.');

    foreach ($res['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $data = json_decode($block['text'], true);
            if (is_array($data)) return $data;
        }
    }
    throw new RuntimeException('AI returned an unexpected response.');
}

/** Rule-based extraction from plain text */
function extract_basic(string $text, string $filename, array $amenityNames): array
{
    $flat = preg_replace('/[ \t]+/', ' ', $text);
    $one  = preg_replace('/\s+/', ' ', $text);
    $lower = strtolower($one);
    $out = array_fill_keys(['name', 'developer', 'tagline', 'location', 'city', 'address', 'status', 'possession', 'rera', 'land_parcel',
        'towers', 'floors', 'total_units', 'short_desc', 'overview', 'map_query'], '');
    $out += ['highlights' => [], 'starting_price_inr' => 0, 'configurations' => [], 'amenities' => [], 'other_amenities' => [], 'connectivity' => []];

    // Name from file name, e.g. "aurelia-residences-brochure-v3.pdf" → "Aurelia Residences"
    $n = pathinfo($filename, PATHINFO_FILENAME);
    $n = preg_replace('/\b(e[- ]?brochure|brochure|final|v\d+|low ?res|compressed|new|web|digital|\d{4,})\b/i', ' ', str_replace(['_', '-', '.'], ' ', $n));
    $out['name'] = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $n))));

    // RERA numbers (MahaRERA: P + 11 digits)
    if (preg_match_all('/\bP\s?\d{11}\b/', $one, $m)) $out['rera'] = implode(', ', array_unique(array_map(fn($x) => str_replace(' ', '', $x), $m[0])));

    // Location: most frequent known locality
    $best = ''; $bestN = 0;
    foreach (MUMBAI_LOCALITIES as $loc) {
        $c = preg_match_all('/\b' . preg_quote(strtolower($loc), '/') . '\b/', $lower);
        if ($c > $bestN || ($c === $bestN && $c > 0 && strlen($loc) > strlen($best))) { $best = $loc; $bestN = $c; }
    }
    if ($best) {
        $out['location'] = $best === 'BKC' ? 'Bandra Kurla Complex' : $best;
        $out['city'] = preg_match('/thane|ghodbunder/i', $best) ? 'Thane' : (preg_match('/vashi|nerul|kharghar|panvel|airoli|ghansoli|belapur|ulwe|navi/i', $best) ? 'Navi Mumbai' : 'Mumbai');
    }

    // Possession
    if (preg_match('/possession[^.\n]{0,60}?((?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\.?[\s,\'’]*\d{2,4}|q[1-4]\s*\d{4}|\d{4})/i', $one, $m)) {
        $out['possession'] = ucwords(trim($m[1]));
    }
    if (preg_match('/ready[\s-]to[\s-]move|occupation certificate received|\bOC received/i', $one)) $out['status'] = 'Ready to Move';
    elseif (preg_match('/new launch|pre[\s-]?launch|launching/i', $one)) $out['status'] = 'New Launch';
    elseif (preg_match('/under construction|possession/i', $one)) $out['status'] = 'Under Construction';

    if (preg_match('/(\d+(?:\.\d+)?)\s*(?:\+\s*)?acres?/i', $one, $m)) $out['land_parcel'] = $m[1] . ' Acres';
    if (preg_match('/(\d+)\s*(?:iconic\s+|residential\s+|luxury\s+)?(?:towers|wings)\b/i', $one, $m)) $out['towers'] = $m[1];
    if (preg_match('/\b((?:\d?B\s*\+\s*)?(?:G|P|Stilt|LG)\s*\+\s*\d+(?:\s*\+\s*\d+)?)\b/i', $one, $m)) $out['floors'] = strtoupper(preg_replace('/\s*\+\s*/', ' + ', $m[1]));
    elseif (preg_match('/(\d{2})\s*(?:-|\s)?(?:storey|storeyed|floors|floor)\b/i', $one, $m)) $out['floors'] = $m[1];
    if (preg_match('/(\d[\d,]*)\s*(?:exclusive\s+|luxury\s+)?(?:residences|apartments|homes|units|flats)\b/i', $one, $m) && (int)str_replace(',', '', $m[1]) > 10) $out['total_units'] = $m[1];
    if (preg_match('/(?i:developed by|a project by|project by|presented by|an offering by|brought to you by)\s+([A-Z][A-Za-z&.]+(?:[ \t]+[A-Z][A-Za-z&.]+){0,3})/', $flat, $m)) $out['developer'] = trim($m[1]);

    // Configurations with carpet areas
    $cfg = [];
    if (preg_match_all('/(\d(?:\.\d)?)\s*BHK\s*(jodi|duplex|penthouse|sky villa|villa|premium|deluxe|grand)?[^\d]{0,40}?(\d{3,4})(?:\s*(?:-|–|to)\s*(\d{3,4}))?\s*(sq\.?\s*ft|sqft|sq\.?\s*m|sqm|sft)/i', $one, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) {
            $type = $m[1] . ' BHK' . (!empty($m[2]) ? ' ' . ucwords(strtolower($m[2])) : '');
            $mul = preg_match('/m/i', str_replace(['sq.ft', 'sqft', 'sft'], '', strtolower($m[5]))) && !preg_match('/ft/i', $m[5]) ? 10.764 : 1;
            $a = (int)round((int)$m[3] * $mul);
            $b = !empty($m[4]) ? (int)round((int)$m[4] * $mul) : $a;
            if ($a < 200 || $a > 15000) continue;
            $cfg[$type] = ['type' => $type, 'carpet_min' => min($a, $cfg[$type]['carpet_min'] ?? PHP_INT_MAX), 'carpet_max' => max($b, $cfg[$type]['carpet_max'] ?? 0)];
        }
    }
    if (!$cfg && preg_match_all('/(\d(?:\.\d)?)\s*(?:&|and|,)?\s*(?:(\d(?:\.\d)?)\s*(?:&|and|,)\s*)?(?:(\d(?:\.\d)?)\s*)?BHK/i', $one, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) foreach ([$m[1], $m[2] ?? '', $m[3] ?? ''] as $b) {
            if ($b !== '' && (float)$b <= 8) $cfg[$b . ' BHK'] = ['type' => $b . ' BHK', 'carpet_min' => 0, 'carpet_max' => 0];
        }
    }
    uksort($cfg, 'strnatcmp');
    $out['configurations'] = array_values($cfg);

    // Price
    if (preg_match('/(?:₹|rs\.?|inr)\s*(\d+(?:\.\d+)?)\s*(cr|crore|crores|l|lac|lakh|lakhs)\b/i', $one, $m)) {
        $out['starting_price_inr'] = (int)round((float)$m[1] * (preg_match('/^c/i', $m[2]) ? 10000000 : 100000));
    }

    // Amenities: match names + common synonyms
    $syn = [
        'Gymnasium' => ['gym', 'fitness centre', 'fitness center', 'health club'], 'Swimming Pool' => ['swimming pool', 'lap pool', 'pool deck'],
        'Infinity Pool' => ['infinity pool', 'infinity edge'], 'Kids\' Pool' => ['kids pool', 'kid\'s pool', 'toddler pool', 'children\'s pool'],
        'Children\'s Play Area' => ['play area', 'kids play', 'children play', 'tot lot'], 'Jogging Track' => ['jogging', 'walking track', 'running track'],
        'Yoga & Meditation Deck' => ['yoga', 'meditation'], 'Indoor Games Room' => ['indoor games', 'games room', 'game room'],
        'Landscaped Gardens' => ['landscaped', 'garden'], 'Clubhouse' => ['clubhouse', 'club house'], 'Mini Theatre' => ['theatre', 'theater', 'cinema'],
        '24x7 Security' => ['24x7 security', '24/7 security', 'security'], 'CCTV Surveillance' => ['cctv'], 'Power Backup' => ['power backup', 'dg backup', 'power back-up'],
        'EV Charging Points' => ['ev charging', 'electric vehicle'], 'High-speed Elevators' => ['high speed elevators', 'high-speed lifts', 'elevators', 'lifts'],
        'Car Parking' => ['parking'], 'Multipurpose Hall' => ['multipurpose hall', 'multi-purpose hall', 'society hall'], 'Banquet Hall' => ['banquet'],
        'Co-working Space' => ['co-working', 'coworking', 'work from home', 'business lounge'], 'Spa' => ['spa'], 'Sauna' => ['sauna'], 'Steam Room' => ['steam'],
        'Jacuzzi' => ['jacuzzi', 'hot tub'], 'Pet Park' => ['pet park', 'pet zone', 'pet area'], 'Amphitheatre' => ['amphitheatre', 'amphitheater'],
        'Rainwater Harvesting' => ['rainwater', 'rain water'], 'Sewage Treatment Plant' => ['sewage treatment', 'stp'], 'Fire Fighting System' => ['fire fighting', 'fire-fighting', 'sprinkler'],
        'Video Door Phone' => ['video door'], 'Smart Home Automation' => ['home automation', 'smart home'], 'Grand Entrance Lobby' => ['entrance lobby', 'grand lobby', 'double height lobby', 'triple height lobby'],
        'Tennis Court' => ['tennis'], 'Badminton Court' => ['badminton'], 'Squash Court' => ['squash'], 'Basketball Court' => ['basketball'],
        'Cricket Pitch / Net' => ['cricket'], 'Football Turf' => ['football', 'futsal', 'turf'], 'Table Tennis' => ['table tennis'], 'Billiards / Pool Table' => ['billiard', 'pool table', 'snooker'],
        'Library / Reading Room' => ['library', 'reading room'], 'Creche / Day Care' => ['creche', 'crèche', 'day care', 'daycare'], 'Senior Citizen Area' => ['senior citizen', 'elders'],
        'Barbecue Area' => ['barbecue', 'bbq'], 'Party Lawn' => ['party lawn', 'party deck', 'event lawn'], 'Sky Lounge' => ['sky lounge', 'sky deck'], 'Rooftop Deck' => ['rooftop', 'terrace garden'],
        'Cycling Track' => ['cycling'], 'Aerobics / Zumba Studio' => ['aerobics', 'zumba', 'dance studio'], 'Skating Rink' => ['skating'], 'Retail / Convenience Store' => ['convenience store', 'retail'],
        'Guest Suites' => ['guest suite', 'guest room'], 'Concierge Services' => ['concierge'], 'Organic Waste Converter' => ['organic waste', 'owc'], 'Solar Lighting' => ['solar'],
        'Reflexology Path' => ['reflexology'], 'Water Features' => ['water feature', 'water body', 'fountain'], 'Open Gym' => ['open gym', 'outdoor gym'],
        'Cafeteria' => ['cafeteria', 'café', 'cafe'], 'Lounge' => ['lounge'], 'Gated Community' => ['gated'], 'Intercom' => ['intercom'], 'Visitor Parking' => ['visitor parking'],
        'Multi-level Parking' => ['multi-level parking', 'multilevel parking', 'mlcp', 'podium parking'], 'Drop-off Zone' => ['drop-off', 'drop off'],
        'Earthquake Resistant Structure' => ['earthquake'], 'Butterfly Garden' => ['butterfly'], 'Sensory Garden' => ['sensory'], 'Central Greens' => ['central green', 'central park'],
    ];
    foreach ($amenityNames as $a) {
        $keys = $syn[$a] ?? [strtolower(preg_replace('/\s*\(.*?\)|\s*\/.*$/', '', $a))];
        foreach ($keys as $k) {
            if (preg_match('/\b' . preg_quote($k, '/') . '/i', $lower)) { $out['amenities'][] = $a; break; }
        }
    }

    // Connectivity: "Place – 5 mins" / "Place 2.5 km"
    if (preg_match_all('/([A-Z][A-Za-z.&\'’ ]{2,48}?)\s*[-–—:|•]?\s*(\d+(?:\.\d+)?\s*(?:mins?|minutes|kms?|km))\b/', $flat, $mm, PREG_SET_ORDER)) {
        $seen = [];
        foreach ($mm as $m) {
            $place = trim($m[1], " -–—:|•");
            if (strlen($place) < 3 || isset($seen[strtolower($place)])) continue;
            $seen[strtolower($place)] = true;
            $pl = strtolower($place);
            $cat = match (true) {
                (bool)preg_match('/airport/', $pl) => 'Airport',
                (bool)preg_match('/metro|station|railway|highway|road|link|expressway|flyover|bridge|coastal/', $pl) => 'Transit',
                (bool)preg_match('/school|college|university|academy|institute/', $pl) => 'Education',
                (bool)preg_match('/hospital|clinic|medical/', $pl) => 'Healthcare',
                (bool)preg_match('/mall|market|club|restaurant|hotel|theatre|beach|lake|park/', $pl) => 'Lifestyle',
                (bool)preg_match('/bkc|complex|business|park|it |tech|office|centre|center/', $pl) => 'Business',
                default => 'Nearby',
            };
            $out['connectivity'][] = ['category' => $cat, 'place' => $place, 'distance' => preg_replace('/\s+/', ' ', $m[2])];
            if (count($out['connectivity']) >= 14) break;
        }
    }

    // Overview: first few descriptive sentences
    $sentences = array_map(fn($x) => preg_replace('/\s+/', ' ', trim($x)), preg_split('/(?<=[.!?])\s+|\n+/', $text));
    $good = array_values(array_filter($sentences, fn($s) => strlen($s) > 70 && strlen($s) < 400 && !preg_match('/rera|disclaimer|artistic|subject to|terms|conditions|http|www\.|@|\||\d{5,}/i', $s)));
    if ($good) {
        $out['overview'] = trim(implode(' ', array_slice($good, 0, 3)) . "\n\n" . implode(' ', array_slice($good, 3, 3)));
        $out['short_desc'] = mb_substr($good[0], 0, 200);
    }
    // Tagline: first short, sentence-like line without numbers
    foreach (preg_split('/\n+/', $text) as $line) {
        $line = trim(preg_replace('/\s+/', ' ', $line));
        if (strlen($line) >= 20 && strlen($line) <= 90 && preg_match('/[a-z]/', $line) && !preg_match('/\d|by |www|@|rera|disclaimer/i', $line)) { $out['tagline'] = rtrim($line, '.'); break; }
    }
    if ($out['name'] && $out['location']) $out['map_query'] = $out['name'] . ', ' . $out['location'] . ', ' . ($out['city'] ?: 'Mumbai');

    $out['amenities'] = array_values(array_unique($out['amenities']));
    return $out;
}

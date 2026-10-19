<?php
/**
 * SQLite database: created & seeded automatically on first run.
 * The database file lives in /data (protected by .htaccess).
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    $file  = DATA_DIR . '/luxhabitat.sqlite';
    $fresh = !file_exists($file);

    $pdo = new PDO('sqlite:' . $file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    migrate($pdo);
    if ($fresh) seed($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT
    );
    CREATE TABLE IF NOT EXISTS projects (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        slug           TEXT UNIQUE NOT NULL,
        name           TEXT NOT NULL,
        developer      TEXT DEFAULT '',
        location       TEXT DEFAULT '',
        city           TEXT DEFAULT 'Mumbai',
        address        TEXT DEFAULT '',
        status         TEXT DEFAULT 'Under Construction',
        possession     TEXT DEFAULT '',
        rera           TEXT DEFAULT '',
        land_parcel    TEXT DEFAULT '',
        towers         TEXT DEFAULT '',
        floors         TEXT DEFAULT '',
        total_units    TEXT DEFAULT '',
        tagline        TEXT DEFAULT '',
        short_desc     TEXT DEFAULT '',
        overview       TEXT DEFAULT '',
        highlights     TEXT DEFAULT '[]',
        starting_price INTEGER DEFAULT 0,
        configurations TEXT DEFAULT '[]',
        amenities      TEXT DEFAULT '[]',
        connectivity   TEXT DEFAULT '[]',
        faqs           TEXT DEFAULT '[]',
        elevation      TEXT DEFAULT '',
        thumbnail      TEXT DEFAULT '',
        banners        TEXT DEFAULT '[]',
        gallery        TEXT DEFAULT '[]',
        floor_plans    TEXT DEFAULT '[]',
        video_url      TEXT DEFAULT '',
        map_query      TEXT DEFAULT '',
        brochure       TEXT DEFAULT '',
        featured       INTEGER DEFAULT 0,
        is_active      INTEGER DEFAULT 1,
        sort_order     INTEGER DEFAULT 0,
        meta_title     TEXT DEFAULT '',
        meta_desc      TEXT DEFAULT '',
        created_at     TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at     TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS leads (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER,
        project    TEXT DEFAULT '',
        name       TEXT NOT NULL,
        phone      TEXT NOT NULL,
        email      TEXT DEFAULT '',
        message    TEXT DEFAULT '',
        source     TEXT DEFAULT 'enquiry',
        visit_date TEXT DEFAULT '',
        page_url   TEXT DEFAULT '',
        utm        TEXT DEFAULT '',
        ip         TEXT DEFAULT '',
        status     TEXT DEFAULT 'new',
        notes      TEXT DEFAULT '',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS banners (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        image      TEXT NOT NULL,
        title      TEXT DEFAULT '',
        subtitle   TEXT DEFAULT '',
        link       TEXT DEFAULT '',
        sort_order INTEGER DEFAULT 0,
        is_active  INTEGER DEFAULT 1
    );
    CREATE TABLE IF NOT EXISTS amenities (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        name       TEXT UNIQUE NOT NULL,
        category   TEXT DEFAULT 'Lifestyle',
        icon       TEXT DEFAULT '',
        sort_order INTEGER DEFAULT 0
    );
    CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at);
    ");
}

function seed(PDO $pdo): void
{
    $set = $pdo->prepare('INSERT OR REPLACE INTO settings(key, value) VALUES(?, ?)');
    $defaults = [
        'site_name'        => 'LuxHabitat Mumbai',
        'tagline'          => 'Curated luxury residences across Mumbai',
        'hero_title'       => 'Find a home that feels like an arrival',
        'hero_subtitle'    => 'Handpicked luxury residences from Mumbai\'s most trusted developers — with zero brokerage and complete guidance.',
        'phone'            => '+91 98200 00000',
        'whatsapp'         => '919820000000',
        'email'            => 'hello@luxhabitatmumbai.com',
        'notify_email'     => 'hello@luxhabitatmumbai.com',
        'address'          => 'Bandra Kurla Complex, Mumbai 400051',
        'maharera_agent'   => '',
        'office_hours'     => 'Mon – Sun · 10:00 AM – 7:00 PM',
        'about_text'       => "LuxHabitat Mumbai is a boutique real-estate advisory and authorised channel partner for Mumbai's leading developers. We shortlist, compare and negotiate on your behalf — so every site visit is worth your time.\n\nFrom first conversation to possession, you get one dedicated advisor, honest comparisons and developer-direct pricing.",
        'stat_1_value'     => '12+',  'stat_1_label' => 'Years of advisory',
        'stat_2_value'     => '60+',  'stat_2_label' => 'Developer partners',
        'stat_3_value'     => '2,500+', 'stat_3_label' => 'Families home',
        'instagram'        => '', 'facebook' => '', 'youtube' => '', 'linkedin' => '',
        'disclaimer'       => 'The content on this website is for information purposes only and does not constitute an offer. Prices, plans, specifications and amenities are subject to change by the developer without notice. Images are artistic impressions. Please verify all details with the developer and on the MahaRERA website before making any purchase decision. We are an authorised channel partner and not the developer.',
        'head_scripts'     => '',
        'thankyou_scripts' => '',
        'webhook_url'      => '',
        'anthropic_api_key'=> '',
        'admin_password'   => password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT),
        'logo'             => '',
    ];
    foreach ($defaults as $k => $v) $set->execute([$k, $v]);

    // Master amenity list
    $ins = $pdo->prepare('INSERT OR IGNORE INTO amenities(name, category, sort_order) VALUES(?, ?, ?)');
    $i = 0;
    foreach (master_amenities() as $cat => $names) {
        foreach ($names as $n) $ins->execute([$n, $cat, $i++]);
    }

    // Home banners
    $b = $pdo->prepare('INSERT INTO banners(image, title, subtitle, link, sort_order) VALUES(?,?,?,?,?)');
    $b->execute(['https://images.unsplash.com/photo-1570168007204-dfb528c6958f?auto=format&fit=crop&w=2400&q=80', 'Live above the city', 'Sea-facing residences in South & Western Mumbai', '', 1]);
    $b->execute(['https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=2400&q=80', 'Interiors that whisper luxury', 'Designer-finished homes, ready to move in', '', 2]);
    $b->execute(['https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=2400&q=80', 'Resort living, every day', '50+ curated amenities in every project', '', 3]);

    // Demo projects (edit or delete these from the admin panel)
    $u = fn($id, $w = 1800) => "https://images.unsplash.com/$id?auto=format&fit=crop&w=$w&q=80";
    $demo = [
        [
            'name' => 'Aurelia Sea Residences', 'developer' => 'Demo Developer', 'location' => 'Worli', 'status' => 'Under Construction',
            'possession' => 'December 2028', 'land_parcel' => '4.5 Acres', 'towers' => '2', 'floors' => 'G + 62', 'total_units' => '380',
            'tagline' => 'Uninterrupted Arabian Sea views from every living room',
            'short_desc' => 'Ultra-luxury sea-facing 3, 4 & 5 BHK residences on Worli Sea Face.',
            'starting_price' => 72500000,
            'configurations' => [['type' => '3 BHK', 'carpet_min' => 1450, 'carpet_max' => 1680, 'price' => ''], ['type' => '4 BHK', 'carpet_min' => 2100, 'carpet_max' => 2450, 'price' => ''], ['type' => '5 BHK Penthouse', 'carpet_min' => 4200, 'carpet_max' => 5100, 'price' => '']],
            'img' => ['photo-1486406146926-c627a92ad1ab', 'photo-1600607687939-ce8a6c25118c', 'photo-1600566753190-17f0baa2a6c3', 'photo-1613490493576-7fde63acd811', 'photo-1484154218962-a197022b5858'],
            'featured' => 1, 'amen' => 32,
        ],
        [
            'name' => 'The Banyan Court', 'developer' => 'Demo Developer', 'location' => 'Bandra West', 'status' => 'New Launch',
            'possession' => 'June 2029', 'land_parcel' => '2.1 Acres', 'towers' => '1', 'floors' => 'G + 40', 'total_units' => '160',
            'tagline' => 'A private garden address in the heart of Bandra',
            'short_desc' => 'Boutique 2, 3 & 4 BHK residences with a 1-acre landscaped podium.',
            'starting_price' => 48000000,
            'configurations' => [['type' => '2 BHK', 'carpet_min' => 820, 'carpet_max' => 940, 'price' => ''], ['type' => '3 BHK', 'carpet_min' => 1250, 'carpet_max' => 1420, 'price' => ''], ['type' => '4 BHK', 'carpet_min' => 1900, 'carpet_max' => 2150, 'price' => '']],
            'img' => ['photo-1545324418-cc1a3fa10c00', 'photo-1560448204-e02f11c3d0e2', 'photo-1493809842364-78817add7ffb', 'photo-1600210492486-724fe5c67fb0', 'photo-1600573472550-8090b5e0745e'],
            'featured' => 1, 'amen' => 24,
        ],
        [
            'name' => 'Skyline One', 'developer' => 'Demo Developer', 'location' => 'Andheri West', 'status' => 'Ready to Move',
            'possession' => 'Ready', 'land_parcel' => '3 Acres', 'towers' => '3', 'floors' => 'G + 35', 'total_units' => '540',
            'tagline' => 'Move-in ready homes, minutes from the metro',
            'short_desc' => 'Ready-to-move 1, 2 & 3 BHK homes with OC received, near Metro Line 1.',
            'starting_price' => 18500000,
            'configurations' => [['type' => '1 BHK', 'carpet_min' => 450, 'carpet_max' => 520, 'price' => ''], ['type' => '2 BHK', 'carpet_min' => 690, 'carpet_max' => 780, 'price' => ''], ['type' => '3 BHK', 'carpet_min' => 1020, 'carpet_max' => 1150, 'price' => '']],
            'img' => ['photo-1574362848149-11496d93a7c7', 'photo-1502672260266-1c1ef2d93688', 'photo-1484154218962-a197022b5858', 'photo-1560448204-e02f11c3d0e2', 'photo-1600566753190-17f0baa2a6c3'],
            'featured' => 1, 'amen' => 18,
        ],
        [
            'name' => 'Verdant Hills', 'developer' => 'Demo Developer', 'location' => 'Powai', 'status' => 'Under Construction',
            'possession' => 'March 2028', 'land_parcel' => '8 Acres', 'towers' => '4', 'floors' => 'G + 45', 'total_units' => '820',
            'tagline' => 'Lake views, forest air, city convenience',
            'short_desc' => 'Lake-facing 2, 3 & 4 BHK residences beside Powai Lake with a 70,000 sq.ft. clubhouse.',
            'starting_price' => 27500000,
            'configurations' => [['type' => '2 BHK', 'carpet_min' => 760, 'carpet_max' => 850, 'price' => ''], ['type' => '3 BHK', 'carpet_min' => 1100, 'carpet_max' => 1280, 'price' => ''], ['type' => '4 BHK', 'carpet_min' => 1650, 'carpet_max' => 1800, 'price' => '']],
            'img' => ['photo-1515263487990-61b07816b324', 'photo-1600585154340-be6161a56a0c', 'photo-1600596542815-ffad4c1539a9', 'photo-1493809842364-78817add7ffb', 'photo-1613490493576-7fde63acd811'],
            'featured' => 1, 'amen' => 40,
        ],
        [
            'name' => 'Casa Marina', 'developer' => 'Demo Developer', 'location' => 'Juhu', 'status' => 'Under Construction',
            'possession' => 'September 2027', 'land_parcel' => '1.2 Acres', 'towers' => '1', 'floors' => 'G + 22', 'total_units' => '64',
            'tagline' => 'Beach-side duplexes and sky villas in Juhu',
            'short_desc' => 'Limited-edition 4 BHK duplexes & sky villas, two minutes from Juhu Beach.',
            'starting_price' => 125000000,
            'configurations' => [['type' => '4 BHK Duplex', 'carpet_min' => 2800, 'carpet_max' => 3200, 'price' => ''], ['type' => '5 BHK Sky Villa', 'carpet_min' => 5200, 'carpet_max' => 6000, 'price' => '']],
            'img' => ['photo-1512917774080-9991f1c4c750', 'photo-1600607687939-ce8a6c25118c', 'photo-1613490493576-7fde63acd811', 'photo-1600047509807-ba8f99d2cdde', 'photo-1560448204-e02f11c3d0e2'],
            'featured' => 0, 'amen' => 28,
        ],
        [
            'name' => 'Urban Nest Thane', 'developer' => 'Demo Developer', 'location' => 'Thane West', 'status' => 'New Launch',
            'possession' => 'December 2029', 'land_parcel' => '12 Acres', 'towers' => '6', 'floors' => 'G + 38', 'total_units' => '1,400',
            'tagline' => 'Smart, spacious homes in a 12-acre township',
            'short_desc' => 'Township living with 1, 2 & 3 BHK smart homes on Ghodbunder Road.',
            'starting_price' => 9500000,
            'configurations' => [['type' => '1 BHK', 'carpet_min' => 420, 'carpet_max' => 480, 'price' => ''], ['type' => '2 BHK', 'carpet_min' => 640, 'carpet_max' => 720, 'price' => ''], ['type' => '3 BHK', 'carpet_min' => 950, 'carpet_max' => 1040, 'price' => '']],
            'img' => ['photo-1460317442991-0ec209397118', 'photo-1502672260266-1c1ef2d93688', 'photo-1484154218962-a197022b5858', 'photo-1600566753190-17f0baa2a6c3', 'photo-1600585154340-be6161a56a0c'],
            'featured' => 0, 'amen' => 36,
        ],
    ];

    $allAmen = $pdo->query('SELECT name FROM amenities ORDER BY sort_order')->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $pdo->prepare('INSERT INTO projects
        (slug,name,developer,location,city,address,status,possession,land_parcel,towers,floors,total_units,tagline,short_desc,overview,highlights,
         starting_price,configurations,amenities,connectivity,faqs,elevation,thumbnail,banners,gallery,floor_plans,map_query,featured,sort_order,meta_title,meta_desc)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

    foreach ($demo as $i => $p) {
        $img = $p['img'];
        $amen = array_slice($allAmen, ($i * 7) % 20, $p['amen']);
        $overview = "{$p['name']} is a landmark address in {$p['location']}, designed for those who expect more from the place they call home. Thoughtfully planned residences, generous decks and a lifestyle podium come together in a project that balances privacy with connected city living.\n\nEvery residence is planned for natural light and cross-ventilation, with premium finishes, smart-home automation and efficient layouts that maximise usable carpet area.";
        $conn = [
            ['category' => 'Transit',   'place' => 'Nearest Metro Station', 'distance' => '5 mins'],
            ['category' => 'Transit',   'place' => 'Western Express Highway', 'distance' => '10 mins'],
            ['category' => 'Airport',   'place' => 'Mumbai International Airport', 'distance' => '25 mins'],
            ['category' => 'Education', 'place' => 'International Schools', 'distance' => '8 mins'],
            ['category' => 'Healthcare','place' => 'Multi-speciality Hospital', 'distance' => '7 mins'],
            ['category' => 'Lifestyle', 'place' => 'Premium Malls & Dining', 'distance' => '10 mins'],
            ['category' => 'Business',  'place' => 'Bandra Kurla Complex', 'distance' => '20 mins'],
        ];
        $faqs = [
            ['q' => "What is the starting price at {$p['name']}?", 'a' => 'Pricing depends on floor, view and configuration. Share your details and our advisor will send you the latest price sheet and offers.'],
            ['q' => 'Is the project RERA registered?', 'a' => 'Yes. The MahaRERA registration number is shared on request and can be verified on maharera.maharashtra.gov.in.'],
            ['q' => 'Do you charge any brokerage?', 'a' => 'No. As an authorised channel partner, our services are free for home buyers — you pay the developer price.'],
        ];
        $stmt->execute([
            slugify($p['name']), $p['name'], $p['developer'], $p['location'], 'Mumbai', $p['location'] . ', Mumbai',
            $p['status'], $p['possession'], $p['land_parcel'], $p['towers'], $p['floors'], $p['total_units'],
            $p['tagline'], $p['short_desc'], $overview,
            json_encode(['Panoramic views from every residence', 'Triple-height grand entrance lobby', 'Smart-home automation', 'Vastu-compliant layouts', 'Multi-level clubhouse']),
            $p['starting_price'], json_encode($p['configurations']), json_encode(array_values($amen)), json_encode($conn), json_encode($faqs),
            $u($img[0], 1400), $u($img[0], 900),
            json_encode([$u($img[0], 2400), $u($img[3], 2400), $u($img[1], 2400)]),
            json_encode(array_map(fn($x) => $u($x, 1600), $img)),
            json_encode([]),
            $p['name'] . ', ' . $p['location'] . ', Mumbai',
            $p['featured'], $i + 1, '', '',
        ]);
    }
}

<?php
/** Master list of real-estate amenities (seeded into the DB; admin can add more) */

function master_amenities(): array
{
    return [
        'Lifestyle' => [
            'Grand Entrance Lobby', 'Clubhouse', 'Banquet Hall', 'Party Lawn', 'Multipurpose Hall', 'Lounge',
            'Business Centre', 'Co-working Space', 'Library / Reading Room', 'Mini Theatre', 'Cafeteria',
            'Sky Lounge', 'Rooftop Deck', 'Barbecue Area', 'Guest Suites', 'Concierge Services', 'Senior Citizen Area',
        ],
        'Fitness & Wellness' => [
            'Gymnasium', 'Swimming Pool', 'Infinity Pool', 'Kids\' Pool', 'Jacuzzi', 'Spa', 'Sauna', 'Steam Room',
            'Yoga & Meditation Deck', 'Aerobics / Zumba Studio', 'Jogging Track', 'Cycling Track', 'Reflexology Path', 'Open Gym',
        ],
        'Sports' => [
            'Tennis Court', 'Badminton Court', 'Squash Court', 'Basketball Court', 'Cricket Pitch / Net', 'Football Turf',
            'Multipurpose Sports Court', 'Indoor Games Room', 'Table Tennis', 'Billiards / Pool Table', 'Skating Rink', 'Golf Putting Green',
        ],
        'Kids & Family' => [
            'Children\'s Play Area', 'Toddlers\' Zone', 'Creche / Day Care', 'Kids\' Activity Room', 'Pet Park',
        ],
        'Nature & Open Spaces' => [
            'Landscaped Gardens', 'Central Greens', 'Amphitheatre', 'Water Features', 'Sit-out Pavilions', 'Butterfly Garden', 'Sensory Garden',
        ],
        'Convenience' => [
            'High-speed Elevators', 'Car Parking', 'Multi-level Parking', 'EV Charging Points', 'Visitor Parking', 'Retail / Convenience Store',
            'ATM', 'Laundry', 'Drop-off Zone', 'Society Office', 'Service Lifts', 'Smart Home Automation', 'Wi-Fi Enabled Common Areas',
        ],
        'Safety & Sustainability' => [
            '24x7 Security', 'CCTV Surveillance', 'Video Door Phone', 'Intercom', 'Fire Fighting System', 'Power Backup',
            'Rainwater Harvesting', 'Sewage Treatment Plant', 'Solar Lighting', 'Organic Waste Converter', 'Gated Community', 'Earthquake Resistant Structure',
        ],
    ];
}

/** Thin-line SVG icon picked by keyword */
function amenity_icon(string $name): string
{
    $n = strtolower($name);
    $p = [
        'pool|jacuzzi|swim'                         => '<path d="M2 18c2 0 2-1.5 4-1.5S8 18 10 18s2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5"/><path d="M2 21.5c2 0 2-1.5 4-1.5s2 1.5 4 1.5 2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5"/><path d="M8 15V5a2 2 0 0 1 4 0M16 15V5a2 2 0 0 0-4 0M8 9h8M8 12.5h8"/>',
        'gym|fitness'                               => '<path d="M6 7v10M3 9v6M18 7v10M21 9v6M6 12h12"/>',
        'yoga|meditation|spa|sauna|steam|reflex'    => '<circle cx="12" cy="5" r="2"/><path d="M12 7v6M5 11l7 2 7-2M8 21l4-8 4 8M5 21h14"/>',
        'jogging|cycling|track|aerobic|zumba'       => '<circle cx="15" cy="4.5" r="1.8"/><path d="M7 21l3-6 3 2v5M10 15l1.5-5 4 2.5 3-1M11.5 10l-3 .5-2 3"/>',
        'tennis|badminton|squash|court|cricket|football|basket|turf|golf|skating|sports' => '<circle cx="12" cy="12" r="9"/><path d="M5.5 5.5c3 3 3 10 0 13M18.5 5.5c-3 3-3 10 0 13"/>',
        'game|billiard|table tennis'                => '<rect x="3" y="7" width="18" height="10" rx="3"/><path d="M8 10v4M6 12h4M15.5 11h.01M18 13h.01"/>',
        'child|kid|toddler|creche'                  => '<path d="M4 21V9l8-6 8 6v12"/><path d="M9 21v-5a3 3 0 0 1 6 0v5"/><circle cx="12" cy="10" r="1.5"/>',
        'pet'                                       => '<circle cx="7" cy="9" r="1.6"/><circle cx="11" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="18" cy="10" r="1.6"/><path d="M8 17c0-3 2-5 4-5s5 2 5 5-2 3-4 3-5 0-5-3z"/>',
        'garden|green|landscap|butterfly|sensory|pavilion|nature|lawn' => '<path d="M12 21V11"/><path d="M12 11C12 6 8 3 4 3c0 5 3 8 8 8zM12 13c0-4 3-7 8-7 0 4-3 7-8 7z"/>',
        'amphi|theatre|mini theatre'                => '<path d="M3 20h18M5 16h14M7 12h10M9 8h6M12 4v4"/>',
        'water feature'                             => '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/>',
        'club|lounge|banquet|party|hall|cafe|bbq|barbecue|deck|sky' => '<path d="M8 3h8l-1 7a3 3 0 0 1-6 0L8 3zM12 13v8M8 21h8"/>',
        'business|co-working|office|library|reading' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
        'lift|elevator'                             => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 9l3-3 3 3M9 15l3 3 3-3"/>',
        'parking|drop-off'                          => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>',
        'ev|charging|power|solar'                   => '<path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z"/>',
        'security|cctv|gated|intercom|video door|fire|earthquake' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6l8-3z"/><path d="M9 12l2 2 4-4"/>',
        'rain|sewage|waste|organic'                 => '<path d="M4 14a8 8 0 0 0 16 0c0-5-8-11-8-11S4 9 4 14z"/><path d="M9 15l2 2 4-4"/>',
        'smart|wi-fi|wifi'                          => '<path d="M2 8.5a15 15 0 0 1 20 0M5 12a10 10 0 0 1 14 0M8.5 15.5a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1"/>',
        'retail|store|atm|laundry|concierge'        => '<path d="M4 7h16l-1 13H5L4 7zM9 7a3 3 0 0 1 6 0"/>',
        'guest|senior|society'                      => '<circle cx="12" cy="8" r="3.5"/><path d="M5 21a7 7 0 0 1 14 0"/>',
        'lobby|entrance'                            => '<path d="M3 21h18M5 21V5l7-2 7 2v16M10 21v-6h4v6"/>',
    ];
    $path = '<path d="M12 3l2.4 5.6L20 9.3l-4.3 3.8L17 19l-5-3-5 3 1.3-5.9L4 9.3l5.6-.7z"/>';
    foreach ($p as $k => $svg) {
        if (preg_match('/' . $k . '/', $n)) { $path = $svg; break; }
    }
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function all_amenities(): array
{
    return db()->query('SELECT * FROM amenities ORDER BY sort_order, name')->fetchAll();
}

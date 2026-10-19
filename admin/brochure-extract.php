<?php
/**
 * AJAX endpoint for "Smart fill from e-brochure".
 * Receives the PDF (+ text extracted in the browser), stores the PDF as the project brochure,
 * and returns structured project data for the form.
 */
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/brochure-parser.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');
set_time_limit(300);

function out(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), (string)($_SERVER['HTTP_X_CSRF'] ?? ''))) {
    out(['error' => 'Invalid request. Refresh the page and try again.'], 400);
}

$files = files_list('pdf');
if (!$files) out(['error' => upload_error_message('pdf') ?: 'No PDF received.'], 400);
$file = $files[0];
$origName = $file['name'];
$size = (int)$file['size'];

$brochure = save_brochure($file);
if (!$brochure) out(['error' => 'That file does not look like a valid PDF.'], 400);

$text = mb_substr((string)($_POST['text'] ?? ''), 0, 400000);
$amenityNames = array_column(all_amenities(), 'name');
$apiKey = setting('anthropic_api_key');

$engine = 'basic';
$note = '';
$data = null;

if ($apiKey && function_exists('curl_init')) {
    try {
        // Send the whole PDF when it fits comfortably in one request (reads images & scanned pages);
        // otherwise send the extracted text.
        $usePdf = $size <= 20 * 1024 * 1024;
        if (!$usePdf && mb_strlen(trim($text)) < 200) throw new RuntimeException('PDF is too large for AI reading and contains little text.');
        $data = extract_with_claude($apiKey, $usePdf ? ROOT . '/' . $brochure : null, $text, $amenityNames);
        $engine = 'ai';
    } catch (Throwable $e) {
        $note = $e->getMessage() . ' Used basic extraction instead.';
    }
}

if (!$data) {
    if (mb_strlen(trim($text)) < 50) {
        $data = extract_basic('', $origName, []);
        $note = trim($note . ' Very little text could be read from this PDF (it may be image-only). ' . ($apiKey ? '' : 'Add an Anthropic API key in Settings to read scanned brochures.'));
    } else {
        $data = extract_basic($text, $origName, $amenityNames);
    }
}

// Normalise amenity names to the master list (case-insensitive) and keep extras separate
$lowerMap = [];
foreach ($amenityNames as $n) $lowerMap[strtolower($n)] = $n;
$amen = []; $other = array_values(array_filter((array)($data['other_amenities'] ?? []), 'is_string'));
foreach ((array)($data['amenities'] ?? []) as $a) {
    if (!is_string($a)) continue;
    if (isset($lowerMap[strtolower(trim($a))])) $amen[] = $lowerMap[strtolower(trim($a))];
    else $other[] = $a;
}
$data['amenities'] = array_values(array_unique($amen));
$data['other_amenities'] = array_values(array_unique($other));

out(['ok' => true, 'engine' => $engine, 'brochure' => $brochure, 'note' => trim($note), 'data' => $data]);

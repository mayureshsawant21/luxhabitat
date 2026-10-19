<?php
/**
 * Single endpoint for every lead form on the website.
 * Saves the lead, notifies the team, then redirects to the single thank-you page.
 */
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url(''));

$in = fn($k, $max = 255) => mb_substr(trim(strip_tags((string)($_POST[$k] ?? ''))), 0, $max);

// Bots fill the hidden honeypot field — pretend success, store nothing
if ($in('website') !== '') redirect(url('thank-you'));

$name   = $in('name', 80);
$phone  = preg_replace('/\D+/', '', $in('phone', 20));
$email  = filter_var($in('email', 120), FILTER_VALIDATE_EMAIL) ?: '';
$msg    = $in('message', 1000);
$source = preg_replace('/[^a-z0-9\-]/', '', strtolower($in('source', 30))) ?: 'enquiry';
$visit  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $in('visit_date', 10)) ? $in('visit_date', 10) : '';
$pid    = (int)($_POST['project_id'] ?? 0);
$page   = $in('page_url', 300);
$utm    = $in('utm', 300);

if (strlen($phone) === 12 && str_starts_with($phone, '91')) $phone = substr($phone, 2);
if (mb_strlen($name) < 2 || strlen($phone) < 10) {
    $_SESSION['flash_error'] = 'Please enter your name and a valid 10-digit mobile number.';
    redirect($_SERVER['HTTP_REFERER'] ?? url(''));
}

// Simple rate-limit: max 6 submissions per session per 10 minutes
$_SESSION['lead_times'] = array_filter($_SESSION['lead_times'] ?? [], fn($t) => $t > time() - 600);
if (count($_SESSION['lead_times']) >= 6) redirect(url('thank-you'));
$_SESSION['lead_times'][] = time();

$project = null;
if ($pid) {
    $q = db()->prepare('SELECT id, name, slug, brochure FROM projects WHERE id = ?');
    $q->execute([$pid]);
    $project = $q->fetch() ?: null;
}

db()->prepare('INSERT INTO leads(project_id, project, name, phone, email, message, source, visit_date, page_url, utm, ip, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
    ->execute([$project['id'] ?? null, $project['name'] ?? '', $name, $phone, $email, $msg, $source, $visit, $page, $utm, $_SERVER['REMOTE_ADDR'] ?? '', date('Y-m-d H:i:s')]);

// Any enquiry on a project unlocks its floor plans & brochure for this visitor
if ($project) {
    $_SESSION['unlocked'][$project['id']] = true;
}

// ---- Notifications (best-effort; never block the visitor) ----
$lines = [
    'Name'      => $name,
    'Phone'     => '+91 ' . $phone,
    'Email'     => $email ?: '-',
    'Project'   => $project['name'] ?? 'General enquiry',
    'Source'    => $source,
    'Site visit'=> $visit ?: '-',
    'Message'   => $msg ?: '-',
    'Page'      => $page ?: '-',
    'UTM'       => $utm ?: '-',
    'Time'      => date('d M Y, h:i A'),
];
$to = setting('notify_email');
if ($to) {
    $body = '';
    foreach ($lines as $k => $v) $body .= str_pad($k . ':', 12) . $v . "\n";
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $headers = "From: " . setting('site_name') . " <no-reply@$host>\r\nContent-Type: text/plain; charset=UTF-8";
    if ($email) $headers .= "\r\nReply-To: $email";
    @mail($to, 'New lead: ' . $name . ' — ' . ($project['name'] ?? 'Website'), $body, $headers);
}
$hook = setting('webhook_url');
if ($hook && function_exists('curl_init') && preg_match('#^https://#', $hook)) {
    $ch = curl_init($hook);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($lines),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}

$qs = ['src' => $source];
if ($project) $qs['p'] = $project['slug'];
if ($source === 'brochure' && $project && $project['brochure']) $qs['dl'] = $project['id'];
$_SESSION['thanks_name'] = $name;
redirect(url('thank-you?' . http_build_query($qs)));

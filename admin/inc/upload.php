<?php
/** Upload helpers for admin (images are resized with GD when available). */

const IMG_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

/** Normalise $_FILES['x'] (single or multiple) into a list of file arrays */
function files_list(string $field): array
{
    if (empty($_FILES[$field])) return [];
    $f = $_FILES[$field];
    if (!is_array($f['name'])) return $f['error'] === UPLOAD_ERR_OK ? [$f] : [];
    $out = [];
    foreach ($f['name'] as $i => $n) {
        if ($f['error'][$i] !== UPLOAD_ERR_OK) continue;
        $out[] = ['name' => $n, 'tmp_name' => $f['tmp_name'][$i], 'size' => $f['size'][$i], 'error' => 0];
    }
    return $out;
}

function upload_error_message(string $field): string
{
    $e = $_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE;
    if (is_array($e)) $e = max($e);
    return match ($e) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is larger than the server upload limit (' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
        default => '',
    };
}

/** Saves an uploaded image; returns relative path (e.g. uploads/images/2026/10/abc.jpg) or '' */
function save_image(array $file, int $maxW = 2400): string
{
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, IMG_EXT, true)) return '';
    $info = @getimagesize($file['tmp_name']);
    if (!$info) return '';

    $dir = 'uploads/images/' . date('Y/m');
    if (!is_dir(ROOT . '/' . $dir)) mkdir(ROOT . '/' . $dir, 0755, true);
    $base = slugify(pathinfo($file['name'], PATHINFO_FILENAME));
    $name = substr($base, 0, 40) . '-' . bin2hex(random_bytes(4)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    $dest = ROOT . '/' . $dir . '/' . $name;

    [$w, $h, $type] = $info;
    if ($w > $maxW && function_exists('imagecreatetruecolor') && in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
            IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false,
        };
        if ($src) {
            $nh = (int)round($h * $maxW / $w);
            $dst = imagecreatetruecolor($maxW, $nh);
            if ($type !== IMAGETYPE_JPEG) { imagealphablending($dst, false); imagesavealpha($dst, true); }
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $nh, $w, $h);
            $ok = match ($type) {
                IMAGETYPE_JPEG => imagejpeg($dst, $dest, 84),
                IMAGETYPE_PNG  => imagepng($dst, $dest, 7),
                IMAGETYPE_WEBP => imagewebp($dst, $dest, 84),
            };
            imagedestroy($src); imagedestroy($dst);
            if ($ok) return $dir . '/' . $name;
        }
    }
    return move_uploaded_file($file['tmp_name'], $dest) ? $dir . '/' . $name : '';
}

/** Saves an uploaded brochure (PDF) into the protected folder */
function save_brochure(array $file): string
{
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') return '';
    $fh = fopen($file['tmp_name'], 'rb');
    $magic = $fh ? fread($fh, 5) : '';
    if ($fh) fclose($fh);
    if ($magic !== '%PDF-') return '';
    $name = substr(slugify(pathinfo($file['name'], PATHINFO_FILENAME)), 0, 50) . '-' . bin2hex(random_bytes(4)) . '.pdf';
    $dest = UPLOAD_DIR . '/brochures/' . $name;
    $ok = is_uploaded_file($file['tmp_name']) ? move_uploaded_file($file['tmp_name'], $dest) : rename($file['tmp_name'], $dest);
    return $ok ? 'uploads/brochures/' . $name : '';
}

/** Delete a previously uploaded local file (never touches external URLs) */
function delete_upload(?string $path): void
{
    $path = (string)$path;
    if ($path === '' || !str_starts_with($path, 'uploads/')) return;
    $full = realpath(ROOT . '/' . $path);
    $root = realpath(UPLOAD_DIR);
    if ($full && $root && str_starts_with($full, $root) && is_file($full)) @unlink($full);
}

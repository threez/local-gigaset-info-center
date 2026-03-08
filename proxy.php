<?php
/**
 * Image converter proxy — mimics defunct gigaset.net/proxy/image.do
 *
 * Fetches a PNG icon and converts it to the Gigaset internal fnt format
 * (uncompressed 1bpp bitmap) expected by the phone's XHTML-GP renderer.
 *
 * Called by the phone as: GET proxy.php?data=<absolute-image-url>
 * The <meta name="imageproxy"> tag in index.php points here.
 *
 * fnt format (uncompressed):
 *   uint16 LE  width
 *   uint16 LE  height
 *   rows of ceil(width/8) bytes, MSB first per byte, 0=white 1=black
 *
 * @copyright Copyright (c) 2024 Tilman Vogel <tilman.vogel@web.de>
 * AGPL-3.0-or-later — see LICENSE
 */

$data_url = $_GET['data'] ?? '';

if (empty($data_url) || !filter_var($data_url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Bad Request');
}

// Restrict to our own icon directory to prevent open-proxy abuse
$base_url = getenv('BASE_URL') ?: 'http://info.gigaset.net/info';
if (strpos($data_url, $base_url . '/') !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

$img_data = @file_get_contents($data_url);
if ($img_data === false) {
    http_response_code(502);
    exit('Could not fetch image');
}

$src = @imagecreatefromstring($img_data);
if ($src === false) {
    http_response_code(500);
    exit('Invalid image');
}

// Scale to 16x16 (matching object width/height in index.php)
$w = 16;
$h = 16;
$dst = imagecreatetruecolor($w, $h);
// White background (for PNGs with alpha channel)
imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
imagedestroy($src);

// Build fnt: 4-byte header + 1bpp pixel rows
$fnt = pack('vv', $w, $h);
$row_bytes = intdiv($w + 7, 8);

for ($y = 0; $y < $h; $y++) {
    $row = array_fill(0, $row_bytes, 0);
    for ($x = 0; $x < $w; $x++) {
        $rgb  = imagecolorat($dst, $x, $y);
        $luma = 0.299 * (($rgb >> 16) & 0xFF)
              + 0.587 * (($rgb >>  8) & 0xFF)
              + 0.114 * ( $rgb        & 0xFF);
        if ($luma < 128) {
            $row[intdiv($x, 8)] |= (0x80 >> ($x % 8));
        }
    }
    $fnt .= implode('', array_map('chr', $row));
}

imagedestroy($dst);

header('Content-Type: image/fnt');
header('Content-Length: ' . strlen($fnt));
echo $fnt;

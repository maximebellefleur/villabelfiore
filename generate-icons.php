<?php
/**
 * Rooted PWA icon generator — run once, then delete.
 * Generates icon-512.png, icon-192.png, apple-touch-icon.png, favicon-32.png
 * using GD. Design: Deep Moss hexagon badge with white tree inside.
 */

if (!function_exists('imagecreatetruecolor')) {
    die("GD is not available.\n");
}

$outDir = __DIR__ . '/public/assets/images/';

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Draw a flat-top regular hexagon (filled).
 */
function filled_hex(GdImage $img, float $cx, float $cy, float $r, int $color): void {
    $pts = [];
    for ($k = 0; $k < 6; $k++) {
        $a = deg2rad(60 * $k + 30); // flat-top: start at 30°
        $pts[] = (int)round($cx + $r * cos($a));
        $pts[] = (int)round($cy + $r * sin($a));
    }
    imagefilledpolygon($img, $pts, $color);
}

/**
 * Draw a flat-top hexagon outline (thick).
 */
function stroke_hex(GdImage $img, float $cx, float $cy, float $r, int $color, int $thick): void {
    imagesetthickness($img, $thick);
    $pts = [];
    for ($k = 0; $k < 6; $k++) {
        $a = deg2rad(60 * $k + 30);
        $pts[] = (int)round($cx + $r * cos($a));
        $pts[] = (int)round($cy + $r * sin($a));
    }
    imagepolygon($img, $pts, $color);
    imagesetthickness($img, 1);
}

/**
 * Approximate a rounded-corner filled rectangle.
 * Simple: draw a filled rect + 4 filled circles at corners.
 */
function rounded_rect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void {
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
    imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
}

/**
 * Allocate a hex colour string in a GD image.
 */
function hex_color(GdImage $img, string $hex): int {
    $hex = ltrim($hex, '#');
    return imagecolorallocate($img,
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    );
}

// ── Icon render ───────────────────────────────────────────────────────────────

function make_icon(int $size, string $path): void {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    imagealphablending($img, false);

    // Transparent base
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    imagealphablending($img, true);

    $cx = $size / 2;
    $cy = $size / 2;

    // Colors
    $deepMoss   = hex_color($img, '#29402B');
    $midGreen   = hex_color($img, '#3D6642');
    $lightGreen = hex_color($img, '#4CAF50');
    $cream      = hex_color($img, '#F5F0EA');
    $white      = hex_color($img, '#FFFFFF');

    // ── Background: rounded square ───────────────────────────────────────────
    $pad = (int)($size * 0.04);
    $rad = (int)($size * 0.16);
    rounded_rect($img, $pad, $pad, $size - $pad, $size - $pad, $rad, $deepMoss);

    // ── Hexagon outline ──────────────────────────────────────────────────────
    $hexR     = $size * 0.41;
    $thick    = max(1, (int)($size * 0.022));
    filled_hex($img, $cx, $cy, $hexR, $midGreen);
    filled_hex($img, $cx, $cy, $hexR - $thick, $deepMoss);

    // ── Inner corner lines (like the logo's internal geometry) ───────────────
    // Draw two X lines from hex midpoints to centre for the geometric cage feel
    $innerR = $hexR * 0.58;
    $lc = imagecolorallocatealpha($img,
        (int)hexdec('4D'), (int)hexdec('8A'), (int)hexdec('52'), 80); // semi-transparent
    imagesetthickness($img, max(1, (int)($size * 0.013)));
    for ($k = 0; $k < 3; $k++) {
        $a1 = deg2rad(60 * $k + 30);
        $a2 = deg2rad(60 * $k + 30 + 180);
        $x1 = (int)round($cx + $hexR * 0.72 * cos($a1));
        $y1 = (int)round($cy + $hexR * 0.72 * sin($a1));
        $x2 = (int)round($cx + $hexR * 0.72 * cos($a2));
        $y2 = (int)round($cy + $hexR * 0.72 * sin($a2));
        imageline($img, $x1, $y1, $x2, $y2, $lc);
    }
    imagesetthickness($img, 1);

    // ── Tree: trunk ──────────────────────────────────────────────────────────
    $trunkW  = max(2, (int)($size * 0.065));
    $trunkH  = (int)($size * 0.24);
    $trunkY1 = (int)($cy + $size * 0.075);
    $trunkY2 = $trunkY1 + $trunkH;
    imagefilledrectangle($img,
        (int)($cx - $trunkW / 2), $trunkY1,
        (int)($cx + $trunkW / 2), $trunkY2,
        $white
    );

    // ── Tree: roots (two arcs spreading left/right at base) ──────────────────
    $rootW = (int)($size * 0.12);
    imagefilledellipse($img, (int)($cx - $rootW), $trunkY2, (int)($rootW * 1.2), (int)($size * 0.08), $white);
    imagefilledellipse($img, (int)($cx + $rootW), $trunkY2, (int)($rootW * 1.2), (int)($size * 0.08), $white);
    // erase the top half of root ellipses (they should only show below trunk base)
    $eraser = $deepMoss;
    imagefilledrectangle($img,
        (int)($cx - $rootW * 2), $trunkY1,
        (int)($cx + $rootW * 2), $trunkY2 - 1,
        $eraser
    );
    // redraw trunk over the erased area
    imagefilledrectangle($img,
        (int)($cx - $trunkW / 2), $trunkY1,
        (int)($cx + $trunkW / 2), $trunkY2,
        $white
    );

    // ── Tree: canopy — three overlapping circles ──────────────────────────────
    $cCy  = (int)($cy - $size * 0.075);
    $cR1  = (int)($size * 0.185); // main centre circle
    $cR2  = (int)($size * 0.140); // side circles

    imagefilledellipse($img, (int)($cx - $cR2 * 0.75), (int)($cCy + $size * 0.04), $cR2 * 2, $cR2 * 2, $white);
    imagefilledellipse($img, (int)($cx + $cR2 * 0.75), (int)($cCy + $size * 0.04), $cR2 * 2, $cR2 * 2, $white);
    imagefilledellipse($img, (int)$cx, (int)$cCy, $cR1 * 2, $cR1 * 2, $white);

    // Small detail leaves (top mini circles)
    $leafR = max(2, (int)($size * 0.06));
    $leafY = (int)($cCy - $cR1 * 0.75);
    imagefilledellipse($img, (int)$cx,              $leafY,                    $leafR * 2, $leafR * 2, $white);
    imagefilledellipse($img, (int)($cx - $leafR),   $leafY + (int)($leafR * 0.5), $leafR * 2, $leafR * 2, $white);
    imagefilledellipse($img, (int)($cx + $leafR),   $leafY + (int)($leafR * 0.5), $leafR * 2, $leafR * 2, $white);

    imagepng($img, $path, 9);
    imagedestroy($img);
    echo "✓ Generated: $path (" . filesize($path) . " bytes)\n";
}

// ── Generate all sizes ────────────────────────────────────────────────────────

make_icon(512, $outDir . 'icon-512.png');
make_icon(192, $outDir . 'icon-192.png');
make_icon(180, $outDir . 'apple-touch-icon.png');
make_icon(32,  $outDir . 'favicon-32.png');

echo "\nAll icons generated in $outDir\n";

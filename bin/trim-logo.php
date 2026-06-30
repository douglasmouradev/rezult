<?php

declare(strict_types=1);

/**
 * Remove faixas verdes nas bordas e fundo preto do logo PNG.
 * Uso: php bin/trim-logo.php
 */
$path = dirname(__DIR__) . '/public/assets/img/logo-rezult.png';
$img = imagecreatefrompng($path);
if ($img === false) {
    fwrite(STDERR, "Cannot load logo\n");
    exit(1);
}

$w = imagesx($img);
$h = imagesy($img);

$isBackground = static function (int $r, int $g, int $b, int $a): bool {
    if ($a > 100) {
        return true;
    }
    if ($r < 40 && $g < 40 && $b < 40) {
        return true;
    }
    // faixa mint clara (artefato de exportação)
    if ($g > 165 && $r > 130 && $b > 130 && abs($r - $g) < 50) {
        return true;
    }

    return false;
};

$isEdgeArtifactColumn = static function ($image, int $x, int $height) use ($isBackground): bool {
    $green = 0;
    $total = 0;
    for ($y = 0; $y < $height; $y++) {
        $c = imagecolorat($image, $x, $y);
        $a = ($c >> 24) & 0x7F;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($isBackground($r, $g, $b, $a)) {
            continue;
        }
        $total++;
        if ($g > $r + 10 && $g > $b + 10 && $g > 80) {
            $green++;
        }
    }
    if ($total === 0) {
        return true;
    }

    return $green / $total > 0.92;
};

// Bounding box do conteúdo real (R + seta dourada)
$minX = $w;
$minY = $h;
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($img, $x, $y);
        $a = ($c >> 24) & 0x7F;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($isBackground($r, $g, $b, $a)) {
            continue;
        }
        $minX = min($minX, $x);
        $minY = min($minY, $y);
        $maxX = max($maxX, $x);
        $maxY = max($maxY, $y);
    }
}

while ($maxX > $minX && $isEdgeArtifactColumn($img, $maxX, $h)) {
    $maxX--;
}
while ($minX < $maxX && $isEdgeArtifactColumn($img, $minX, $h)) {
    $minX++;
}

// Remove faixa verde residual de 1–2px na borda direita do canvas original
$maxX = max($minX, $maxX - 8);

$pad = 2;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($w - 1, $maxX + $pad);
$maxY = min($h - 1, $maxY + $pad);
$cropW = $maxX - $minX + 1;
$cropH = $maxY - $minY + 1;

$cropped = imagecreatetruecolor($cropW, $cropH);
imagealphablending($cropped, false);
imagesavealpha($cropped, true);
$transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);

for ($y = 0; $y < $cropH; $y++) {
    for ($x = 0; $x < $cropW; $x++) {
        $c = imagecolorat($img, $minX + $x, $minY + $y);
        $a = ($c >> 24) & 0x7F;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($isBackground($r, $g, $b, $a)) {
            imagesetpixel($cropped, $x, $y, $transparent);
            continue;
        }
        $color = imagecolorallocatealpha($cropped, $r, $g, $b, $a);
        imagesetpixel($cropped, $x, $y, $color);
    }
}

imagepng($cropped, $path, 9);
echo "Logo recortado: {$cropW}x{$cropH} -> {$path}\n";

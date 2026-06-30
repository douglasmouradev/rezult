<?php

declare(strict_types=1);

/**
 * Recorta o logo PNG original removendo faixas mint e fundo preto.
 * Uso: php bin/trim-logo.php [arquivo_entrada] [arquivo_saida]
 */
$root = dirname(__DIR__);
$in = $argv[1] ?? $root . '/public/assets/img/logo-rezult.png';
$out = $argv[2] ?? $root . '/public/assets/img/logo-rezult.png';

$img = imagecreatefrompng($in);
if ($img === false) {
    fwrite(STDERR, "Não foi possível carregar: {$in}\n");
    exit(1);
}

$w = imagesx($img);
$h = imagesy($img);

$isPaleMint = static function (int $r, int $g, int $b): bool {
    return $g > 155 && $r > 120 && $b > 115 && abs($r - $g) < 55;
};

$isBlackBg = static function (int $r, int $g, int $b, int $a): bool {
    return $a > 100 || ($r < 45 && $g < 45 && $b < 45);
};

$isLogoPixel = static function (int $r, int $g, int $b, int $a) use ($isPaleMint, $isBlackBg): bool {
    if ($isBlackBg($r, $g, $b, $a) || $isPaleMint($r, $g, $b)) {
        return false;
    }

    return true;
};

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
        if (!$isLogoPixel($r, $g, $b, $a)) {
            continue;
        }
        $minX = min($minX, $x);
        $minY = min($minY, $y);
        $maxX = max($maxX, $x);
        $maxY = max($maxY, $y);
    }
}

// Remove colunas finas isoladas na borda direita (artefato verde)
$columnScore = static function ($image, int $x, int $height) use ($isLogoPixel): int {
    $score = 0;
    for ($y = 0; $y < $height; $y++) {
        $c = imagecolorat($image, $x, $y);
        $a = ($c >> 24) & 0x7F;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($isLogoPixel($r, $g, $b, $a)) {
            $score++;
        }
    }

    return $score;
};

while ($maxX > $minX) {
    $right = $columnScore($img, $maxX, $h);
    $inner = $columnScore($img, $maxX - 1, $h);
    if ($right <= 3 && $right < $inner) {
        $maxX--;
        continue;
    }
    break;
}

$pad = 3;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($w - 1, $maxX + $pad);
$maxY = min($h - 1, $maxY + $pad);
$cropW = $maxX - $minX + 1;
$cropH = $maxY - $minY + 1;

$dst = imagecreatetruecolor($cropW, $cropH);
imagealphablending($dst, false);
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefilledrectangle($dst, 0, 0, $cropW, $cropH, $transparent);

for ($y = 0; $y < $cropH; $y++) {
    for ($x = 0; $x < $cropW; $x++) {
        $c = imagecolorat($img, $minX + $x, $minY + $y);
        $a = ($c >> 24) & 0x7F;
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($isBlackBg($r, $g, $b, $a) || $isPaleMint($r, $g, $b)) {
            imagesetpixel($dst, $x, $y, $transparent);
            continue;
        }
        $color = imagecolorallocatealpha($dst, $r, $g, $b, $a);
        imagesetpixel($dst, $x, $y, $color);
    }
}

imagepng($dst, $out, 9);
echo "Logo limpo: {$cropW}x{$cropH} -> {$out}\n";

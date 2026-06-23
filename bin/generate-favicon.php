<?php

declare(strict_types=1);

/**
 * Gera favicons a partir do logo Rezult em public/assets/img/ (servidos pelo nginx).
 */
$root = dirname(__DIR__);
$srcPath = $root . '/public/assets/img/logo-rezult.png';
$outDir = $root . '/public/assets/img';
$publicDir = $root . '/public';

if (!is_file($srcPath)) {
    fwrite(STDERR, "Logo não encontrado: {$srcPath}\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Extensão GD não disponível.\n");
    exit(1);
}

$src = imagecreatefrompng($srcPath);
if ($src === false) {
    fwrite(STDERR, "Não foi possível ler o PNG do logo.\n");
    exit(1);
}

imagesavealpha($src, true);

$resize = static function ($image, int $size) {
    $w = imagesx($image);
    $h = imagesy($image);
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
    $scale = min($size / $w, $size / $h);
    $nw = (int) round($w * $scale);
    $nh = (int) round($h * $scale);
    $ox = (int) round(($size - $nw) / 2);
    $oy = (int) round(($size - $nh) / 2);
    imagecopyresampled($dst, $image, $ox, $oy, 0, 0, $nw, $nh, $w, $h);
    return $dst;
};

$sizes = [
    'favicon.png' => 32,
    'favicon-16x16.png' => 16,
    'favicon-32x32.png' => 32,
    'apple-touch-icon.png' => 180,
];

foreach ($sizes as $name => $size) {
    $img = $resize($src, $size);
    $path = $outDir . '/' . $name;
    imagepng($img, $path, 9);
    echo "Gerado: {$path}\n";
}

$png32 = $outDir . '/favicon-32x32.png';
$pngData = file_get_contents($png32);
if ($pngData === false) {
    fwrite(STDERR, "Falha ao ler favicon-32x32.png\n");
    exit(1);
}

$ico = pack('vvv', 0, 1, 1);
$ico .= pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($pngData), 6 + 16);
$ico .= $pngData;
file_put_contents($outDir . '/favicon.ico', $ico);
echo "Gerado: {$outDir}/favicon.ico\n";

// Espelha na raiz de public/ e do projeto (pedido automático do navegador em /favicon.ico)
$mirrorNames = ['favicon.ico', 'favicon.png', 'favicon-16x16.png', 'favicon-32x32.png', 'apple-touch-icon.png'];
foreach ($mirrorNames as $name) {
    $from = $outDir . '/' . $name;
    foreach ([$publicDir . '/' . $name, $root . '/' . $name] as $to) {
        if (copy($from, $to)) {
            echo "Copiado: {$to}\n";
        }
    }
}

echo "Concluído.\n";

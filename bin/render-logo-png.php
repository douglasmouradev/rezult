<?php

declare(strict_types=1);

/**
 * Gera PNG do logo SVG para favicons (requer extensão Imagick).
 * Uso: php bin/render-logo-png.php
 */
$root = dirname(__DIR__);
$svgPath = $root . '/public/assets/img/logo-rezult.svg';
$outPath = $root . '/public/assets/img/logo-rezult.png';

if (!extension_loaded('imagick')) {
    fwrite(STDERR, "Imagick não disponível — mantenha logo-rezult.png ou use php bin/trim-logo.php\n");
    exit(0);
}

$im = new Imagick();
$im->setBackgroundColor(new ImagickPixel('transparent'));
$im->readImage($svgPath);
$im->setImageFormat('png32');
$im->resizeImage(280, 280, Imagick::FILTER_LANCZOS, 1, true);
$im->trimImage(0);
$im->setImagePage(0, 0, 0, 0);
$im->writeImage($outPath);
$im->destroy();
echo "PNG gerado via Imagick: {$outPath}\n";

<?php

declare(strict_types=1);

$src = imagecreatefrompng(dirname(__DIR__) . '/public/assets/img/logo-rezult.png');
$w = imagesx($src);
$h = imagesy($src);
$size = 32;
$dst = imagecreatetruecolor($size, $size);
imagealphablending($dst, false);
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
$scale = min($size / $w, $size / $h);
$nw = (int) round($w * $scale);
$nh = (int) round($h * $scale);
imagecopyresampled($dst, $src, (int) (($size - $nw) / 2), (int) (($size - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
ob_start();
imagepng($dst);
echo base64_encode(ob_get_clean());

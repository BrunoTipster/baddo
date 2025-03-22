<?php
/**
 * Funções para manipulação de imagens
 */

function resizeImage($file, $width, $height, $crop = false) {
    list($w, $h) = getimagesize($file);
    $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    if ($crop) {
        $ratio = max($width/$w, $height/$h);
        $h = $height / $ratio;
        $x = ($w - $width / $ratio) / 2;
        $w = $width / $ratio;
    } else {
        $ratio = min($width/$w, $height/$h);
        $width = $w * $ratio;
        $height = $h * $ratio;
        $x = 0;
    }

    $new = imagecreatetruecolor($width, $height);

    switch ($type) {
        case 'jpeg':
        case 'jpg':
            $src = imagecreatefromjpeg($file);
            break;
        case 'png':
            $src = imagecreatefrompng($file);
            break;
        case 'gif':
            $src = imagecreatefromgif($file);
            break;
    }

    imagecopyresampled($new, $src, 0, 0, $x, 0, $width, $height, $w, $h);

    switch ($type) {
        case 'jpeg':
        case 'jpg':
            imagejpeg($new, $file, 90);
            break;
        case 'png':
            imagepng($new, $file, 9);
            break;
        case 'gif':
            imagegif($new, $file);
            break;
    }

    imagedestroy($new);
    imagedestroy($src);
}
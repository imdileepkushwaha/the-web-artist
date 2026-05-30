<?php
$im = imagecreatefrompng("images/twa-logo.png");
$width = imagesx($im);
$height = imagesy($im);
$colors = [];

for ($x = 0; $x < $width; $x += 5) {
    for ($y = 0; $y < $height; $y += 5) {
        $color_index = imagecolorat($im, $x, $y);
        $color_tran = imagecolorsforindex($im, $color_index);
        
        // Skip transparent, pure white, pure black
        if ($color_tran['alpha'] > 100) continue;
        if ($color_tran['red'] > 240 && $color_tran['green'] > 240 && $color_tran['blue'] > 240) continue;
        if ($color_tran['red'] < 15 && $color_tran['green'] < 15 && $color_tran['blue'] < 15) continue;
        
        $hex = sprintf("#%02x%02x%02x", $color_tran['red'], $color_tran['green'], $color_tran['blue']);
        if (!isset($colors[$hex])) $colors[$hex] = 0;
        $colors[$hex]++;
    }
}
arsort($colors);
$count = 0;
foreach ($colors as $hex => $freq) {
    echo "$hex => $freq\n";
    $count++;
    if ($count > 5) break;
}
?>

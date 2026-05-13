<?php
// Simple icon generator for PWA
// This creates basic colored squares as placeholder icons
// In production, replace with actual designed icons

$iconSizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];
$iconColor = '#6366f1'; // Primary color

header('Content-Type: text/plain');

echo "Creating PWA icons...\n";

foreach ($iconSizes as $size) {
    $image = imagecreatetruecolor($size, $size);
    
    // Convert hex to RGB
    $r = hexdec(substr($iconColor, 1, 2));
    $g = hexdec(substr($iconColor, 3, 2));
    $b = hexdec(substr($iconColor, 5, 2));
    
    $color = imagecolorallocate($image, $r, $g, $b);
    imagefill($image, 0, 0, $color);
    
    // Add text "IH" for Internship Hub
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $fontSize = max(8, $size / 8);
    $text = "IH";
    
    // Calculate text position
    $bbox = imagettfbbox($fontSize, 0, 'arial', $text);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
    $x = ($size - $textWidth) / 2;
    $y = ($size + $textHeight) / 2;
    
    imagettftext($image, $fontSize, 0, $x, $y, $textColor, 'arial', $text);
    
    $filename = __DIR__ . "/icons/icon-{$size}x{$size}.png";
    
    if (imagepng($image, $filename)) {
        echo "✓ Created icon-{$size}x{$size}.png\n";
    } else {
        echo "✗ Failed to create icon-{$size}x{$size}.png\n";
    }
    
    imagedestroy($image);
}

echo "\nIcon creation complete!\n";
echo "Replace these with professionally designed icons for production.\n";
?>

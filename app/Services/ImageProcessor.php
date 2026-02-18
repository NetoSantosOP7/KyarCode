<?php

namespace App\Services;

class ImageProcessor
{
    public function applyBorderRadius($image, int $radius, array $bgColor)
    {
        $w = imagesx($image);
        $h = imagesy($image);

        $rounded = imagecreatetruecolor($w, $h);
        imagealphablending($rounded, false);
        imagesavealpha($rounded, true);

        $transparent = imagecolorallocatealpha($rounded, 0, 0, 0, 127);
        imagefill($rounded, 0, 0, $transparent);

        $bgColorIdx = imagecolorallocate($rounded, $bgColor[0], $bgColor[1], $bgColor[2]);
        $this->drawFilledRoundedRect($rounded, 0, 0, $w - 1, $h - 1, $radius, $bgColorIdx);

        imagecopy($rounded, $image, 0, 0, 0, 0, $w, $h);
        imagedestroy($image);

        return $rounded;
    }
    
    public function applyGlow($image, array $glowRGB, array $bgColor, int $glowIntensity = 50)
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);

        $glowIntensityNorm = $glowIntensity / 100;
        $glowSpread = (int)(20 + ($glowIntensityNorm * 50));
        $blurIterations = max(1, (int)($glowIntensityNorm * 3));

        $newW = $srcW + ($glowSpread * 2);
        $newH = $srcH + ($glowSpread * 2);

        $canvas = imagecreatetruecolor($newW, $newH);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $bgColorIdx = imagecolorallocate($canvas, $bgColor[0], $bgColor[1], $bgColor[2]);
        imagefill($canvas, 0, 0, $bgColorIdx);
        imagealphablending($canvas, true);

        $glowLayer = imagecreatetruecolor($newW, $newH);
        imagealphablending($glowLayer, false);
        imagesavealpha($glowLayer, true);
        $transparent = imagecolorallocatealpha($glowLayer, 0, 0, 0, 127);
        imagefill($glowLayer, 0, 0, $transparent);
        imagealphablending($glowLayer, true);

        imagecopy($glowLayer, $image, $glowSpread, $glowSpread, 0, 0, $srcW, $srcH);

        for ($i = 0; $i < $blurIterations; $i++) {
            imagefilter($glowLayer, IMG_FILTER_GAUSSIAN_BLUR);
        }

        imagecopy($canvas, $glowLayer, 0, 0, 0, 0, $newW, $newH);
        imagedestroy($glowLayer);

        imagecopy($canvas, $image, $glowSpread, $glowSpread, 0, 0, $srcW, $srcH);
        imagedestroy($image);

        return $canvas;
    }
    
    public function addLogo($image, string $logoBase64, int $logoSize, bool $hasFrame = false)
    {
        $parts = explode(',', $logoBase64, 2);
        if (count($parts) < 2) return $image;
        
        $logoData = base64_decode($parts[1]);
        if (!$logoData) return $image;
        
        $logoResource = @imagecreatefromstring($logoData);
        if (!$logoResource) return $image;
        
        $qrW = imagesx($image);
        
        $adjustedLogoSize = $hasFrame ? $logoSize * 0.7 : $logoSize;
        $newSize = (int)($qrW * ($adjustedLogoSize / 100));
        $white = imagecolorallocate($image, 255, 255, 255);
        
        imagefilledellipse($image, (int)($qrW / 2), (int)($qrW / 2), (int)($newSize * 1.05), (int)($newSize * 1.05), $white);
        imagecopyresampled($image, $logoResource, (int)(($qrW - $newSize) / 2), (int)(($qrW - $newSize) / 2), 0, 0, (int)$newSize, (int)$newSize, imagesx($logoResource), imagesy($logoResource));
        imagedestroy($logoResource);
        
        return $image;
    }
    
    public function drawCircularEyes($image, array $rgbColor, array $rgbBg)
    {
        $w = imagesx($image);
        $scale = 20;
        $moduleSize = $scale;
        $eyeSize = 7 * $moduleSize;
        $quietZone = 2 * $moduleSize;

        $eyePositions = [
            ['x' => $quietZone, 'y' => $quietZone],
            ['x' => $w - $quietZone - $eyeSize, 'y' => $quietZone],
            ['x' => $quietZone, 'y' => $w - $quietZone - $eyeSize]
        ];

        $bgColorIdx = imagecolorallocate($image, $rgbBg[0], $rgbBg[1], $rgbBg[2]);
        $fgColorIdx = imagecolorallocate($image, $rgbColor[0], $rgbColor[1], $rgbColor[2]);

        foreach ($eyePositions as $pos) {
            imagefilledrectangle($image, (int)$pos['x'], (int)$pos['y'], (int)($pos['x'] + $eyeSize), (int)($pos['y'] + $eyeSize), $bgColorIdx);

            $centerX = $pos['x'] + $eyeSize / 2;
            $centerY = $pos['y'] + $eyeSize / 2;

            imagefilledellipse($image, (int)$centerX, (int)$centerY, (int)$eyeSize, (int)$eyeSize, $fgColorIdx);

            $innerSize = $eyeSize - (2 * $moduleSize);
            imagefilledellipse($image, (int)$centerX, (int)$centerY, (int)$innerSize, (int)$innerSize, $bgColorIdx);

            $dotSize = 3 * $moduleSize;
            imagefilledellipse($image, (int)$centerX, (int)$centerY, (int)$dotSize, (int)$dotSize, $fgColorIdx);
        }

        return $image;
    }
    
    public function drawFilledRoundedRect($im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
    
    public function hexToRgb(string $hex): array
    {
        $hex = str_replace("#", "", $hex);
        return [
            hexdec(substr($hex, 0, 2)), 
            hexdec(substr($hex, 2, 2)), 
            hexdec(substr($hex, 4, 2))
        ];
    }
    
    public function toDataUri($image): string
    {
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        return 'data:image/png;base64,' . base64_encode($imageData);
    }
}

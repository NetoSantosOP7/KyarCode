<?php

namespace App\Controllers;

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Data\QRMatrix;
use App\Config\QRConfig;
use App\Services\ImageProcessor;
use App\Services\FrameApplier;

class QRController
{
    private ImageProcessor $imageProcessor;
    private FrameApplier $frameApplier;
    
    public function __construct()
    {
        $this->imageProcessor = new ImageProcessor();
        $this->frameApplier = new FrameApplier();
    }
    
    public function generate($configOrText, $color = null, $bg = null, $logoBase64 = null, $frameType = 'none', $glowColor = null, $is3d = false, $logoSize = 18, $qrBorderRadius = 20, $bgBorderRadius = 40, $shadowIntensity = 50, $glowIntensity = 50, $customText = '', $textSize = 28, $textColor = '#ffffff', $textShadowColor = '#000000', $textShadowIntensity = 30, $textGlow = 0, $moduleStyle = 'square')
    {
        if ($configOrText instanceof QRConfig) {
            $config = $configOrText;
        } else {
            $config = new QRConfig([
                'text' => $configOrText,
                'color' => $color ?? '#000000',
                'bg' => $bg ?? '#ffffff',
                'logo' => $logoBase64,
                'frameType' => $frameType,
                'glowColor' => $glowColor,
                'effect' => $is3d === 'true' || $is3d === true ? '3d' : ($glowColor && $glowColor !== 'none' ? 'glow' : ($frameType !== 'none' ? 'frame' : 'none')),
                'logoSize' => $logoSize,
                'qrBorderRadius' => $qrBorderRadius,
                'bgBorderRadius' => $bgBorderRadius,
                'shadowIntensity' => $shadowIntensity,
                'glowIntensity' => $glowIntensity,
                'customText' => $customText,
                'textSize' => $textSize,
                'textColor' => $textColor,
                'textShadowColor' => $textShadowColor,
                'textShadowIntensity' => $textShadowIntensity,
                'textGlow' => $textGlow,
                'moduleStyle' => $moduleStyle,
            ]);
        }
        
        $rgbColor = $this->imageProcessor->hexToRgb($config->color);
        $rgbBg = $this->imageProcessor->hexToRgb($config->bg);

        $drawCircularModules = ($config->moduleStyle === 'rounded');
        $circleRadius = 0.45;
        
        $keepAsSquare = [
            QRMatrix::M_FINDER_DARK,
            QRMatrix::M_FINDER_DOT,
            QRMatrix::M_ALIGNMENT_DARK
        ];

        $options = new QROptions([
            'version'             => QRCode::VERSION_AUTO,
            'outputType'          => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'            => QRCode::ECC_H,
            'scale'               => 20,
            'imageBase64'         => false,
            'addQuietzone'        => true,
            'quietzoneSize'       => 2,
            'imageTransparent'    => true,
            'drawCircularModules' => $drawCircularModules,
            'circleRadius'        => $circleRadius,
            'keepAsSquare'        => $keepAsSquare,
            'moduleValues'        => [
                QRMatrix::M_DATA_DARK => $rgbColor,
                QRMatrix::M_DATA      => null,
                QRMatrix::M_QUIETZONE => null,
                QRMatrix::M_FINDER_DARK => $rgbColor,
                QRMatrix::M_FINDER_DOT => $rgbColor,
                QRMatrix::M_ALIGNMENT_DARK => $rgbColor,
                QRMatrix::M_TIMING_DARK => $rgbColor,
                QRMatrix::M_FORMAT_DARK => $rgbColor,
                QRMatrix::M_VERSION_DARK => $rgbColor,
                QRMatrix::M_DARKMODULE => $rgbColor,
            ],
        ]);

        $qrcode = new QRCode($options);
        $qrRawData = $qrcode->render($config->text);
        $qrSource = imagecreatefromstring($qrRawData);
        
        imagealphablending($qrSource, false);
        imagesavealpha($qrSource, true);

        $w = imagesx($qrSource);
        $h = imagesy($qrSource);

        $qrResource = imagecreatetruecolor($w, $h);
        imagealphablending($qrResource, true);
        imagesavealpha($qrResource, true);

        $bgColorIdx = imagecolorallocate($qrResource, $rgbBg[0], $rgbBg[1], $rgbBg[2]);
        imagefill($qrResource, 0, 0, $bgColorIdx);

        imagecopy($qrResource, $qrSource, 0, 0, 0, 0, $w, $h);
        imagedestroy($qrSource);

        if ($config->moduleStyle === 'rounded') {
            $qrResource = $this->imageProcessor->drawCircularEyes($qrResource, $rgbColor, $rgbBg);
        }

        $isCharacterFrame = in_array($config->frameType, ['naruto', 'batman', 'onepiece'], true);
        
        if ($config->frameType !== 'none') {

            if (!$isCharacterFrame && $config->logoBase64) {
                $qrResource = $this->imageProcessor->addLogo($qrResource, $config->logoBase64, $config->logoSize, false);
            }
            $qrResource = $this->frameApplier->apply($qrResource, $config->frameType, $rgbColor, $rgbBg, $config->logoBase64, $config->logoSize);
        } else {
            if ($config->logoBase64) {
                $qrResource = $this->imageProcessor->addLogo($qrResource, $config->logoBase64, $config->logoSize, false);
            }
        }

        if ($config->glowColor && $config->glowColor !== 'none') {
            $glowRGB = $this->imageProcessor->hexToRgb($config->glowColor);
            $qrResource = $this->imageProcessor->applyGlow($qrResource, $glowRGB, $rgbBg, $config->glowIntensity);
        }

        $qrResource = $this->imageProcessor->applyBorderRadius($qrResource, $config->bgBorderRadius, $rgbBg);

        return $this->imageProcessor->toDataUri($qrResource);
    }
}

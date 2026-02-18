<?php

namespace App\Services;

class FrameApplier
{
    private string $framesPath;
    private ImageProcessor $imageProcessor;
    
    private array $frameConfigs = [
        'naruto' => ['scale' => 1.9, 'offsetX' => -17, 'offsetY' => 4],
        'batman' => ['scale' => 1.9, 'offsetX' => -13, 'offsetY' => -20],
        'onepiece' => ['scale' => 1.8, 'offsetX' => -10, 'offsetY' => 8],
    ];
    
    private array $geometricFrames = [
        'classic' => ['thickness' => 15, 'padding' => 5],
        'modern' => ['thickness' => 25, 'padding' => 8],
        'bold' => ['thickness' => 35, 'padding' => 10],
    ];
    
    public function __construct(string $framesPath = null)
    {
        $this->framesPath = $framesPath ?? __DIR__ . '/../../public/frames/';
        $this->imageProcessor = new ImageProcessor();
    }
    
    public function apply($qrResource, string $type, array $mainColor, array $bgColor, $logoBase64 = null, $logoSize = 18)
    {
        if ($type === 'none') {
            return $qrResource;
        }
        
        if ($this->isCharacterFrame($type)) {

            if ($logoBase64) {
                $qrResource = $this->imageProcessor->addLogo($qrResource, $logoBase64, $logoSize, true);
            }
            return $this->applyCharacterFrame($qrResource, $type);
        }
        
        if ($this->isGeometricFrame($type)) {
            return $this->applyGeometricFrame($qrResource, $type, $mainColor, $bgColor);
        }
        
        return $qrResource;
    }
    
    private function isCharacterFrame(string $type): bool
    {
        return isset($this->frameConfigs[$type]);
    }
    
    private function isGeometricFrame(string $type): bool
    {
        return isset($this->geometricFrames[$type]);
    }
    
    private function applyCharacterFrame($qrResource, string $type)
    {
        $frameFile = $type . '.png';
        $framePath = $this->framesPath . $frameFile;
        
        if (!file_exists($framePath)) {
            return $qrResource;
        }
        
        $config = $this->frameConfigs[$type];
        $qrW = imagesx($qrResource);
        $qrH = imagesy($qrResource);
        $desiredFrameSize = (int)($qrW * $config['scale']);
        
        $frameImage = @imagecreatefrompng($framePath);
        if (!$frameImage) {
            return $qrResource;
        }
        
        $resizedFrame = $this->resizeFrameOptimized($frameImage, $desiredFrameSize);
        if (!$resizedFrame) {
            return $qrResource;
        }
        
        $canvas = $this->createTransparentCanvas($desiredFrameSize, $desiredFrameSize);
        
        $centerX = (int)(($desiredFrameSize - $qrW) / 2) + $config['offsetX'];
        $centerY = (int)(($desiredFrameSize - $qrH) / 2) + $config['offsetY'];
        
        imagecopy($canvas, $qrResource, $centerX, $centerY, 0, 0, $qrW, $qrH);
        imagecopy($canvas, $resizedFrame, 0, 0, 0, 0, $desiredFrameSize, $desiredFrameSize);
        
        imagedestroy($qrResource);
        imagedestroy($resizedFrame);
        
        return $canvas;
    }
    
    private function applyGeometricFrame($qrResource, string $type, array $mainColor, array $bgColor)
    {
        $settings = $this->geometricFrames[$type];
        $qrW = imagesx($qrResource);
        $qrH = imagesy($qrResource);
        
        $thickness = $settings['thickness'];
        $padding = $settings['padding'];
        $newW = $qrW + (($thickness + $padding) * 2);
        $newH = $qrH + (($thickness + $padding) * 2);
        
        $canvas = $this->createTransparentCanvas($newW, $newH);
        
        $frameCol = imagecolorallocate($canvas, $mainColor[0], $mainColor[1], $mainColor[2]);
        $bgCol = imagecolorallocate($canvas, $bgColor[0], $bgColor[1], $bgColor[2]);
        
        $this->imageProcessor->drawFilledRoundedRect($canvas, 0, 0, $newW - 1, $newH - 1, 50, $frameCol);
        $this->imageProcessor->drawFilledRoundedRect($canvas, $thickness, $thickness, $newW - $thickness - 1, $newH - $thickness - 1, 35, $bgCol);
        
        imagecopy($canvas, $qrResource, $thickness + $padding, $thickness + $padding, 0, 0, $qrW, $qrH);
        imagedestroy($qrResource);
        
        return $canvas;
    }
    
    private function resizeFrameOptimized($frameImage, int $desiredSize)
    {
        $frameW = imagesx($frameImage);
        $frameH = imagesy($frameImage);
        
        if ($frameW > 600 || $frameH > 600) {
            $intermediateSize = (int)(max($frameW, $frameH) / 2);
            $intermediate = $this->createTransparentCanvas($intermediateSize, $intermediateSize);
            
            imagecopyresized($intermediate, $frameImage, 0, 0, 0, 0, $intermediateSize, $intermediateSize, $frameW, $frameH);
            imagedestroy($frameImage);
            
            $resizedFrame = $this->createTransparentCanvas($desiredSize, $desiredSize);
            imagecopyresized($resizedFrame, $intermediate, 0, 0, 0, 0, $desiredSize, $desiredSize, $intermediateSize, $intermediateSize);
            imagedestroy($intermediate);
        } else {
            $resizedFrame = $this->createTransparentCanvas($desiredSize, $desiredSize);
            imagecopyresized($resizedFrame, $frameImage, 0, 0, 0, 0, $desiredSize, $desiredSize, $frameW, $frameH);
            imagedestroy($frameImage);
        }
        
        return $resizedFrame;
    }
    
    private function createTransparentCanvas(int $width, int $height)
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        imagealphablending($canvas, true);
        
        return $canvas;
    }
}

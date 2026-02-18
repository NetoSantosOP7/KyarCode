<?php

namespace App\Config;

class QRConfig
{
    public string $text;
    public string $color;
    public string $bg;
    
    public int $qrBorderRadius;
    public int $bgBorderRadius;
    public string $moduleStyle;
    
    public ?string $logoBase64;
    public int $logoSize;
    
    public string $customText;
    public int $textSize;
    public string $textColor;
    public string $textShadowColor;
    public int $textShadowIntensity;
    public int $textGlow;
    
    public string $effect;
    public int $shadowIntensity;
    public string $glowColor;
    public int $glowIntensity;
    public string $frameType;
    public bool $is3d;
    
    public function __construct(array $data = [])
    {
        $this->text = $this->sanitizeString($data['text'] ?? 'https://google.com');
        $this->color = $this->validateColor($data['color'] ?? '#000000');
        $this->bg = $this->validateColor($data['bg'] ?? '#ffffff');
        
        $this->qrBorderRadius = $this->validateRange((int)($data['qrBorderRadius'] ?? 20), 0, 120);
        $this->bgBorderRadius = $this->validateRange((int)($data['bgBorderRadius'] ?? 40), 0, 120);
        $this->moduleStyle = $this->validateModuleStyle($data['moduleStyle'] ?? 'square');
        
        $this->logoBase64 = $this->validateLogo($data['logo'] ?? null);
        $this->logoSize = $this->validateRange((int)($data['logoSize'] ?? 18), 10, 32);
        
        $this->customText = $this->sanitizeString($data['customText'] ?? '', 255);
        $this->textSize = $this->validateRange((int)($data['textSize'] ?? 36), 24, 72);
        $this->textColor = $this->validateColor($data['textColor'] ?? '#0f172a');
        $this->textShadowColor = $this->validateColor($data['textShadowColor'] ?? '#000000');
        $this->textShadowIntensity = $this->validateRange((int)($data['textShadowIntensity'] ?? 30), 0, 100);
        $this->textGlow = $this->validateRange((int)($data['textGlow'] ?? 0), 0, 100);
        
        $this->effect = $this->validateEffect($data['effect'] ?? 'none');
        $this->shadowIntensity = $this->validateRange((int)($data['shadowIntensity'] ?? 50), 0, 100);
        $this->glowColor = $this->validateColor($data['glowColor'] ?? 'none', true);
        $this->glowIntensity = $this->validateRange((int)($data['glowIntensity'] ?? 50), 0, 100);
        $this->frameType = $this->validateFrameType($data['frameType'] ?? 'none');
        $this->is3d = ($this->effect === '3d');
    }
    
    private function validateColor(string $color, bool $allowNone = false): string
    {
        $color = trim($color);
        
        if ($allowNone && $color === 'none') {
            return 'none';
        }
        
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return strtolower($color);
        }
        
        return '#000000';
    }
    
    private function validateRange(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }
    
    private function sanitizeString(string $str, int $maxLength = 2048): string
    {
        $str = trim($str);
        $str = mb_substr($str, 0, $maxLength);
        return $str;
    }
    
    private function validateModuleStyle(string $style): string
    {
        $allowed = ['square', 'rounded'];
        return in_array($style, $allowed, true) ? $style : 'square';
    }
    
    private function validateEffect(string $effect): string
    {
        $allowed = ['none', '3d', 'glow', 'frame'];
        return in_array($effect, $allowed, true) ? $effect : 'none';
    }
    
    private function validateFrameType(string $frameType): string
    {
        $allowed = ['none', 'naruto', 'batman', 'onepiece', 'classic', 'modern', 'bold'];
        return in_array($frameType, $allowed, true) ? $frameType : 'none';
    }
    
    private function validateLogo(?string $logo): ?string
    {
        if (!$logo || empty($logo)) {
            return null;
        }
        
        if (strpos($logo, 'data:image/') !== 0) {
            return null;
        }
        
        $parts = explode(',', $logo, 2);
        if (count($parts) < 2) {
            return null;
        }
        
        $decoded = base64_decode($parts[1], true);
        if ($decoded === false) {
            return null;
        }
        
        if (strlen($decoded) > 2 * 1024 * 1024) {
            return null;
        }
        
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($decoded);
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
        
        if (!in_array($mimeType, $allowedMimes, true)) {
            return null;
        }
        
        return $logo;
    }
    
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'color' => $this->color,
            'bg' => $this->bg,
            'qrBorderRadius' => $this->qrBorderRadius,
            'bgBorderRadius' => $this->bgBorderRadius,
            'moduleStyle' => $this->moduleStyle,
            'logoBase64' => $this->logoBase64,
            'logoSize' => $this->logoSize,
            'customText' => $this->customText,
            'textSize' => $this->textSize,
            'textColor' => $this->textColor,
            'textShadowColor' => $this->textShadowColor,
            'textShadowIntensity' => $this->textShadowIntensity,
            'textGlow' => $this->textGlow,
            'effect' => $this->effect,
            'shadowIntensity' => $this->shadowIntensity,
            'glowColor' => $this->glowColor,
            'glowIntensity' => $this->glowIntensity,
            'frameType' => $this->frameType,
            'is3d' => $this->is3d,
        ];
    }
}

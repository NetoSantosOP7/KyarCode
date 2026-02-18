<?php

define('ROOT_PATH', dirname(__DIR__));

$protocol = "https"; 
$host = $_SERVER['HTTP_HOST'];

define('URL_BASE', $protocol . "://" . $host);

require_once ROOT_PATH . '/vendor/autoload.php';
use App\Controllers\QRController;
use App\Config\QRConfig;

$route = $_GET['url'] ?? 'home';

if (ob_get_length()) ob_clean();

if ($route === 'home') {
    include ROOT_PATH . '/app/Views/home.php';
} elseif ($route === 'generate') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    try {
        $config = new QRConfig([
            'text' => $_POST['text'] ?? 'https://google.com',
            'color' => $_POST['color'] ?? '#000000',
            'bg' => $_POST['bg'] ?? '#ffffff',
            'qrBorderRadius' => $_POST['qrBorderRadius'] ?? 20,
            'bgBorderRadius' => $_POST['bgBorderRadius'] ?? 40,
            'moduleStyle' => $_POST['moduleStyle'] ?? 'square',
            'logo' => $_POST['logo'] ?? null,
            'logoSize' => $_POST['logoSize'] ?? 18,
            'customText' => $_POST['customText'] ?? '',
            'textSize' => $_POST['textSize'] ?? 36,
            'textColor' => $_POST['textColor'] ?? '#0f172a',
            'textShadowColor' => $_POST['textShadowColor'] ?? '#000000',
            'textShadowIntensity' => $_POST['textShadowIntensity'] ?? 30,
            'textGlow' => $_POST['textGlow'] ?? 0,
            'effect' => $_POST['effect'] ?? 'none',
            'shadowIntensity' => $_POST['shadowIntensity'] ?? 50,
            'glowColor' => $_POST['glowColor'] ?? 'none',
            'glowIntensity' => $_POST['glowIntensity'] ?? 50,
            'frameType' => $_POST['frameType'] ?? 'none',
        ]);

        $controller = new QRController();
        echo $controller->generate($config);
        
    } catch (Throwable $e) {
        http_response_code(500);
        echo "Erro: " . $e->getMessage();
    }

    exit;
}

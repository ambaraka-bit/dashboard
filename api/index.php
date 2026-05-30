<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $base   = __DIR__ . '/src/';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$appCfg = require __DIR__ . '/config/app.php';
date_default_timezone_set($appCfg['timezone']);

header('Access-Control-Allow-Origin: '  . $appCfg['cors_origin']);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

set_exception_handler(static function (\Throwable $e): void {
    $status = ($e->getCode() >= 400 && $e->getCode() < 600)
              ? $e->getCode()
              : 500;
    http_response_code($status);
    echo json_encode([
        'success'   => false,
        'timestamp' => date('c'),
        'error'     => ['code' => $status, 'message' => $e->getMessage()],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
});

use App\Router;
$router = new Router();
require __DIR__ . '/routes/api.php';
$router->dispatch();
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

if (PHP_SAPI === 'cli-server' && $requestPath !== '/' && is_file(__DIR__ . $requestPath)) {
    return false;
}

if (PHP_SAPI === 'cli-server' && $requestPath === '/_aml/live-reload') {
    $fingerprint = [];
    foreach ([$root . '/app', $root . '/configs', $root . '/database', __DIR__] as $watchedRoot) {
        if (!is_dir($watchedRoot)) { continue; }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($watchedRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php', 'css', 'js', 'html', 'json', 'svg'], true)) {
                $fingerprint[] = $file->getPathname() . ':' . $file->getMTime() . ':' . $file->getSize();
            }
        }
    }
    sort($fingerprint);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(['version' => sha1(implode('|', $fingerprint))], JSON_THROW_ON_ERROR);
    return;
}

$moduleAutoloader = $root . '/runtime/autoload.php';
if (is_file($moduleAutoloader)) {
    require_once $moduleAutoloader;
} else {
    $frameworkAutoloader = $root . '/runtime/framework/Autoloader.php';
    if (!is_file($frameworkAutoloader)) {
        http_response_code(500);
        exit('Application indisponible.');
    }
    require_once $frameworkAutoloader;
    \PHPAML\Autoloader::register(['PHPAML\\' => $root . '/runtime/framework', 'App\\' => $root . '/app']);
}

\PHPAML\Config\Env::load($root . '/.env');
$config = require $root . '/configs/app.php';
(new \PHPAML\WebApplication($config))->run();

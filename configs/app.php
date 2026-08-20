<?php

declare(strict_types=1);

use PHPAML\Config\Env;
use PHPAML\Middleware\SecurityHeadersMiddleware;

return [
    'project_root' => dirname(__DIR__),
    'name' => 'PHPAML Movies API',
    'debug' => Env::bool('APP_DEBUG', false),
    'session' => [
        'lifetime' => 7200,
        'same_site' => 'Lax',
    ],
    'views_path' => dirname(__DIR__) . '/app/views',
    'log_path' => dirname(__DIR__) . '/runtime/storage/logs/application.log',
    'rate_limit' => [
        'enabled' => true,
        'storage_path' => dirname(__DIR__) . '/runtime/storage/rate-limits',
        'limit' => 60,
        'window' => 60,
        'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
    ],
    'database' => [
        'dsn' => Env::get('DATABASE_DSN', 'sqlite:' . dirname(__DIR__) . '/runtime/storage/database.sqlite'),
        'username' => Env::get('DATABASE_USER', 'root'),
        'password' => Env::get('DATABASE_PASSWORD', 'root'),
    ],
    'routes' => require __DIR__ . '/api-routes.php',
    'middlewares' => [SecurityHeadersMiddleware::class],
];

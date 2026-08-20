<?php
declare(strict_types=1);

use App\Controllers\Api\MovieController;

return [
    'GET /' => [MovieController::class, 'info'],
    'GET /api/v1/movies' => [MovieController::class, 'index'],
    'GET /api/v1/movies/{id}' => [MovieController::class, 'show'],
    'GET /api/v1/genres' => [MovieController::class, 'genres'],
    'OPTIONS /api/v1/movies' => [MovieController::class, 'options'],
];

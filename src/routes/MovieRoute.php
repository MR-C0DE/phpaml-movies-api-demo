<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\MovieController;
use PHPAML\Routing\Route;

final class MovieRoute extends Route
{
    protected string $prefix = '/api/v1';

    protected function routes(): void
    {
        $this->get('/movies', [MovieController::class, 'index']);
        $this->get('/movies/{id}', [MovieController::class, 'show']);
        $this->get('/genres', [MovieController::class, 'genres']);
        $this->options('/movies', [MovieController::class, 'options']);
    }
}

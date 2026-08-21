<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controllers\MovieController;
use PHPAML\Routing\Route;

final class ApiRoute extends Route
{
    protected function routes(): void
    {
        $this->get('/', [MovieController::class, 'info']);
    }
}

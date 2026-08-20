<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\MovieRepository;
use PHPAML\Data\Connection;
use PHPAML\Http\Request;
use PHPAML\Http\Response;

final class MovieController
{
    private MovieRepository $movies;

    public function __construct(Connection $connection) { $this->movies = new MovieRepository($connection->pdo()); }

    public function info(): Response
    {
        return $this->json(['name' => 'PHPAML Movies API', 'version' => 'v1', 'documentation' => '/openapi.json', 'endpoints' => ['/api/v1/movies', '/api/v1/movies/{id}', '/api/v1/genres']]);
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 12)));
        $result = $this->movies->search(trim((string) $request->query('q', '')), trim((string) $request->query('genre', '')), $page, $perPage);
        return $this->json(['data' => $result['items'], 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total'], 'last_page' => max(1, (int) ceil($result['total'] / $perPage))]]);
    }

    public function show(Request $request): Response
    {
        $movie = $this->movies->find((int) $request->attribute('id'));
        return $movie === null ? $this->json(['error' => ['code' => 'MOVIE_NOT_FOUND', 'message' => 'Movie not found.']], 404) : $this->json(['data' => $movie]);
    }

    public function genres(): Response { return $this->json(['data' => $this->movies->genres()]); }
    public function options(): Response { return $this->json(null, 204); }

    private function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status)->withHeader('Access-Control-Allow-Origin', '*')->withHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')->withHeader('Access-Control-Allow-Headers', 'Accept, Content-Type')->withHeader('Cache-Control', 'public, max-age=60');
    }
}

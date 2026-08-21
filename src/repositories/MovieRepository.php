<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Movie;
use PDO;

final class MovieRepository
{
    public function __construct(private PDO $database)
    {
        $this->prepareDatabase();
    }

    /** @return array{items: list<array<string, int|float|string>>, total: int} */
    public function search(string $query, string $genre, int $page, int $perPage): array
    {
        $conditions = [];
        $parameters = [];

        if ($query !== '') {
            $conditions[] = '(title LIKE :query OR director LIKE :query OR synopsis LIKE :query)';
            $parameters['query'] = '%' . $query . '%';
        }
        if ($genre !== '') {
            $conditions[] = 'genre = :genre';
            $parameters['genre'] = $genre;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $count = $this->database->prepare('SELECT COUNT(*) FROM movies' . $where);
        $count->execute($parameters);

        $statement = $this->database->prepare(
            'SELECT * FROM movies' . $where . ' ORDER BY year DESC, title ASC LIMIT :limit OFFSET :offset',
        );
        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => array_map(
                static fn (array $row): array => Movie::fromDatabase($row)->toArray(),
                $statement->fetchAll(),
            ),
            'total' => (int) $count->fetchColumn(),
        ];
    }

    /** @return array<string, int|float|string>|null */
    public function find(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM movies WHERE id = :id');
        $statement->execute(['id' => $id]);
        $movie = $statement->fetch();

        return is_array($movie) ? Movie::fromDatabase($movie)->toArray() : null;
    }

    /** @return list<string> */
    public function genres(): array
    {
        return array_values(array_map(
            'strval',
            $this->database->query('SELECT DISTINCT genre FROM movies ORDER BY genre')->fetchAll(PDO::FETCH_COLUMN),
        ));
    }

    private function prepareDatabase(): void
    {
        $migration = require dirname(__DIR__, 2) . '/database/migrations/001_create_movies.php';
        $seeder = require dirname(__DIR__, 2) . '/database/seeders/movies.php';
        $migration($this->database);
        $seeder($this->database);
    }
}

<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MovieRepository
{
    public function __construct(private PDO $database) { $this->prepareDatabase(); }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public function search(string $query, string $genre, int $page, int $perPage): array
    {
        $conditions = []; $parameters = [];
        if ($query !== '') { $conditions[] = '(title LIKE :query OR director LIKE :query OR synopsis LIKE :query)'; $parameters['query'] = '%' . $query . '%'; }
        if ($genre !== '') { $conditions[] = 'genre = :genre'; $parameters['genre'] = $genre; }
        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $count = $this->database->prepare('SELECT COUNT(*) FROM movies' . $where); $count->execute($parameters);
        $statement = $this->database->prepare('SELECT * FROM movies' . $where . ' ORDER BY year DESC, title ASC LIMIT :limit OFFSET :offset');
        foreach ($parameters as $key => $value) $statement->bindValue(':' . $key, $value);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT); $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT); $statement->execute();
        return ['items' => array_map($this->format(...), $statement->fetchAll()), 'total' => (int) $count->fetchColumn()];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->database->prepare('SELECT * FROM movies WHERE id = :id'); $statement->execute(['id' => $id]); $movie = $statement->fetch();
        return is_array($movie) ? $this->format($movie) : null;
    }

    /** @return list<string> */
    public function genres(): array { return array_values(array_map('strval', $this->database->query('SELECT DISTINCT genre FROM movies ORDER BY genre')->fetchAll(PDO::FETCH_COLUMN))); }

    private function prepareDatabase(): void
    {
        $this->database->exec('CREATE TABLE IF NOT EXISTS movies (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, year INTEGER NOT NULL, director TEXT NOT NULL, genre TEXT NOT NULL, rating INTEGER NOT NULL, synopsis TEXT NOT NULL)');
        if ((int) $this->database->query('SELECT COUNT(*) FROM movies')->fetchColumn() > 0) return;
        $insert = $this->database->prepare('INSERT INTO movies (title, year, director, genre, rating, synopsis) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($this->seedMovies() as $movie) $insert->execute($movie);
    }

    /** @param array<string, mixed> $movie @return array<string, mixed> */
    private function format(array $movie): array { return ['id' => (int) $movie['id'], 'title' => $movie['title'], 'year' => (int) $movie['year'], 'director' => $movie['director'], 'genre' => $movie['genre'], 'rating' => ((int) $movie['rating']) / 10, 'synopsis' => $movie['synopsis']]; }

    /** @return list<list<int|string>> */
    private function seedMovies(): array
    {
        return [
            ['Dune: Part Two', 2024, 'Denis Villeneuve', 'Science Fiction', 87, 'Paul Atreides unites with Chani and the Fremen while seeking revenge.'],
            ['Oppenheimer', 2023, 'Christopher Nolan', 'Drama', 86, 'The story of the scientist who led the Manhattan Project.'],
            ['Everything Everywhere All at Once', 2022, 'Daniel Kwan & Daniel Scheinert', 'Adventure', 78, 'A laundromat owner confronts the multiverse and the life she could have lived.'],
            ['Parasite', 2019, 'Bong Joon Ho', 'Thriller', 85, 'Two families become entangled through ambition, class, and deception.'],
            ['Spider-Man: Into the Spider-Verse', 2018, 'Bob Persichetti', 'Animation', 84, 'Miles Morales discovers that anyone can wear the mask.'],
            ['Arrival', 2016, 'Denis Villeneuve', 'Science Fiction', 79, 'A linguist works to communicate with visitors from another world.'],
            ['Mad Max: Fury Road', 2015, 'George Miller', 'Action', 81, 'Furiosa and Max flee a tyrant across a ruined wasteland.'],
            ['Interstellar', 2014, 'Christopher Nolan', 'Science Fiction', 87, 'Explorers travel through a wormhole to secure humanity’s future.'],
        ];
    }
}

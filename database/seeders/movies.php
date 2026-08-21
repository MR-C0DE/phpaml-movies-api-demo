<?php

declare(strict_types=1);

return static function (\PDO $database): void {
    if ((int) $database->query('SELECT COUNT(*) FROM movies')->fetchColumn() > 0) {
        return;
    }

    $movies = [
        ['Dune: Part Two', 2024, 'Denis Villeneuve', 'Science Fiction', 87, 'Paul Atreides unites with Chani and the Fremen while seeking revenge.'],
        ['Oppenheimer', 2023, 'Christopher Nolan', 'Drama', 86, 'The story of the scientist who led the Manhattan Project.'],
        ['Everything Everywhere All at Once', 2022, 'Daniel Kwan & Daniel Scheinert', 'Adventure', 78, 'A laundromat owner confronts the multiverse and the life she could have lived.'],
        ['Parasite', 2019, 'Bong Joon Ho', 'Thriller', 85, 'Two families become entangled through ambition, class, and deception.'],
        ['Spider-Man: Into the Spider-Verse', 2018, 'Bob Persichetti', 'Animation', 84, 'Miles Morales discovers that anyone can wear the mask.'],
        ['Arrival', 2016, 'Denis Villeneuve', 'Science Fiction', 79, 'A linguist works to communicate with visitors from another world.'],
        ['Mad Max: Fury Road', 2015, 'George Miller', 'Action', 81, 'Furiosa and Max flee a tyrant across a ruined wasteland.'],
        ['Interstellar', 2014, 'Christopher Nolan', 'Science Fiction', 87, 'Explorers travel through a wormhole to secure humanity’s future.'],
    ];
    $insert = $database->prepare(
        'INSERT INTO movies (title, year, director, genre, rating, synopsis) VALUES (?, ?, ?, ?, ?, ?)',
    );
    foreach ($movies as $movie) {
        $insert->execute($movie);
    }
};

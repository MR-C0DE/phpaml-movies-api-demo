<?php

declare(strict_types=1);

return static function (\PDO $database): void {
    $database->exec(
        'CREATE TABLE IF NOT EXISTS movies ('
        . 'id INTEGER PRIMARY KEY AUTOINCREMENT, '
        . 'title TEXT NOT NULL, '
        . 'year INTEGER NOT NULL, '
        . 'director TEXT NOT NULL, '
        . 'genre TEXT NOT NULL, '
        . 'rating INTEGER NOT NULL, '
        . 'synopsis TEXT NOT NULL'
        . ')',
    );
};

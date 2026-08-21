<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Movie
{
    public function __construct(
        public int $id,
        public string $title,
        public int $year,
        public string $director,
        public string $genre,
        public float $rating,
        public string $synopsis,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromDatabase(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['title'],
            (int) $row['year'],
            (string) $row['director'],
            (string) $row['genre'],
            ((int) $row['rating']) / 10,
            (string) $row['synopsis'],
        );
    }

    /** @return array<string, int|float|string> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

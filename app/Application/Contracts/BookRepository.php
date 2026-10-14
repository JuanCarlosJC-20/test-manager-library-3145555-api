<?php

namespace App\Application\Contracts;

use App\Domain\Books\Book;

interface BookRepository
{
    /** @return array{items: list<array<string, mixed>>, page: int, size: int, total: int} */
    public function search(?string $query, ?string $categoryId, int $page, int $size): array;

    public function findActive(string $id): ?Book;

    public function create(Book $book): Book;

    public function update(Book $book): Book;

    public function updateCoverKey(string $bookId, ?string $coverKey): bool;

    public function softDelete(Book $book): void;
}

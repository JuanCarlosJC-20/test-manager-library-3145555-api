<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;
use App\Application\Contracts\Clock;
use App\Application\Exceptions\BookNotFound;

final readonly class DeleteBook
{
    public function __construct(private BookRepository $books, private Clock $clock) {}

    public function execute(string $id): void
    {
        $book = $this->books->findActive($id) ?? throw new BookNotFound('No se encontró el libro.');

        $this->books->softDelete($book->softDelete($this->clock->now()));
    }
}

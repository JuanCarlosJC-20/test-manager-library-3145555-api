<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;
use App\Application\Exceptions\BookNotFound;
use App\Domain\Books\Book;

final readonly class GetBook
{
    public function __construct(private BookRepository $books) {}

    public function execute(string $id): Book
    {
        return $this->books->findActive($id) ?? throw new BookNotFound('No se encontró el libro.');
    }
}

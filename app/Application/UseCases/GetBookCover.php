<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;
use App\Application\Contracts\CoverStorage;
use App\Application\Data\StoredCover;
use App\Application\Exceptions\BookNotFound;
use App\Application\Exceptions\CoverNotFound;

final readonly class GetBookCover
{
    public function __construct(private BookRepository $books, private CoverStorage $storage) {}

    public function execute(string $bookId): StoredCover
    {
        $book = $this->books->findActive($bookId) ?? throw new BookNotFound('No se encontró el libro.');

        if ($book->coverKey === null || $book->coverKey === '') {
            throw new CoverNotFound('El libro no tiene portada.');
        }

        return $this->storage->retrieve($book->coverKey) ?? throw new CoverNotFound('No se encontró la portada.');
    }
}

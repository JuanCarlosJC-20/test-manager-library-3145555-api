<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;
use App\Application\Contracts\CoverStorage;
use App\Application\Exceptions\BookNotFound;

final readonly class DeleteBookCover
{
    public function __construct(private BookRepository $books, private CoverStorage $storage) {}

    public function execute(string $bookId): void
    {
        $book = $this->books->findActive($bookId) ?? throw new BookNotFound('No se encontró el libro.');

        if ($book->coverKey === null) {
            return;
        }

        if (! $this->books->updateCoverKey($bookId, null)) {
            throw new BookNotFound('No se encontró el libro.');
        }

        $this->storage->delete($book->coverKey);
    }
}

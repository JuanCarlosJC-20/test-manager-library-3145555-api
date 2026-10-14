<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;
use App\Application\Contracts\CoverStorage;
use App\Application\Exceptions\BookNotFound;

final readonly class UploadBookCover
{
    public function __construct(private BookRepository $books, private CoverStorage $storage) {}

    public function execute(string $bookId, string $contents, string $mimeType): void
    {
        $book = $this->books->findActive($bookId) ?? throw new BookNotFound('No se encontró el libro.');

        $newKey = $this->storage->store($contents, $mimeType);

        try {
            if (! $this->books->updateCoverKey($bookId, $newKey)) {
                throw new BookNotFound('No se encontró el libro.');
            }
        } catch (\Throwable $exception) {
            $this->storage->delete($newKey);
            throw $exception;
        }

        if ($book->coverKey !== null) {
            $this->storage->delete($book->coverKey);
        }
    }
}

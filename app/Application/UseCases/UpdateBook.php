<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;
use App\Application\Exceptions\BookNotFound;
use App\Domain\Books\Book;

final readonly class UpdateBook
{
    public function __construct(private BookRepository $books) {}

    public function execute(string $id, string $title, string $author, string $categoryId, int $totalCopies): Book
    {
        $book = $this->books->findActive($id) ?? throw new BookNotFound('No se encontró el libro.');
        $adjusted = $book->adjustTotalCopies($totalCopies);
        $updated = new Book($id, $title, trim($author), $book->isbn, $categoryId,
            $adjusted->totalCopies, $adjusted->availableCopies, $book->coverKey);

        return $this->books->update($updated);
    }
}

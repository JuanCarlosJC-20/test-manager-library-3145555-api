<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;
use App\Application\Contracts\IdGenerator;
use App\Domain\Books\Book;
use App\Domain\Books\Isbn;

final readonly class CreateBook
{
    public function __construct(private BookRepository $books, private IdGenerator $ids) {}

    public function execute(string $title, string $author, string $isbn, string $categoryId, int $totalCopies): Book
    {
        return $this->books->create(new Book(
            $this->ids->generate(), $title, trim($author), new Isbn($isbn), $categoryId, $totalCopies, $totalCopies,
        ));
    }
}

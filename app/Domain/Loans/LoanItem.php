<?php

namespace App\Domain\Loans;

use App\Domain\Books\Isbn;
use DateTimeImmutable;

final readonly class LoanItem
{
    public function __construct(
        public string $id,
        public string $bookId,
        public string $bookTitle,
        public Isbn $isbn,
        public string $categoryName,
        public DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $returnedAt = null,
    ) {}

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdueAt(DateTimeImmutable $now): bool
    {
        return ! $this->isReturned() && $this->dueAt < $now;
    }

    public function returnAt(DateTimeImmutable $at): self
    {
        if ($this->isReturned()) {
            throw new ItemAlreadyReturned('El ejemplar ya fue devuelto.');
        }

        return new self(
            $this->id,
            $this->bookId,
            $this->bookTitle,
            $this->isbn,
            $this->categoryName,
            $this->dueAt,
            $at,
        );
    }
}

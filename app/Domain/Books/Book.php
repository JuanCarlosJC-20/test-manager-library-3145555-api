<?php

namespace App\Domain\Books;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Book
{
    public string $title;

    public function __construct(
        public string $id,
        string $title,
        public string $author,
        public Isbn $isbn,
        public string $categoryId,
        public int $totalCopies,
        public int $availableCopies,
        public ?string $coverKey = null,
        public ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->title = trim($title);

        if ($this->title === '' || mb_strlen($this->title) > 250) {
            throw new InvalidArgumentException('El título es obligatorio y admite hasta 250 caracteres.');
        }

        if ($totalCopies < 1 || $availableCopies < 0 || $availableCopies > $totalCopies) {
            throw new InvalidArgumentException('Las cantidades de ejemplares no son válidas.');
        }
    }

    public function withdraw(): self
    {
        if ($this->deletedAt !== null || $this->availableCopies < 1) {
            throw new BookNotAvailable('No hay ejemplares disponibles.');
        }

        return $this->withCopies($this->totalCopies, $this->availableCopies - 1);
    }

    public function restock(): self
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw new CopyLimitExceeded('Los ejemplares disponibles no pueden superar el total.');
        }

        return $this->withCopies($this->totalCopies, $this->availableCopies + 1);
    }

    public function adjustTotalCopies(int $newTotal): self
    {
        $newAvailable = $this->availableCopies + ($newTotal - $this->totalCopies);

        if ($newTotal < 1 || $newAvailable < 0) {
            throw new CopyLimitExceeded('El total solicitado dejaría cantidades inválidas.');
        }

        return $this->withCopies($newTotal, $newAvailable);
    }

    public function softDelete(DateTimeImmutable $at): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->author,
            $this->isbn,
            $this->categoryId,
            $this->totalCopies,
            $this->availableCopies,
            $this->coverKey,
            $at,
        );
    }

    private function withCopies(int $total, int $available): self
    {
        return new self(
            $this->id,
            $this->title,
            $this->author,
            $this->isbn,
            $this->categoryId,
            $total,
            $available,
            $this->coverKey,
            $this->deletedAt,
        );
    }
}

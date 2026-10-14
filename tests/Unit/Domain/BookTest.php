<?php

namespace Tests\Unit\Domain;

use App\Domain\Books\Book;
use App\Domain\Books\BookNotAvailable;
use App\Domain\Books\CopyLimitExceeded;
use App\Domain\Books\Isbn;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    public function test_isbn_requires_exactly_thirteen_digits(): void
    {
        self::assertSame('9780307474728', (new Isbn('9780307474728'))->value);

        $this->expectException(InvalidArgumentException::class);
        new Isbn('978030747472');
    }

    public function test_withdraw_and_restock_preserve_inventory_bounds(): void
    {
        $book = $this->book(2, 1);

        self::assertSame(0, $book->withdraw()->availableCopies);
        self::assertSame(2, $book->restock()->availableCopies);

        $this->expectException(BookNotAvailable::class);
        $this->book(1, 0)->withdraw();
    }

    public function test_copy_total_changes_available_copies_by_the_same_difference(): void
    {
        $book = $this->book(5, 3);

        self::assertSame([7, 5], [$book->adjustTotalCopies(7)->totalCopies, $book->adjustTotalCopies(7)->availableCopies]);
        self::assertSame([3, 1], [$book->adjustTotalCopies(3)->totalCopies, $book->adjustTotalCopies(3)->availableCopies]);

        $this->expectException(CopyLimitExceeded::class);
        $book->adjustTotalCopies(1);
    }

    private function book(int $total, int $available): Book
    {
        return new Book('book-id', '  Title  ', 'Author', new Isbn('9780307474728'), 'category-id', $total, $available);
    }
}

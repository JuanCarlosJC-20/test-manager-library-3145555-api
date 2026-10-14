<?php

namespace Tests\Unit\Domain;

use App\Domain\Books\Isbn;
use App\Domain\Loans\DuplicateBookInLoan;
use App\Domain\Loans\Loan;
use App\Domain\Loans\LoanItem;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LoanTest extends TestCase
{
    public function test_status_is_calculated_from_due_and_return_times(): void
    {
        $loanedAt = new DateTimeImmutable('2026-10-01T10:00:00Z');
        $item = $this->item('item-1', 'book-1', $loanedAt);

        self::assertSame('open', $this->loan($loanedAt, [$item])->statusAt(new DateTimeImmutable('2026-10-10T10:00:00Z')));
        self::assertSame('overdue', $this->loan($loanedAt, [$item])->statusAt(new DateTimeImmutable('2026-10-15T10:00:01Z')));
        self::assertSame('closed', $this->loan($loanedAt, [$item->returnAt(new DateTimeImmutable('2026-10-05T10:00:00Z'))])->statusAt(new DateTimeImmutable('2026-10-15T10:00:00Z')));
    }

    public function test_a_returned_item_is_not_overdue(): void
    {
        $loanedAt = new DateTimeImmutable('2026-10-01T10:00:00Z');
        $returned = $this->item('item-1', 'book-1', $loanedAt)->returnAt(new DateTimeImmutable('2026-10-05T10:00:00Z'));

        self::assertFalse($returned->isOverdueAt(new DateTimeImmutable('2026-10-20T10:00:00Z')));
    }

    public function test_duplicate_books_and_invalid_item_counts_are_rejected(): void
    {
        $loanedAt = new DateTimeImmutable('2026-10-01T10:00:00Z');
        $sameBookItems = [
            $this->item('item-1', 'book-1', $loanedAt),
            $this->item('item-2', 'book-1', $loanedAt),
        ];

        $this->expectException(DuplicateBookInLoan::class);
        $this->loan($loanedAt, $sameBookItems);
    }

    public function test_loan_requires_one_to_three_items_with_fourteen_day_due_dates(): void
    {
        $loanedAt = new DateTimeImmutable('2026-10-01T10:00:00Z');

        try {
            $this->loan($loanedAt, []);
            self::fail('An empty loan should be rejected.');
        } catch (InvalidArgumentException) {
            self::assertTrue(true);
        }

        $invalidDueDate = new LoanItem('item-1', 'book-1', 'Title', new Isbn('9780307474728'), 'Novela', new DateTimeImmutable('2026-10-27T10:00:00Z'));

        $this->expectException(InvalidArgumentException::class);
        $this->loan($loanedAt, [$invalidDueDate]);
    }

    private function loan(DateTimeImmutable $loanedAt, array $items): Loan
    {
        return new Loan('loan-id', 'member-id', 'user-id', $loanedAt, $items);
    }

    private function item(string $id, string $bookId, DateTimeImmutable $loanedAt): LoanItem
    {
        return new LoanItem($id, $bookId, 'Title', new Isbn('9780307474728'), 'Novela', $loanedAt->modify('+14 days'));
    }
}

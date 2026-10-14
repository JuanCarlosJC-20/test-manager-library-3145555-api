<?php

namespace App\Domain\Loans;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Loan
{
    /** @param list<LoanItem> $items */
    public function __construct(
        public string $id,
        public string $memberId,
        public string $loanedByUserId,
        public DateTimeImmutable $loanedAt,
        public array $items,
    ) {
        if (count($items) < 1 || count($items) > 3) {
            throw new InvalidArgumentException('Un préstamo debe contener de uno a tres libros.');
        }

        $bookIds = array_map(static fn (LoanItem $item): string => $item->bookId, $items);

        if (count(array_unique($bookIds)) !== count($bookIds)) {
            throw new DuplicateBookInLoan('No se puede repetir un libro dentro del mismo préstamo.');
        }

        $expectedDueAt = $loanedAt->modify('+14 days');

        foreach ($items as $item) {
            if (! $item instanceof LoanItem || $item->dueAt != $expectedDueAt) {
                throw new InvalidArgumentException('La fecha de vencimiento debe ser 14 días después del préstamo.');
            }
        }
    }

    public function statusAt(DateTimeImmutable $now): string
    {
        foreach ($this->items as $item) {
            if (! $item->isReturned() && $item->isOverdueAt($now)) {
                return 'overdue';
            }
        }

        foreach ($this->items as $item) {
            if (! $item->isReturned()) {
                return 'open';
            }
        }

        return 'closed';
    }
}

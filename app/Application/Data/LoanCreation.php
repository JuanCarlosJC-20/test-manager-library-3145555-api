<?php

namespace App\Application\Data;

use DateTimeImmutable;

final readonly class LoanCreation
{
    /** @param list<string> $bookIds @param list<string> $itemIds */
    public function __construct(
        public string $loanId,
        public string $memberId,
        public string $userId,
        public array $bookIds,
        public array $itemIds,
        public DateTimeImmutable $loanedAt,
    ) {
    }
}

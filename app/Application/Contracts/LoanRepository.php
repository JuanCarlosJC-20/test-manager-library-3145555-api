<?php

namespace App\Application\Contracts;

use App\Application\Data\LoanCreation;
use App\Domain\Loans\Loan;
use App\Domain\Loans\LoanItem;
use DateTimeImmutable;

interface LoanRepository
{
    public function createLoan(LoanCreation $creation): Loan;

    public function find(string $id): ?Loan;

    /** @param array<string, string|null> $filters @return array{items: list<Loan>, page: int, size: int, total: int} */
    public function search(array $filters, int $page, int $size, DateTimeImmutable $now): array;

    public function returnItem(string $loanId, string $itemId, DateTimeImmutable $at): LoanItem;

    /** @return list<array<string, mixed>> */
    public function overdue(DateTimeImmutable $now): array;
}

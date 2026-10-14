<?php

namespace App\Application\UseCases;

use App\Application\Contracts\Clock;
use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\LoanRepository;
use App\Application\Data\LoanCreation;
use App\Domain\Loans\DuplicateBookInLoan;
use App\Domain\Loans\Loan;

final readonly class CreateLoan
{
    public function __construct(private LoanRepository $loans, private Clock $clock, private IdGenerator $ids)
    {
    }

    /** @param list<string> $bookIds */
    public function execute(string $memberId, string $userId, array $bookIds): Loan
    {
        if (count($bookIds) < 1 || count($bookIds) > 3) {
            throw new \InvalidArgumentException('Un préstamo debe contener de uno a tres libros.');
        }

        if (count(array_unique($bookIds)) !== count($bookIds)) {
            throw new DuplicateBookInLoan('No se puede repetir un libro dentro del mismo préstamo.');
        }

        return $this->loans->createLoan(new LoanCreation(
            $this->ids->generate(), $memberId, $userId, $bookIds,
            array_map(fn (): string => $this->ids->generate(), $bookIds), $this->clock->now(),
        ));
    }
}

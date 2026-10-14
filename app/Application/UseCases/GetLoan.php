<?php

namespace App\Application\UseCases;

use App\Application\Contracts\LoanRepository;
use App\Application\Exceptions\LoanNotFound;

final readonly class GetLoan
{
    public function __construct(private LoanRepository $loans)
    {
    }

    public function execute(string $id): \App\Domain\Loans\Loan
    {
        return $this->loans->find($id) ?? throw new LoanNotFound('No se encontró el préstamo.');
    }
}

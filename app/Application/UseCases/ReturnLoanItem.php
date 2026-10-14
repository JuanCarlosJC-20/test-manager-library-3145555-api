<?php

namespace App\Application\UseCases;

use App\Application\Contracts\Clock;
use App\Application\Contracts\LoanRepository;
use App\Domain\Loans\LoanItem;

final readonly class ReturnLoanItem
{
    public function __construct(private LoanRepository $loans, private Clock $clock)
    {
    }

    public function execute(string $loanId, string $itemId): LoanItem
    {
        return $this->loans->returnItem($loanId, $itemId, $this->clock->now());
    }
}

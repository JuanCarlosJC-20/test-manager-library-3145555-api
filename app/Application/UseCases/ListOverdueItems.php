<?php

namespace App\Application\UseCases;

use App\Application\Contracts\Clock;
use App\Application\Contracts\LoanRepository;

final readonly class ListOverdueItems
{
    public function __construct(private LoanRepository $loans, private Clock $clock)
    {
    }

    public function execute(): array
    {
        return $this->loans->overdue($this->clock->now());
    }
}

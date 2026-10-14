<?php

namespace App\Application\UseCases;

use App\Application\Contracts\Clock;
use App\Application\Contracts\LoanRepository;

final readonly class SearchLoans
{
    public function __construct(private LoanRepository $loans, private Clock $clock)
    {
    }

    public function execute(array $filters, int $page, int $size): array
    {
        return $this->loans->search($filters, $page, $size, $this->clock->now());
    }
}

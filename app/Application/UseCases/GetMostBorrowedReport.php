<?php

namespace App\Application\UseCases;

use App\Application\Contracts\MostBorrowedReport;
use App\Application\Exceptions\InvalidDateRange;

final readonly class GetMostBorrowedReport
{
    public function __construct(private MostBorrowedReport $report)
    {
    }

    public function execute(string $from, string $to): array
    {
        if ($to < $from) {
            throw new InvalidDateRange('La fecha final debe ser igual o posterior a la fecha inicial.');
        }

        return ['from' => $from, 'to' => $to, 'rows' => $this->report->forPeriod($from, $to)];
    }
}

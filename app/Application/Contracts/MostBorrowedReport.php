<?php

namespace App\Application\Contracts;

interface MostBorrowedReport
{
    /** @return list<array{book_id: string, book_title: string, category_name: string, times_borrowed: int}> */
    public function forPeriod(string $from, string $to): array;
}

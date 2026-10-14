<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Contracts\MostBorrowedReport;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final class EloquentMostBorrowedReport implements MostBorrowedReport
{
    public function forPeriod(string $from, string $to): array
    {
        $start = new DateTimeImmutable($from.' 00:00:00', new DateTimeZone('UTC'));
        $end = (new DateTimeImmutable($to.' 00:00:00', new DateTimeZone('UTC')))->modify('+1 day');

        return DB::table('loan_items')->join('loans', 'loans.id', '=', 'loan_items.loan_id')
            ->where('loans.loaned_at', '>=', EloquentLoanRepository::timestamp($start))
            ->where('loans.loaned_at', '<', EloquentLoanRepository::timestamp($end))
            ->groupBy('loan_items.book_id', 'loan_items.book_title', 'loan_items.category_name')
            ->orderByDesc('times_borrowed')->orderBy('loan_items.book_title')
            ->get([
                'loan_items.book_id',
                'loan_items.book_title',
                'loan_items.category_name',
                DB::raw('COUNT(*)::integer AS times_borrowed'),
            ])->map(static fn (object $row): array => [
                'book_id' => (string) $row->book_id,
                'book_title' => $row->book_title,
                'category_name' => $row->category_name,
                'times_borrowed' => (int) $row->times_borrowed,
            ])->all();
    }
}

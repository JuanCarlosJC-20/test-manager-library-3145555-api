<?php

namespace App\Adapters\Inbound\Http\Resources;

use App\Domain\Loans\Loan;
use DateTimeImmutable;

final class LoanResponse
{
    public static function from(Loan $loan, DateTimeImmutable $now): array
    {
        return [
            'id' => $loan->id,
            'member_id' => $loan->memberId,
            'loaned_at' => ApiTimestamp::format($loan->loanedAt),
            'status' => $loan->statusAt($now),
            'items' => array_map(static fn ($item): array => [
                'id' => $item->id,
                'book_id' => $item->bookId,
                'book_title' => $item->bookTitle,
                'isbn' => $item->isbn->value,
                'category_name' => $item->categoryName,
                'due_at' => ApiTimestamp::format($item->dueAt),
                'returned_at' => $item->returnedAt === null ? null : ApiTimestamp::format($item->returnedAt),
            ], $loan->items),
        ];
    }

    public static function item($item): array
    {
        return [
            'id' => $item->id,
            'book_id' => $item->bookId,
            'book_title' => $item->bookTitle,
            'isbn' => $item->isbn->value,
            'category_name' => $item->categoryName,
            'due_at' => ApiTimestamp::format($item->dueAt),
            'returned_at' => $item->returnedAt === null ? null : ApiTimestamp::format($item->returnedAt),
        ];
    }
}

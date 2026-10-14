<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Contracts\LoanRepository;
use App\Application\Data\LoanCreation;
use App\Application\Exceptions\BookNotFound;
use App\Application\Exceptions\LoanNotFound;
use App\Application\Exceptions\MemberHasOverdue;
use App\Application\Exceptions\MemberLimitExceeded;
use App\Application\Exceptions\MemberNotFound;
use App\Domain\Books\BookNotAvailable;
use App\Domain\Books\Isbn;
use App\Domain\Loans\ItemAlreadyReturned;
use App\Domain\Loans\Loan;
use App\Domain\Loans\LoanItem;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final class EloquentLoanRepository implements LoanRepository
{
    public function createLoan(LoanCreation $creation): Loan
    {
        return DB::transaction(function () use ($creation): Loan {
            $member = DB::table('members')->where('id', $creation->memberId)->lockForUpdate()->first();

            if ($member === null) {
                throw new MemberNotFound('No se encontró el miembro.');
            }

            $activeCount = DB::table('loan_items')->join('loans', 'loans.id', '=', 'loan_items.loan_id')
                ->where('loans.member_id', $creation->memberId)->whereNull('loan_items.returned_at')->count();
            $hasOverdue = DB::table('loan_items')->join('loans', 'loans.id', '=', 'loan_items.loan_id')
                ->where('loans.member_id', $creation->memberId)->whereNull('loan_items.returned_at')
                ->where('loan_items.due_at', '<', self::timestamp($creation->loanedAt))->exists();

            if ($hasOverdue) {
                throw new MemberHasOverdue('El miembro tiene préstamos vencidos pendientes de devolución.');
            }

            if ($activeCount + count($creation->bookIds) > 3) {
                throw new MemberLimitExceeded($activeCount);
            }

            $bookRows = DB::table('books')->whereIn('id', $creation->bookIds)->whereNull('deleted_at')
                ->orderBy('id')->lockForUpdate()->get();

            if ($bookRows->count() !== count($creation->bookIds)) {
                throw new BookNotFound('No se encontró uno de los libros solicitados.');
            }

            $categoryNames = DB::table('categories')->whereIn('id', $bookRows->pluck('category_id')->unique())
                ->pluck('name', 'id');

            foreach ($bookRows as $book) {
                if ((int) $book->available_copies < 1) {
                    throw new BookNotAvailable('No hay ejemplares disponibles de '.$book->title.'.');
                }
            }

            DB::table('loans')->insert([
                'id' => $creation->loanId,
                'member_id' => $creation->memberId,
                'loaned_by_user_id' => $creation->userId,
                'loaned_at' => self::timestamp($creation->loanedAt),
            ]);

            foreach ($creation->bookIds as $index => $bookId) {
                $book = $bookRows->firstWhere('id', $bookId);
                DB::table('books')->where('id', $bookId)->decrement('available_copies');
                DB::table('loan_items')->insert([
                    'id' => $creation->itemIds[$index],
                    'loan_id' => $creation->loanId,
                    'book_id' => $bookId,
                    'book_title' => $book->title,
                    'isbn' => $book->isbn,
                    'category_name' => $categoryNames[$book->category_id],
                    'due_at' => self::timestamp($creation->loanedAt->modify('+14 days')),
                    'returned_at' => null,
                ]);
            }

            return $this->find($creation->loanId) ?? throw new \RuntimeException('Created loan could not be reloaded.');
        });
    }

    public function find(string $id): ?Loan
    {
        $row = DB::table('loans')->where('id', $id)->first();

        if ($row === null) {
            return null;
        }

        $items = DB::table('loan_items')->where('loan_id', $id)->orderBy('id')->get();
        $loanItems = $items->map(static fn (object $item): LoanItem => new LoanItem(
            (string) $item->id,
            (string) $item->book_id,
            $item->book_title,
            new Isbn(trim($item->isbn)),
            $item->category_name,
            new DateTimeImmutable($item->due_at),
            $item->returned_at === null ? null : new DateTimeImmutable($item->returned_at),
        ))->all();

        return new Loan(
            (string) $row->id,
            (string) $row->member_id,
            (string) $row->loaned_by_user_id,
            new DateTimeImmutable($row->loaned_at),
            $loanItems,
        );
    }

    public function search(array $filters, int $page, int $size, DateTimeImmutable $now): array
    {
        $builder = DB::table('loans');
        if (! empty($filters['member_id'])) {
            $builder->where('member_id', $filters['member_id']);
        }
        if (! empty($filters['from'])) {
            $builder->where('loaned_at', '>=', $filters['from'].' 00:00:00+00');
        }
        if (! empty($filters['to'])) {
            $builder->where('loaned_at', '<', (new DateTimeImmutable($filters['to'].' 00:00:00', new DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d H:i:sP'));
        }

        $status = $filters['status'] ?? null;
        $overdueItems = static fn ($query) => $query->selectRaw('1')->from('loan_items')
            ->whereColumn('loan_items.loan_id', 'loans.id')->whereNull('loan_items.returned_at')
            ->where('loan_items.due_at', '<', self::timestamp($now));
        $openItems = static fn ($query) => $query->selectRaw('1')->from('loan_items')
            ->whereColumn('loan_items.loan_id', 'loans.id')->whereNull('loan_items.returned_at');
        if ($status === 'overdue') {
            $builder->whereExists($overdueItems);
        } elseif ($status === 'open') {
            $builder->whereExists($openItems)->whereNotExists($overdueItems);
        } elseif ($status === 'closed') {
            $builder->whereNotExists($openItems);
        }

        $total = (clone $builder)->count();
        $ids = $builder->orderByDesc('loaned_at')->forPage($page, $size)->pluck('id');
        $items = $ids->map(fn (string $id): ?Loan => $this->find($id))->filter()->values()->all();

        return ['items' => $items, 'page' => $page, 'size' => $size, 'total' => $total];
    }

    public function returnItem(string $loanId, string $itemId, DateTimeImmutable $at): LoanItem
    {
        return DB::transaction(function () use ($loanId, $itemId, $at): LoanItem {
            $loan = DB::table('loans')->where('id', $loanId)->first();
            if ($loan === null) {
                throw new LoanNotFound('No se encontró el préstamo.');
            }

            $member = DB::table('members')->where('id', $loan->member_id)->lockForUpdate()->first();
            if ($member === null) {
                throw new LoanNotFound('No se encontró el préstamo.');
            }

            $item = DB::table('loan_items')->where('loan_id', $loanId)->where('id', $itemId)->lockForUpdate()->first();

            if ($item === null) {
                throw new LoanNotFound('No se encontró el ejemplar del préstamo.');
            }
            if ($item->returned_at !== null) {
                throw new ItemAlreadyReturned('El ejemplar ya fue devuelto.');
            }

            $book = DB::table('books')->where('id', $item->book_id)->lockForUpdate()->first();
            if ($book === null || (int) $book->available_copies >= (int) $book->total_copies) {
                throw new \RuntimeException('Book inventory cannot be incremented.');
            }

            DB::table('loan_items')->where('id', $itemId)->update(['returned_at' => self::timestamp($at)]);
            DB::table('books')->where('id', $item->book_id)->increment('available_copies');

            return new LoanItem(
                (string) $item->id,
                (string) $item->book_id,
                $item->book_title,
                new Isbn(trim($item->isbn)),
                $item->category_name,
                new DateTimeImmutable($item->due_at),
                $at,
            );
        });
    }

    public function overdue(DateTimeImmutable $now): array
    {
        return DB::table('loan_items')->join('loans', 'loans.id', '=', 'loan_items.loan_id')
            ->join('members', 'members.id', '=', 'loans.member_id')
            ->whereNull('loan_items.returned_at')->where('loan_items.due_at', '<', self::timestamp($now))
            ->orderBy('loan_items.due_at')->get([
                'loans.id as loan_id', 'loan_items.id as item_id', 'loan_items.book_title',
                'members.full_name as member_name', 'loan_items.due_at',
            ])->map(static function (object $row) use ($now): array {
                $dueAt = new DateTimeImmutable($row->due_at);
                $secondsLate = max(1, $now->getTimestamp() - $dueAt->getTimestamp());

                return [
                    'loan_id' => (string) $row->loan_id,
                    'item_id' => (string) $row->item_id,
                    'book_title' => $row->book_title,
                    'member_name' => $row->member_name,
                    'due_at' => self::iso($dueAt),
                    'days_late' => (int) ceil($secondsLate / 86400),
                ];
            })->all();
    }

    public static function timestamp(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');
    }

    public static function iso(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}

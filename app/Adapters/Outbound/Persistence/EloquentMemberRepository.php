<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Contracts\MemberRepository;
use App\Application\Exceptions\DocumentTaken;
use App\Domain\Members\DocumentNumber;
use App\Domain\Members\Member;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class EloquentMemberRepository implements MemberRepository
{
    public function search(?string $query, int $page, int $size): array
    {
        $builder = DB::table('members');

        if ($query !== null && trim($query) !== '') {
            $term = '%'.trim($query).'%';
            $builder->where(static function ($where) use ($term): void {
                $where->where('full_name', 'ILIKE', $term)->orWhere('document_number', 'ILIKE', $term);
            });
        }

        $total = (clone $builder)->count();
        $items = $builder->orderBy('full_name')->forPage($page, $size)->get(['id', 'full_name', 'document_number'])
            ->map(static fn (object $row): array => [
                'id' => (string) $row->id,
                'full_name' => $row->full_name,
                'document_number' => $row->document_number,
            ])->all();

        return ['items' => $items, 'page' => $page, 'size' => $size, 'total' => $total];
    }

    public function find(string $id): ?Member
    {
        $row = DB::table('members')->where('id', $id)->first();

        return $row === null ? null : new Member((string) $row->id, $row->full_name, new DocumentNumber($row->document_number));
    }

    public function activeLoanCount(string $memberId): int
    {
        return DB::table('loan_items')->join('loans', 'loans.id', '=', 'loan_items.loan_id')
            ->where('loans.member_id', $memberId)->whereNull('loan_items.returned_at')->count();
    }

    public function create(Member $member): Member
    {
        try {
            DB::table('members')->insert([
                'id' => $member->id,
                'full_name' => $member->fullName,
                'document_number' => $member->documentNumber->value,
            ]);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                throw new DocumentTaken('El documento ya está registrado.');
            }

            throw $exception;
        }

        return $member;
    }
}

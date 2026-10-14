<?php

namespace App\Application\Contracts;

use App\Domain\Members\Member;

interface MemberRepository
{
    /** @return array{items: list<array<string, mixed>>, page: int, size: int, total: int} */
    public function search(?string $query, int $page, int $size): array;

    public function find(string $id): ?Member;

    public function activeLoanCount(string $memberId): int;

    public function create(Member $member): Member;
}

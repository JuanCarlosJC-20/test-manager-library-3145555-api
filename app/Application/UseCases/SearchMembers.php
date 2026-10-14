<?php

namespace App\Application\UseCases;

use App\Application\Contracts\MemberRepository;

final readonly class SearchMembers
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function execute(?string $query, int $page, int $size): array
    {
        return $this->members->search($query, $page, $size);
    }
}

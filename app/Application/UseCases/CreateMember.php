<?php

namespace App\Application\UseCases;

use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\MemberRepository;
use App\Application\Exceptions\DocumentTaken;
use App\Domain\Members\DocumentNumber;
use App\Domain\Members\Member;

final readonly class CreateMember
{
    public function __construct(private MemberRepository $members, private IdGenerator $ids)
    {
    }

    public function execute(string $fullName, string $documentNumber): Member
    {
        return $this->members->create(new Member(
            $this->ids->generate(), trim($fullName), new DocumentNumber(trim($documentNumber)),
        ));
    }
}

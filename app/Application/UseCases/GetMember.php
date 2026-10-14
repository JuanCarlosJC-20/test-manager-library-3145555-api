<?php

namespace App\Application\UseCases;

use App\Application\Contracts\MemberRepository;
use App\Application\Exceptions\MemberNotFound;

final readonly class GetMember
{
    public function __construct(private MemberRepository $members)
    {
    }

    public function execute(string $id): array
    {
        $member = $this->members->find($id) ?? throw new MemberNotFound('No se encontró el miembro.');

        return [
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber->value,
            'active_loans' => $this->members->activeLoanCount($id),
        ];
    }
}

<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\StoreMemberRequest;
use App\Application\UseCases\CreateMember;
use Illuminate\Http\JsonResponse;

final readonly class StoreMemberController
{
    public function __construct(private CreateMember $createMember)
    {
    }

    public function __invoke(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();
        $member = $this->createMember->execute($data['full_name'], $data['document_number']);

        return response()->json([
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber->value,
        ], 201);
    }
}

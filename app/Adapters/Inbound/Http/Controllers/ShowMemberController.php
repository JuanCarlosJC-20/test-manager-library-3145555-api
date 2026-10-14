<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\UseCases\GetMember;
use Illuminate\Http\JsonResponse;

final readonly class ShowMemberController
{
    public function __construct(private GetMember $getMember)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        return response()->json($this->getMember->execute($id));
    }
}

<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\StoreLibrarianRequest;
use App\Application\UseCases\CreateLibrarian;
use Illuminate\Http\JsonResponse;

final readonly class StoreLibrarianController
{
    public function __construct(private CreateLibrarian $createLibrarian) {}

    public function __invoke(StoreLibrarianRequest $request): JsonResponse
    {
        $user = $this->createLibrarian->execute(
            $request->string('username')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role->value,
        ], 201);
    }
}

<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\LoginRequest;
use App\Application\UseCases\Login;
use Illuminate\Http\JsonResponse;

final readonly class LoginController
{
    public function __construct(private Login $login) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $token = $this->login->execute($request->string('username')->toString(), $request->string('password')->toString());

        return response()->json([
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $token->expiresIn,
            'role' => $token->role->value,
        ]);
    }
}

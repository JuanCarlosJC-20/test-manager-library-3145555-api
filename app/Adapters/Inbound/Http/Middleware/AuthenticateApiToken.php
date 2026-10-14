<?php

namespace App\Adapters\Inbound\Http\Middleware;

use App\Application\Contracts\TokenVerifier;
use App\Application\Exceptions\InvalidToken;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateApiToken
{
    public function __construct(private TokenVerifier $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw new AuthenticationException;
        }

        try {
            $claims = $this->tokens->verify($token);
        } catch (InvalidToken) {
            throw new AuthenticationException;
        }

        $request->attributes->set('api_token_claims', $claims);

        return $next($request);
    }
}

<?php

namespace App\Adapters\Inbound\Http\Middleware;

use App\Application\Data\TokenClaims;
use App\Domain\Users\Role;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        $claims = $request->attributes->get('api_token_claims');
        $allowed = array_map(static fn (string $role): ?Role => Role::tryFrom($role), $allowedRoles);

        if (! $claims instanceof TokenClaims || ! in_array($claims->role, $allowed, true)) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}

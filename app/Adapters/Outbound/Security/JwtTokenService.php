<?php

namespace App\Adapters\Outbound\Security;

use App\Application\Contracts\Clock;
use App\Application\Contracts\TokenIssuer;
use App\Application\Contracts\TokenVerifier;
use App\Application\Data\IssuedToken;
use App\Application\Data\TokenClaims;
use App\Application\Exceptions\InvalidToken;
use App\Domain\Users\Role;
use App\Domain\Users\User;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

final readonly class JwtTokenService implements TokenIssuer, TokenVerifier
{
    private const int TOKEN_LIFETIME_SECONDS = 28800;

    public function __construct(private Clock $clock, private ?string $secret) {}

    public function issue(User $user): IssuedToken
    {
        $issuedAt = $this->clock->now()->getTimestamp();
        $expiresAt = $issuedAt + self::TOKEN_LIFETIME_SECONDS;
        $token = JWT::encode([
            'sub' => $user->id,
            'role' => $user->role->value,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ], $this->signingKey(), 'HS256');

        return new IssuedToken($token, self::TOKEN_LIFETIME_SECONDS, $user->role);
    }

    public function verify(string $token): TokenClaims
    {
        try {
            $claims = JWT::decode($token, new Key($this->signingKey(), 'HS256'));
            $subject = $claims->sub ?? null;
            $role = Role::tryFrom($claims->role ?? '');
            $expiresAt = $claims->exp ?? null;

            if (! is_string($subject) || $subject === '' || $role === null || ! is_int($expiresAt)) {
                throw new InvalidToken('El token no contiene las afirmaciones requeridas.');
            }

            return new TokenClaims($subject, $role, new DateTimeImmutable('@'.$expiresAt));
        } catch (InvalidToken $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidToken('El token no es válido.', previous: $exception);
        }
    }

    private function signingKey(): string
    {
        if ($this->secret === null || strlen($this->secret) < 32) {
            throw new RuntimeException('JWT_SECRET debe tener al menos 32 caracteres aleatorios.');
        }

        return $this->secret;
    }
}

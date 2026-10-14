<?php

namespace App\Application\UseCases;

use App\Application\Contracts\PasswordHasher;
use App\Application\Contracts\TokenIssuer;
use App\Application\Contracts\UserRepository;
use App\Application\Data\IssuedToken;
use App\Application\Exceptions\InvalidCredentials;

final readonly class Login
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwords,
        private TokenIssuer $tokens,
    ) {}

    public function execute(string $username, string $password): IssuedToken
    {
        $user = $this->users->findByUsername(mb_strtolower(trim($username)));

        if ($user === null) {
            $this->passwords->hash($password);

            throw new InvalidCredentials('Credenciales incorrectas.');
        }

        if (! $this->passwords->verify($password, $user->passwordHash)) {
            throw new InvalidCredentials('Credenciales incorrectas.');
        }

        return $this->tokens->issue($user);
    }
}

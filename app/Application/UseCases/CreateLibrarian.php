<?php

namespace App\Application\UseCases;

use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\PasswordHasher;
use App\Application\Contracts\UserRepository;
use App\Application\Exceptions\UsernameTaken;
use App\Domain\Users\Role;
use App\Domain\Users\User;

final readonly class CreateLibrarian
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwords,
        private IdGenerator $ids,
    ) {}

    public function execute(string $username, string $password): User
    {
        $normalizedUsername = mb_strtolower(trim($username));

        if ($this->users->findByUsername($normalizedUsername) !== null) {
            throw new UsernameTaken('Ese nombre de usuario ya está registrado.');
        }

        return $this->users->create(new User(
            $this->ids->generate(),
            $normalizedUsername,
            $this->passwords->hash($password),
            Role::Librarian,
        ));
    }
}

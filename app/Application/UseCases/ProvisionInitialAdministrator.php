<?php

namespace App\Application\UseCases;

use App\Application\Contracts\IdGenerator;
use App\Application\Contracts\PasswordHasher;
use App\Application\Contracts\UserRepository;
use App\Application\Exceptions\UsernameTaken;
use App\Domain\Users\Role;
use App\Domain\Users\User;
use RuntimeException;

final readonly class ProvisionInitialAdministrator
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwords,
        private IdGenerator $ids,
    ) {
    }

    public function execute(string $username, string $password): bool
    {
        $username = mb_strtolower(trim($username));
        if ($username === '' || $password === '') {
            throw new RuntimeException('ADMIN_USERNAME and ADMIN_PASSWORD must be configured.');
        }

        $existing = $this->users->findByUsername($username);
        if ($existing !== null) {
            if ($existing->role !== Role::Admin) {
                throw new RuntimeException('The configured administrator username is already used by a librarian.');
            }

            return false;
        }

        try {
            $this->users->create(new User(
                $this->ids->generate(), $username, $this->passwords->hash($password), Role::Admin,
            ));
        } catch (UsernameTaken) {
            $existing = $this->users->findByUsername($username);
            if ($existing?->role !== Role::Admin) {
                throw new RuntimeException('The configured administrator username is already used by a librarian.');
            }

            return false;
        }

        return true;
    }
}

<?php

namespace App\Application\Contracts;

use App\Domain\Users\User;

interface UserRepository
{
    public function findById(string $id): ?User;

    public function findByUsername(string $username): ?User;

    public function create(User $user): User;
}

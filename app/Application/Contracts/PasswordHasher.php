<?php

namespace App\Application\Contracts;

interface PasswordHasher
{
    public function hash(string $plainText): string;

    public function verify(string $plainText, string $hash): bool;
}

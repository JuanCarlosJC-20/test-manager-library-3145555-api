<?php

namespace App\Application\Data;

use App\Domain\Users\Role;

final readonly class IssuedToken
{
    public function __construct(public string $accessToken, public int $expiresIn, public Role $role) {}
}

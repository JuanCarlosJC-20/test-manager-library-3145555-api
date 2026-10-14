<?php

namespace App\Application\Data;

use App\Domain\Users\Role;
use DateTimeImmutable;

final readonly class TokenClaims
{
    public function __construct(public string $subject, public Role $role, public DateTimeImmutable $expiresAt) {}
}

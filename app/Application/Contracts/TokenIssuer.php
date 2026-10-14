<?php

namespace App\Application\Contracts;

use App\Application\Data\IssuedToken;
use App\Domain\Users\User;

interface TokenIssuer
{
    public function issue(User $user): IssuedToken;
}

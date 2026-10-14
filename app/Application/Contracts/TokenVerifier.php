<?php

namespace App\Application\Contracts;

use App\Application\Data\TokenClaims;

interface TokenVerifier
{
    public function verify(string $token): TokenClaims;
}

<?php

namespace App\Application\Exceptions;

use RuntimeException;

final class MemberLimitExceeded extends RuntimeException
{
    public function __construct(public readonly int $currentCount)
    {
        parent::__construct('El miembro superaría el límite de libros sin devolver.');
    }
}

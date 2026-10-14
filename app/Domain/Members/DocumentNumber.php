<?php

namespace App\Domain\Members;

use InvalidArgumentException;

final readonly class DocumentNumber
{
    public function __construct(public string $value)
    {
        if (trim($value) === '' || mb_strlen($value) > 30) {
            throw new InvalidArgumentException('El documento es obligatorio y admite hasta 30 caracteres.');
        }
    }
}

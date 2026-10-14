<?php

namespace App\Domain\Members;

use InvalidArgumentException;

final readonly class Member
{
    public string $fullName;

    public function __construct(public string $id, string $fullName, public DocumentNumber $documentNumber)
    {
        $this->fullName = trim($fullName);

        if ($this->fullName === '' || mb_strlen($this->fullName) > 200) {
            throw new InvalidArgumentException('El nombre es obligatorio y admite hasta 200 caracteres.');
        }
    }
}

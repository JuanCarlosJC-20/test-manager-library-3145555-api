<?php

namespace App\Domain\Users;

use InvalidArgumentException;

final readonly class User
{
    public string $username;

    public function __construct(public string $id, string $username, public string $passwordHash, public Role $role)
    {
        $this->username = mb_strtolower(trim($username));

        if ($this->username === '' || mb_strlen($this->username) > 120) {
            throw new InvalidArgumentException('El usuario es obligatorio y admite hasta 120 caracteres.');
        }

        if ($passwordHash === '' || mb_strlen($passwordHash) > 512) {
            throw new InvalidArgumentException('El hash de contraseña no es válido.');
        }
    }
}

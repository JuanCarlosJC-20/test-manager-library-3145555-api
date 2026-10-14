<?php

namespace App\Domain\Books;

use InvalidArgumentException;

final readonly class Isbn
{
    public function __construct(public string $value)
    {
        if (! preg_match('/\A[0-9]{13}\z/', $value)) {
            throw new InvalidArgumentException('El ISBN debe contener exactamente 13 dígitos.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

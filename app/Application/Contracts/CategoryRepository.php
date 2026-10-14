<?php

namespace App\Application\Contracts;

interface CategoryRepository
{
    /** @return list<array{id: string, name: string}> */
    public function listAll(): array;
}

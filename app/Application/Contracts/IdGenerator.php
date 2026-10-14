<?php

namespace App\Application\Contracts;

interface IdGenerator
{
    public function generate(): string;
}

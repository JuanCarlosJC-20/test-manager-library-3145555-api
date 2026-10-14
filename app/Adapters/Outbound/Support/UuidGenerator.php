<?php

namespace App\Adapters\Outbound\Support;

use App\Application\Contracts\IdGenerator;
use Ramsey\Uuid\Uuid;

final class UuidGenerator implements IdGenerator
{
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}

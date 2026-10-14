<?php

namespace App\Application\Data;

final readonly class StoredCover
{
    public function __construct(public string $contents, public string $mimeType) {}
}

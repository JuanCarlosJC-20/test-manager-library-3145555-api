<?php

namespace App\Application\Contracts;

use App\Application\Data\StoredCover;

interface CoverStorage
{
    public function store(string $contents, string $mimeType): string;

    public function retrieve(string $key): ?StoredCover;

    public function delete(string $key): void;
}

<?php

namespace App\Application\UseCases;

use App\Application\Contracts\CategoryRepository;

final readonly class ListCategories
{
    public function __construct(private CategoryRepository $categories) {}

    /** @return list<array{id: string, name: string}> */
    public function execute(): array
    {
        return $this->categories->listAll();
    }
}

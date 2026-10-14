<?php

namespace App\Application\UseCases;

use App\Application\Contracts\BookRepository;

final readonly class SearchBooks
{
    public function __construct(private BookRepository $books) {}

    public function execute(?string $query, ?string $categoryId, int $page, int $size): array
    {
        return $this->books->search($query, $categoryId, $page, $size);
    }
}

<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Resources\BookResponse;
use App\Application\Contracts\CategoryRepository;
use App\Application\UseCases\GetBook;
use Illuminate\Http\JsonResponse;

final readonly class ShowBookController
{
    public function __construct(private GetBook $getBook, private CategoryRepository $categories) {}

    public function __invoke(string $id): JsonResponse
    {
        $book = $this->getBook->execute($id);

        return response()->json(BookResponse::from($book, $this->categories->findName($book->categoryId) ?? ''));
    }
}

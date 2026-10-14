<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\StoreBookRequest;
use App\Adapters\Inbound\Http\Resources\BookResponse;
use App\Application\Contracts\CategoryRepository;
use App\Application\UseCases\CreateBook;
use Illuminate\Http\JsonResponse;

final readonly class StoreBookController
{
    public function __construct(private CreateBook $createBook, private CategoryRepository $categories) {}

    public function __invoke(StoreBookRequest $request): JsonResponse
    {
        $data = $request->validated();
        $book = $this->createBook->execute($data['title'], $data['author'], $data['isbn'], $data['category_id'], (int) $data['total_copies']);

        return response()->json(BookResponse::from($book, $this->categories->findName($book->categoryId) ?? ''), 201);
    }
}

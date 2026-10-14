<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\UpdateBookRequest;
use App\Adapters\Inbound\Http\Resources\BookResponse;
use App\Application\Contracts\CategoryRepository;
use App\Application\UseCases\UpdateBook;
use Illuminate\Http\JsonResponse;

final readonly class UpdateBookController
{
    public function __construct(private UpdateBook $updateBook, private CategoryRepository $categories) {}

    public function __invoke(UpdateBookRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $book = $this->updateBook->execute($id, $data['title'], $data['author'], $data['category_id'], (int) $data['total_copies']);

        return response()->json(BookResponse::from($book, $this->categories->findName($book->categoryId) ?? ''));
    }
}

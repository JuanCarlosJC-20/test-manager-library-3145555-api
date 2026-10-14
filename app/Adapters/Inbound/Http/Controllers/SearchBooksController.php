<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\SearchBooksRequest;
use App\Application\UseCases\SearchBooks;
use Illuminate\Http\JsonResponse;

final readonly class SearchBooksController
{
    public function __construct(private SearchBooks $searchBooks) {}

    public function __invoke(SearchBooksRequest $request): JsonResponse
    {
        return response()->json($this->searchBooks->execute(
            $request->validated('q'),
            $request->validated('category_id'),
            (int) $request->validated('page', 1),
            (int) $request->validated('size', 20),
        ));
    }
}

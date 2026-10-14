<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\SearchMembersRequest;
use App\Application\UseCases\SearchMembers;
use Illuminate\Http\JsonResponse;

final readonly class SearchMembersController
{
    public function __construct(private SearchMembers $searchMembers)
    {
    }

    public function __invoke(SearchMembersRequest $request): JsonResponse
    {
        return response()->json($this->searchMembers->execute(
            $request->validated('q'),
            (int) $request->validated('page', 1),
            (int) $request->validated('size', 20),
        ));
    }
}

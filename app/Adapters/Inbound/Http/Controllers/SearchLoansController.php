<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\SearchLoansRequest;
use App\Adapters\Inbound\Http\Resources\LoanResponse;
use App\Application\Contracts\Clock;
use App\Application\UseCases\SearchLoans;
use Illuminate\Http\JsonResponse;

final readonly class SearchLoansController
{
    public function __construct(private SearchLoans $searchLoans, private Clock $clock)
    {
    }

    public function __invoke(SearchLoansRequest $request): JsonResponse
    {
        $data = $request->validated();
        $filters = array_intersect_key($data, array_flip(['from', 'to', 'status', 'member_id']));
        $page = (int) ($data['page'] ?? 1);
        $size = (int) ($data['size'] ?? 20);
        $result = $this->searchLoans->execute($filters, $page, $size);
        $result['items'] = array_map(fn ($loan): array => LoanResponse::from($loan, $this->clock->now()), $result['items']);

        return response()->json($result);
    }
}

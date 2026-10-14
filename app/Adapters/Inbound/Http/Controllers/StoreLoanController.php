<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\StoreLoanRequest;
use App\Adapters\Inbound\Http\Resources\LoanResponse;
use App\Application\Contracts\Clock;
use App\Application\Data\TokenClaims;
use App\Application\UseCases\CreateLoan;
use Illuminate\Http\JsonResponse;

final readonly class StoreLoanController
{
    public function __construct(private CreateLoan $createLoan, private Clock $clock)
    {
    }

    public function __invoke(StoreLoanRequest $request): JsonResponse
    {
        $claims = $request->attributes->get('api_token_claims');
        $data = $request->validated();
        $loan = $this->createLoan->execute(
            $data['member_id'],
            $claims instanceof TokenClaims ? $claims->subject : '',
            array_column($data['items'], 'book_id'),
        );

        return response()->json(LoanResponse::from($loan, $this->clock->now()), 201);
    }
}

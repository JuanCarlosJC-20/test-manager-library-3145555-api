<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Resources\LoanResponse;
use App\Application\Contracts\Clock;
use App\Application\UseCases\GetLoan;
use Illuminate\Http\JsonResponse;

final readonly class ShowLoanController
{
    public function __construct(private GetLoan $getLoan, private Clock $clock)
    {
    }

    public function __invoke(string $id): JsonResponse
    {
        return response()->json(LoanResponse::from($this->getLoan->execute($id), $this->clock->now()));
    }
}

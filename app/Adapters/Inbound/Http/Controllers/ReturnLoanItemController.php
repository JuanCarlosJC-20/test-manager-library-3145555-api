<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Resources\LoanResponse;
use App\Application\UseCases\ReturnLoanItem;
use Illuminate\Http\JsonResponse;

final readonly class ReturnLoanItemController
{
    public function __construct(private ReturnLoanItem $returnLoanItem)
    {
    }

    public function __invoke(string $loan_id, string $item_id): JsonResponse
    {
        return response()->json(LoanResponse::item($this->returnLoanItem->execute($loan_id, $item_id)));
    }
}

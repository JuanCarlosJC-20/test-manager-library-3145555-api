<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\MostBorrowedReportRequest;
use App\Application\UseCases\GetMostBorrowedReport;
use Illuminate\Http\JsonResponse;

final readonly class MostBorrowedReportController
{
    public function __construct(private GetMostBorrowedReport $getMostBorrowedReport)
    {
    }

    public function __invoke(MostBorrowedReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json($this->getMostBorrowedReport->execute($data['from'], $data['to']));
    }
}

<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\UseCases\ListOverdueItems;
use Illuminate\Http\JsonResponse;

final readonly class ListOverdueItemsController
{
    public function __construct(private ListOverdueItems $listOverdueItems)
    {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json($this->listOverdueItems->execute());
    }
}

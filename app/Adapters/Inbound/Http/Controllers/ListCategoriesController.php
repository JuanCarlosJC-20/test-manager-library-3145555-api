<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\UseCases\ListCategories;
use Illuminate\Http\JsonResponse;

final readonly class ListCategoriesController
{
    public function __construct(private ListCategories $listCategories) {}

    public function __invoke(): JsonResponse
    {
        return response()->json($this->listCategories->execute());
    }
}

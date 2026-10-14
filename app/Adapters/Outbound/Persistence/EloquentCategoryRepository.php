<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Contracts\CategoryRepository;
use Illuminate\Support\Facades\DB;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function listAll(): array
    {
        return DB::table('categories')->orderBy('name')->get(['id', 'name'])
            ->map(static fn (object $row): array => ['id' => (string) $row->id, 'name' => $row->name])
            ->all();
    }
}

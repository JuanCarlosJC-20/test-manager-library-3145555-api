<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\UseCases\DeleteBookCover;
use Illuminate\Http\Response;

final readonly class DeleteBookCoverController
{
    public function __construct(private DeleteBookCover $deleteBookCover) {}

    public function __invoke(string $id): Response
    {
        $this->deleteBookCover->execute($id);

        return response()->noContent();
    }
}

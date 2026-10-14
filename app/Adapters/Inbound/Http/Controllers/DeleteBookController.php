<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\UseCases\DeleteBook;
use Illuminate\Http\Response;

final readonly class DeleteBookController
{
    public function __construct(private DeleteBook $deleteBook) {}

    public function __invoke(string $id): Response
    {
        $this->deleteBook->execute($id);

        return response()->noContent();
    }
}

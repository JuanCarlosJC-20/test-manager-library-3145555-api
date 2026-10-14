<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Application\UseCases\GetBookCover;
use Illuminate\Http\Response;

final readonly class ShowBookCoverController
{
    public function __construct(private GetBookCover $getBookCover) {}

    public function __invoke(string $id): Response
    {
        $cover = $this->getBookCover->execute($id);

        return response($cover->contents, 200, [
            'Content-Type' => $cover->mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

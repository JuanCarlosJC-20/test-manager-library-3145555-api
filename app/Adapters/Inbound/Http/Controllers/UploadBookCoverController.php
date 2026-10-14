<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Requests\UploadBookCoverRequest;
use App\Application\UseCases\UploadBookCover;
use Illuminate\Http\Response;

final readonly class UploadBookCoverController
{
    public function __construct(private UploadBookCover $uploadBookCover) {}

    public function __invoke(UploadBookCoverRequest $request, string $id): Response
    {
        $file = $request->file('file');
        $contents = $file->getContent();
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $this->uploadBookCover->execute($id, $contents, $mimeType);

        return response()->noContent();
    }
}

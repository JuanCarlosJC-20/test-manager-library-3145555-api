<?php

namespace App\Adapters\Inbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadBookCoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['file' => [
            'bail',
            'required',
            'file',
            'max:2048',
            function (string $attribute, mixed $file, \Closure $fail): void {
                if (! $file instanceof \Illuminate\Http\UploadedFile) {
                    return;
                }

                $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($file->getContent());
                if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                    $fail('El archivo debe ser una imagen JPG o PNG válida.');
                }
            },
        ]];
    }
}

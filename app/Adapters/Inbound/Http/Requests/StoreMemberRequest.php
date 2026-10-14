<?php

namespace App\Adapters\Inbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:200'],
            'document_number' => ['required', 'string', 'max:30'],
        ];
    }
}

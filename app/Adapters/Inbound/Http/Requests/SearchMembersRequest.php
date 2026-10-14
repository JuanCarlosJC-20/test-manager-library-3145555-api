<?php

namespace App\Adapters\Inbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SearchMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'string', 'max:230'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'size' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}

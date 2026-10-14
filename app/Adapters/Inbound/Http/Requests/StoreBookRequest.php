<?php

namespace App\Adapters\Inbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:250'],
            'author' => ['required', 'string', 'max:200'],
            'isbn' => ['required', 'regex:/\A[0-9]{13}\z/'],
            'category_id' => ['required', 'uuid'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ];
    }
}

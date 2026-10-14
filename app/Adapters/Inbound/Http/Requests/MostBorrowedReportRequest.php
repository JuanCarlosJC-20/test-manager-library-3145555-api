<?php

namespace App\Adapters\Inbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MostBorrowedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ];
    }
}

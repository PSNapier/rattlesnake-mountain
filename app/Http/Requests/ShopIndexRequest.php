<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['sometimes', Rule::in(['default', 'name', 'price'])],
            'dir' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}

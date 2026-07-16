<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'visible_in_shop' => ['sometimes', 'boolean'],
            'scorpion_price' => ['required', 'integer', 'min:0'],
            'shop_description' => ['nullable', 'string'],
            'shop_flavor_text' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'scorpion_price.min' => 'Scorpion price cannot be negative.',
        ];
    }
}

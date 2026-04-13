<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id', 'unique:shop_listings,item_id'],
            'visible_in_shop' => ['sometimes', 'boolean'],
            'scorpion_price' => ['required', 'integer', 'min:0'],
            'shop_description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.unique' => 'This item already has a shop listing.',
            'scorpion_price.min' => 'Scorpion price cannot be negative.',
        ];
    }
}

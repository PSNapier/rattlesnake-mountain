<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $itemId = $this->input('item_id');
        $item = Item::find($itemId);

        $maxCount = $item?->max_count ?? 999;

        return [
            'item_id' => 'required|exists:items,id',
            'quantity' => "required|integer|min:0|max:{$maxCount}",
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Item is required.',
            'item_id.exists' => 'Selected item does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity cannot be negative.',
            'quantity.max' => "Quantity cannot exceed the item's max count.",
        ];
    }
}

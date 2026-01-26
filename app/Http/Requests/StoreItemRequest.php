<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:items,name',
            'max_count' => 'required|integer|min:1|max:999',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Item name is required.',
            'name.unique' => 'An item with this name already exists.',
            'max_count.required' => 'Max count is required.',
            'max_count.min' => 'Max count must be at least 1.',
            'max_count.max' => 'Max count cannot exceed 999.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.cms') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:menu_items,id'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
        ];
    }
}

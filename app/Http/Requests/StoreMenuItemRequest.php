<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creates a ghost row. Page rows are created with their page, never here.
 */
class StoreMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('admin.cms') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            // A top-level entry is clickable in the header, so it needs
            // somewhere to go.
            'path' => [Rule::requiredIf(fn () => $this->input('parent_id') === null), 'nullable', 'string', 'max:2048'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:menu_items,id',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (MenuItem::query()->whereKey($value)->whereNotNull('parent_id')->exists()) {
                        $fail('Dropdowns only go one level deep.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

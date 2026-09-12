<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
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
        /** @var MenuItem $menuItem */
        $menuItem = $this->route('menuItem');

        return [
            'label' => ['required', 'string', 'max:255'],
            // A page row's target is its page. Only a top-level ghost row
            // would be left pointing nowhere.
            'path' => [
                Rule::requiredIf(fn () => ! $menuItem->isPageRow() && $this->input('parent_id') === null),
                'nullable',
                'string',
                'max:2048',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:menu_items,id',
                function (string $attribute, mixed $value, Closure $fail) use ($menuItem) {
                    if ((int) $value === $menuItem->id
                        || $menuItem->children()->exists()
                        || MenuItem::query()->whereKey($value)->whereNotNull('parent_id')->exists()) {
                        $fail('Dropdowns only go one level deep.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

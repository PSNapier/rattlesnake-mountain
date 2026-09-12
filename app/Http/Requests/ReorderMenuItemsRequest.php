<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * One drop in the navbar tree: the full order of the list an entry landed
 * in, and which dropdown that list belongs to (null for top level).
 */
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

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $ids = array_map('intval', $this->input('order', []));
                $parentId = $this->input('parent_id');

                if ($parentId !== null) {
                    $parentIsChild = MenuItem::query()->whereKey($parentId)->whereNotNull('parent_id')->exists();
                    $movingParents = in_array((int) $parentId, $ids, true)
                        || MenuItem::query()->whereIn('parent_id', $ids)->exists();

                    if ($parentIsChild || $movingParents) {
                        $validator->errors()->add('order', 'Dropdowns only go one level deep.');
                    }

                    return;
                }

                $bare = MenuItem::query()
                    ->whereIn('id', $ids)
                    ->whereNull('cms_page_id')
                    ->where(fn ($query) => $query->whereNull('path')->orWhere('path', ''))
                    ->exists();

                if ($bare) {
                    $validator->errors()->add('order', 'A top-level entry needs a page or a link.');
                }
            },
        ];
    }
}

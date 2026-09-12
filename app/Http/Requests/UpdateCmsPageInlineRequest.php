<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The inline editor's payload.
 *
 * Deliberately narrower than UpdateCmsPageRequest: `slug` and `visibility` are
 * absent, so editing a page in place can never move it or publish it. Those
 * stay deliberate actions taken from the page list.
 */
class UpdateCmsPageInlineRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string'],
            'content' => ['present', 'array'],
            'content.*' => ['array'],
            'content.*.id' => ['nullable', 'string', 'max:64'],
            'content.*.width' => ['required', 'string', 'in:third,half,two-thirds,full'],
            'content.*.style' => ['required', 'string', 'in:box,box-alt,box-centered,band'],
            'content.*.kind' => ['nullable', 'string', 'in:news'],
            'content.*.html' => ['present', 'nullable', 'string'],
        ];
    }
}

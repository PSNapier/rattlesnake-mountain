<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCmsPageRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:255', 'unique:cms_pages,slug'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string'],
            'content' => ['required', 'array'],
            'content.*' => ['array'],
            'content.*.id' => ['nullable', 'string', 'max:64'],
            'content.*.span' => ['required', 'integer', 'in:1,2,3'],
            'content.*.style' => ['required', 'string', 'in:box,box-alt,box-centered'],
            'content.*.html' => ['present', 'string'],
            'coming_soon' => ['nullable', 'boolean'],
        ];
    }
}

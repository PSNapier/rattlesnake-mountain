<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCmsPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
            'content.*.*' => ['string'],
            'images' => ['nullable', 'array'],
            'images.*.name' => ['required_with:images', 'string', 'max:255'],
            'images.*.link' => ['nullable', 'string', 'max:2048'],
            'images.*.path' => ['required_with:images', 'string', 'max:2048'],
        ];
    }
}

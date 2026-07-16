<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

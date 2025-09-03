<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CharacterImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'image' => 'required|image|mimes:png|max:2048',
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Please select an image to upload.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Only PNG images are allowed.',
            'image.max' => 'The image must be smaller than 2MB.',
            'alt_text.max' => 'Alt text must be shorter than 255 characters.',
            'description.max' => 'Description must be shorter than 1000 characters.',
        ];
    }
}

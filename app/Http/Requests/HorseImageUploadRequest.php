<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HorseImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $maxSize = $this->user()?->isAdmin() ? 10240 : 2048; // 10MB for admins, 2MB for users

        return [
            'image' => 'required|image|mimes:png,jpg,jpeg|max:'.$maxSize,
        ];
    }

    public function messages(): array
    {
        $maxSizeMB = $this->user()?->isAdmin() ? 10 : 2;

        return [
            'image.required' => 'Please select an image to upload.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Only PNG, JPG, and JPEG images are allowed.',
            'image.max' => "The image must be smaller than {$maxSizeMB}MB.",
        ];
    }
}

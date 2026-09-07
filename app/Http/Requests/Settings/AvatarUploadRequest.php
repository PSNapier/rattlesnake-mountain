<?php

namespace App\Http\Requests\Settings;

use App\Support\UploadLimit;
use Illuminate\Foundation\Http\FormRequest;

class AvatarUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxSize = UploadLimit::kilobytesFor($this->user());

        return [
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:'.$maxSize,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxSizeMB = UploadLimit::megabytesFor($this->user());

        return [
            'avatar.required' => 'Please select an image to upload.',
            'avatar.image' => 'The file must be an image.',
            'avatar.mimes' => 'Only JPEG, PNG, and WebP images are allowed.',
            'avatar.max' => "The image must be smaller than {$maxSizeMB}MB.",
        ];
    }
}

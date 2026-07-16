<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class BioUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bio' => [
                'nullable',
                'string',
                'max:5000',
                function ($attribute, $value, $fail) {
                    if ($value && $this->containsUrl($value)) {
                        $fail('The bio cannot contain URLs or links.');
                    }
                },
            ],
        ];
    }

    /**
     * Check if the text contains any URLs.
     */
    private function containsUrl(string $text): bool
    {
        // Check for markdown links [text](url)
        if (preg_match('/\[([^\]]+)\]\(([^)]+)\)/', $text)) {
            return true;
        }

        // Check for plain URLs (http, https, www)
        if (preg_match('/\b(https?:\/\/|www\.)[^\s]+/i', $text)) {
            return true;
        }

        // Check for domain-like patterns (domain.tld)
        if (preg_match('/\b[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*\.[a-z]{2,}\b/i', $text)) {
            return true;
        }

        return false;
    }
}

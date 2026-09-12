<?php

namespace App\Http\Requests;

use App\Support\CmsSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.cms') ?? false;
    }

    /**
     * The body is rich text from the CMS editor. Sanitizing here keeps the
     * controller on one write path and the model away from raw HTML.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => CmsSanitizer::sanitize($this->input('body'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // The same prose costs more characters once it carries tags.
            'body' => ['required', 'string', 'max:30000'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}

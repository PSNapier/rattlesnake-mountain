<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBreedingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sire_id' => ['required', 'integer', 'exists:horses,id', 'different:dam_id'],
            'dam_id' => ['required', 'integer', 'exists:horses,id'],
            'evidence_url' => ['required', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sire_id.required' => 'Select a sire.',
            'dam_id.required' => 'Select a dam.',
            'sire_id.different' => 'Sire and dam must be different horses.',
            'evidence_url.required' => 'An art or story URL is required.',
            'evidence_url.url' => 'Evidence must be a valid URL.',
        ];
    }
}

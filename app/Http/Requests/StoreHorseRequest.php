<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHorseRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:50',
            'design_link' => 'nullable|string|max:500',
            'geno' => 'required|string|max:255',
            'herd_id' => 'nullable|exists:herds,id',
            'bloodline' => 'nullable|array',
            'progeny' => 'nullable|array',
            'stats' => 'nullable|array',
            'inventory' => 'nullable|array',
            'equipment' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The horse name is required.',
            'name.max' => 'The horse name must not exceed 255 characters.',
            'age.required' => 'The horse age is required.',
            'age.integer' => 'The horse age must be a number.',
            'age.min' => 'The horse age must be at least 0.',
            'age.max' => 'The horse age must not exceed 50.',
            'design_link.max' => 'The design link must not exceed 500 characters.',
            'geno.required' => 'The geno string is required.',
            'geno.max' => 'The geno string must not exceed 255 characters.',
            'herd_id.exists' => 'The selected herd does not exist.',
        ];
    }
}

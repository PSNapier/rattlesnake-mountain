<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHerdRequest extends FormRequest
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
            'herd_leader_id' => 'nullable|exists:horses,id',
            'herd_members' => 'nullable|array',
            'inventory' => 'nullable|array',
            'equipment' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The herd name is required.',
            'name.max' => 'The herd name must not exceed 255 characters.',
            'herd_leader_id.exists' => 'The selected herd leader does not exist.',
        ];
    }
}

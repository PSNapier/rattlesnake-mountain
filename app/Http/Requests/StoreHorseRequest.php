<?php

namespace App\Http\Requests;

use App\Enums\HorseSex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHorseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sex' => ['required', Rule::enum(HorseSex::class)],
            'age_years' => 'required|integer|min:0|max:50',
            'age_months' => 'required|integer|min:0|max:11',
            'design_link' => 'nullable|string|max:500',
            'geno' => 'required|string|max:255',
            'herd_id' => 'nullable|exists:herds,id',
            'stats' => 'nullable|array',
            'inventory' => 'nullable|array',
            'equipment' => 'nullable|array',
        ];
    }

    public function ageMonthsTotal(): int
    {
        return ((int) $this->validated('age_years') * 12) + (int) $this->validated('age_months');
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The horse name is required.',
            'name.max' => 'The horse name must not exceed 255 characters.',
            'sex.required' => 'The horse sex is required.',
            'age_years.required' => 'The horse age in years is required.',
            'age_years.integer' => 'The horse age in years must be a number.',
            'age_years.min' => 'The horse age in years must be at least 0.',
            'age_years.max' => 'The horse age in years must not exceed 50.',
            'age_months.required' => 'The horse age in months is required.',
            'age_months.integer' => 'The horse age in months must be a number.',
            'age_months.min' => 'The horse age in months must be at least 0.',
            'age_months.max' => 'The horse age in months must not exceed 11.',
            'design_link.max' => 'The design link must not exceed 500 characters.',
            'geno.required' => 'The geno string is required.',
            'geno.max' => 'The geno string must not exceed 255 characters.',
            'herd_id.exists' => 'The selected herd does not exist.',
        ];
    }
}

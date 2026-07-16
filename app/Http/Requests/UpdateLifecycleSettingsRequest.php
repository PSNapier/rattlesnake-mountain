<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLifecycleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'horse_auto_age_next_update' => 'required|date',
            'horse_auto_age_frequency_unit' => ['required', Rule::in(['weeks', 'months'])],
            'horse_auto_age_frequency_value' => [
                'required',
                'integer',
                'min:1',
                Rule::when($this->horse_auto_age_frequency_unit === 'months', 'max:12', 'max:52'),
            ],
            'horse_auto_age_game_years' => 'required|numeric|min:0.25|max:10',
            'horse_auto_health_roll_min' => 'required|integer|min:0|max:100|lte:horse_auto_health_roll_max',
            'horse_auto_health_roll_max' => 'required|integer|min:0|max:100|gte:horse_auto_health_roll_min',
        ];
    }
}

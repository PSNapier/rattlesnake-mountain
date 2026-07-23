<?php

namespace App\Http\Requests;

use App\Enums\HorseSex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFoalFromBreedingRequest extends FormRequest
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
            'option_index' => ['required', 'integer', 'min:0', 'max:1'],
            'name' => ['required', 'string', 'max:255'],
            'sex' => ['required', Rule::enum(HorseSex::class)],
            'design_link' => ['nullable', 'string', 'max:500'],
            'herd_id' => ['nullable', 'integer', 'exists:herds,id'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\HorseSex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHorseSexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.rollers') === true
            || $this->user()?->can('admin.submissions') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sex' => ['required', Rule::enum(HorseSex::class)],
        ];
    }
}

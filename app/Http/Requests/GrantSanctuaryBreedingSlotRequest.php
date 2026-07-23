<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantSanctuaryBreedingSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.rollers') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'breeding_slot_id' => ['required', 'integer', 'exists:breeding_slots,id'],
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

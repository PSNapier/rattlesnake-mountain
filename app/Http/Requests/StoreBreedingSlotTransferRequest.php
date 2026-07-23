<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBreedingSlotTransferRequest extends FormRequest
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
            'breeding_slot_id' => ['required', 'integer', 'exists:breeding_slots,id'],
            'to_user_id' => ['required', 'integer', 'exists:users,id', 'different:'.$this->user()?->id],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

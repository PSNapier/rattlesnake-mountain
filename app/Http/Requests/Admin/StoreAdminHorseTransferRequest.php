<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminHorseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The `can:admin.horses` middleware on the route is the gate. A staff member
        // who got this far may move any horse, including to the Sanctuary.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn (Builder $query) => $query->whereNull('deleted_at')),
            ],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}

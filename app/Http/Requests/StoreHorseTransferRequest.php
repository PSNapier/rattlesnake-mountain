<?php

namespace App\Http\Requests;

use App\Models\Horse;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHorseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $horse = $this->route('horse');
        $user = $this->user();

        return $user !== null
            && $horse instanceof Horse
            && (int) $horse->owner_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $senderId = (int) $this->user()->id;

        return [
            // The recipient rule is the same one trading uses: every active player,
            // excluding self, banned users and the Sanctuary.
            'to_user_id' => [
                'required',
                'integer',
                'different:'.$senderId,
                Rule::exists('users', 'id')->where(function (Builder $query) use ($senderId) {
                    $query->whereNull('deleted_at')
                        ->whereNull('banned_at')
                        ->where('is_sanctuary', false)
                        ->where('id', '!=', $senderId);
                }),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_user_id.exists' => 'That player cannot receive a horse.',
        ];
    }
}

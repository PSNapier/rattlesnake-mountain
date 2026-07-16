<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedeemCreamPearlVoucherRequest extends FormRequest
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
        $choiceKeys = array_keys(config('vouchers.Cream/Pearl Stone Voucher.choices', config('welcome-package.voucher.choices', [])));

        return [
            'choice' => ['required', 'string', Rule::in($choiceKeys)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'choice.required' => 'Please choose Cream or Pearl.',
            'choice.in' => 'Choice must be cream or pearl.',
        ];
    }
}

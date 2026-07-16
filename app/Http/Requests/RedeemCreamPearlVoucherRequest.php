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
        return [
            'choice' => ['required', 'string', Rule::in(array_keys(config('welcome-package.voucher.choices')))],
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

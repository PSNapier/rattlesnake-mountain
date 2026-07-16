<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RedeemVoucherRequest extends FormRequest
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
        $voucherName = (string) $this->input('voucher');
        $allowedVouchers = array_keys(config('vouchers', []));
        $choiceKeys = $this->choiceKeysFor($voucherName);

        return [
            'voucher' => ['required', 'string', Rule::in($allowedVouchers)],
            'choice' => ['required', 'string', Rule::in($choiceKeys)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'voucher.required' => 'Please select a voucher to redeem.',
            'voucher.in' => 'That voucher type is not redeemable.',
            'choice.required' => 'Please choose an item to redeem.',
            'choice.in' => 'That choice is not valid for this voucher.',
        ];
    }

    /**
     * @return list<string>
     */
    private function choiceKeysFor(string $voucherName): array
    {
        $config = config("vouchers.{$voucherName}");

        if (! is_array($config)) {
            return [];
        }

        if (isset($config['choices']) && is_array($config['choices'])) {
            return array_keys($config['choices']);
        }

        if (($config['source'] ?? null) === 'shop_catalog') {
            return $this->herbNamesFromShopCatalog();
        }

        return $config['items'] ?? [];
    }

    /**
     * @return list<string>
     */
    private function herbNamesFromShopCatalog(): array
    {
        $path = database_path('data/shop_catalog.json');

        if (! is_readable($path)) {
            return [];
        }

        try {
            /** @var list<array{name: string}> $rows */
            $rows = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn (array $row): string => $row['name'],
            $rows
        )));
    }
}

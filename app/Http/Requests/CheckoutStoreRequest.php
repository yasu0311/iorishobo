<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Support\NumericInput;
use App\Support\Prefectures;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutStoreRequest extends FormRequest
{
    /** 電話番号・携帯: 半角数字とハイフンのみ */
    private const PHONE_PATTERN = '/^[0-9\-]+$/';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::ruleSet();
    }

    /**
     * @return array<string, mixed>
     */
    public static function ruleSet(): array
    {
        $prefectures = Prefectures::all();

        return [
            'buyer_name' => 'required|string|max:20',
            'buyer_name_kana' => 'nullable|string|max:20',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => ['nullable', 'string', 'max:15', 'regex:'.self::PHONE_PATTERN, 'required_without:buyer_mobile'],
            'buyer_mobile' => ['nullable', 'string', 'max:15', 'regex:'.self::PHONE_PATTERN, 'required_without:buyer_phone'],
            'buyer_postal_code' => 'required|string|digits:7',
            'buyer_prefecture' => ['required', 'string', Rule::in($prefectures)],
            'buyer_address_line1' => 'required|string|max:50',
            'buyer_address_line2' => 'nullable|string|max:40',
            'shipping_name' => 'nullable|string|max:20|required_with:shipping_postal_code,shipping_address_line1',
            'shipping_name_kana' => 'nullable|string|max:20',
            'shipping_phone' => ['nullable', 'string', 'max:15', 'regex:'.self::PHONE_PATTERN, 'required_with:shipping_name'],
            'shipping_postal_code' => 'nullable|string|digits:7|required_with:shipping_name',
            'shipping_prefecture' => ['nullable', 'string', 'required_with:shipping_name', Rule::in($prefectures)],
            'shipping_address_line1' => 'nullable|string|max:50|required_with:shipping_name',
            'shipping_address_line2' => 'nullable|string|max:40',
            'shipping_method_id' => 'required|integer|exists:shipping_methods,id',
            'payment_method' => ['required', Rule::in(array_map(
                fn (PaymentMethod $method) => $method->value,
                array_filter(PaymentMethod::cases(), fn (PaymentMethod $m) => $m->isAvailableAtCheckout()),
            ))],
            'customer_note' => 'nullable|string|max:1000',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::normalizeInput($this->all()));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function validatePayload(array $input): array
    {
        $input = self::normalizeInput($input);

        return validator($input, self::ruleSet())->validate();
    }

    /**
     * 郵便番号・電話を半角数字（とハイフン）に揃える。
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        foreach (['buyer_postal_code', 'shipping_postal_code'] as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            if (! is_string($input[$key]) || $input[$key] === '') {
                $input[$key] = null;

                continue;
            }

            $input[$key] = NumericInput::normalizePostalCode($input[$key]);
        }

        foreach (['buyer_phone', 'buyer_mobile', 'shipping_phone'] as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            if (! is_string($input[$key]) || $input[$key] === '') {
                $input[$key] = null;

                continue;
            }

            $input[$key] = NumericInput::normalizePhone($input[$key]);
        }

        // 配送先未使用時の空セレクトが Rule::in で落ちないようにする
        if (array_key_exists('shipping_prefecture', $input) && $input['shipping_prefecture'] === '') {
            $input['shipping_prefecture'] = null;
        }

        return $input;
    }
}

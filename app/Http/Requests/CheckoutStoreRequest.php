<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutStoreRequest extends FormRequest
{
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
        return [
            'buyer_name' => 'required|string|max:100',
            'buyer_name_kana' => 'nullable|string|max:100',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'nullable|string|max:20|required_without:buyer_mobile',
            'buyer_mobile' => 'nullable|string|max:20|required_without:buyer_phone',
            'buyer_postal_code' => 'required|string|size:7',
            'buyer_prefecture' => 'required|string|max:20',
            'buyer_address_line1' => 'required|string|max:255',
            'buyer_address_line2' => 'nullable|string|max:255',
            'shipping_name' => 'nullable|string|max:100|required_with:shipping_postal_code,shipping_address_line1',
            'shipping_name_kana' => 'nullable|string|max:100',
            'shipping_phone' => 'nullable|string|max:20|required_with:shipping_name',
            'shipping_postal_code' => 'nullable|string|size:7|required_with:shipping_name',
            'shipping_prefecture' => 'nullable|string|max:20|required_with:shipping_name',
            'shipping_address_line1' => 'nullable|string|max:255|required_with:shipping_name',
            'shipping_address_line2' => 'nullable|string|max:255',
            'shipping_method_id' => 'required|integer|exists:shipping_methods,id',
            'payment_method' => ['required', Rule::in(array_map(
                fn (PaymentMethod $method) => $method->value,
                array_filter(PaymentMethod::cases(), fn (PaymentMethod $m) => $m->isAvailableAtCheckout()),
            ))],
            'customer_note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function validatePayload(array $input): array
    {
        return validator($input, self::ruleSet())->validate();
    }
}

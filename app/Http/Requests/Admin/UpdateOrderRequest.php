<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
        return [
            'buyer_name' => 'required|string|max:100',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'nullable|string|max:20|required_without:buyer_mobile',
            'buyer_mobile' => 'nullable|string|max:20|required_without:buyer_phone',
            'buyer_postal_code' => 'required|string|size:7',
            'buyer_prefecture' => 'required|string|max:20',
            'buyer_address_line1' => 'required|string|max:255',
            'buyer_address_line2' => 'nullable|string|max:255',
            'shipping_name' => 'required|string|max:100',
            'shipping_name_kana' => 'nullable|string|max:100',
            'shipping_phone' => 'required|string|max:20',
            'shipping_postal_code' => 'required|string|size:7',
            'shipping_prefecture' => 'required|string|max:20',
            'shipping_address_line1' => 'required|string|max:255',
            'shipping_address_line2' => 'nullable|string|max:255',
            'customer_note' => 'nullable|string|max:1000',
            'shipping_note' => 'nullable|string|max:1000',
            'tracking_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.unit_price' => 'nullable|integer|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.remove' => 'boolean',
            'mark_as_paid' => 'boolean',
            'mark_as_shipped' => 'boolean',
            'cancel_reason' => 'nullable|string|max:1000',
            'refund_stripe' => 'boolean',
            'refund_amount' => 'nullable|integer|min:1',
            'refund_reason' => 'nullable|string|max:1000|required_with:refund_amount',
            'refund_manual_only' => 'boolean',
            'refund_restore_inventory' => 'boolean',
            'watchlist_reason' => 'nullable|string|max:2000',
        ];
    }
}

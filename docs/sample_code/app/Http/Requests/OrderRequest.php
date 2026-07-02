<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        // 残高利用：未入力や空文字の場合は 0 として扱う
        if ($this->has('points_paid')) {
            $value = $this->points_paid;

            // 文字列の場合はカンマを除去
            if (is_string($value)) {
                $value = str_replace(',', '', $value);
            }

            // 空文字や null の場合は 0 に正規化
            if ($value === '' || $value === null) {
                $value = 0;
            }

            $merge['points_paid'] = $value;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'member_id' => 'required|integer|exists:members,id',
            'usage' => 'required|integer|in:1,2,3',
            'licence' => 'required|string|max:1000',
            'price' => 'required|integer|min:0',
            'quantity' => 'required|integer|min:1',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'points_paid' => 'required|integer|min:0|max:' . (auth()->user()?->member?->getCurrentBalance() ?? 0),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => '商品IDは必須です。',
            'product_id.exists' => '指定された商品が存在しません。',
            'product_name.max' => '商品名は20文字以内で入力してください。',
            'member_id.required' => 'メンバーIDは必須です。',
            'member_id.exists' => '指定されたメンバーが存在しません。',
            'usage.in' => '利用区分は1（個人利用）、2（学校利用）、3（商用利用）のいずれかを選択してください。',
            'price.required' => '価格は必須です。',
            'price.min' => '価格は0以上で入力してください。',
            'quantity.required' => '数量は必須です。',
            'quantity.min' => '数量は1以上で入力してください。',
            'tax_rate.required' => '税率は必須です。',
            'tax_rate.min' => '税率は0以上で入力してください。',
            'tax_rate.max' => '税率は100以下で入力してください。',
            'licence.required' => '購入権利者を入力してください。',
            'licence.max' => 'ライセンス情報は1000文字以内で入力してください。',
            'remark.max' => '備考は500文字以内で入力してください。',
            'points_paid.max' => '残高利用は現在の残高を超えることはできません。',

        ];
    }

    public function attributes(): array
    {
        return [
            'member_id' => '会員ID',
            'product_id' => '商品ID',
            'receipt_number' => '領収番号',
            'status' => '状態',
            'payment_method' => '支払方法',
            'transaction_id' => '取引ID',
            'usage' => '利用法',
            'licence' => '購入権利者',
            'price' => '単価(税抜)',
            'quantity' => '数量',
            'tax_rate' => '消費税率',
            'tax_amount' => '消費税額',
            'total_amount' => '合計金額',
            'points_paid' => '残高利用',
            'amount_paid' => '支払金額',
            'transaction_fee' => '取引手数料',
            'ordered_at' => '注文日時',
            'remark' => '備考',
            'token' => 'トークン',
            'status' => '状態',
            'canceled_at' => 'キャンセル日時',
            'ip_address' => 'IPアドレス',
        ];
    }
    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $validator->errors()->forget('member_id');

            // 残高利用が合計金額（税込）を超えていないかチェック（支払金額がマイナスにならないようにする）
            $price = (int) $this->input('price', 0);
            $quantity = (int) $this->input('quantity', 1);
            $taxRate = (float) $this->input('tax_rate', 0);
            $pointsPaid = (int) $this->input('points_paid', 0);

            $taxAmount = (int) round($price * $quantity * $taxRate);
            $totalAmount = $price * $quantity + $taxAmount;

            if ($pointsPaid > $totalAmount) {
                $validator->errors()->add(
                    'points_paid',
                    '残高利用は合計金額（' . number_format($totalAmount) . '円）を超えることはできません。'
                );
            }
        });
    }
}

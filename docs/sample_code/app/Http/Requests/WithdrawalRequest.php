<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Setting;

class WithdrawalRequest extends FormRequest
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
        if ($this->has('amount') && is_string($this->amount)) {
            $this->merge([
                'amount' => str_replace(',', '', $this->amount),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $minWithdrawalAmount = Setting::getValue('minimum_withdrawal_amount') ?? 1;
        $maxWithdrawalAmount = auth()->user()?->member?->getCurrentBalance() ?? 0;

        return [
            'amount' => 'required|integer|min:' . $minWithdrawalAmount . '|max:' . $maxWithdrawalAmount,
            'bank_name' => 'required|string|max:30',
            'branch_name' => 'required|string|max:30',
            'account_type' => 'required|integer|in:1,2,3',
            'account_holder' => 'required|string|max:30',
            'account_number' => 'required|string|max:7|regex:/^[0-9]+$/',
            'comment' => 'nullable|string|max:1000',
            'mobile_phone' => 'required|string|regex:/^[0-9]{10,11}$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'amount.required' => '出金額は必須です。',
            'amount.integer' => '出金額は数値で入力してください。',
            'amount.min' => '出金額は最低出金金額以上で入力してください。',
            'amount.max' => '出金額は出金可能額を超えることはできません。',
            'bank_name.required' => '金融機関名は必須です。',
            'bank_name.max' => '金融機関名は30文字以内で入力してください。',
            'branch_name.required' => '支店名は必須です。',
            'branch_name.max' => '支店名は30文字以内で入力してください。',
            'account_type.required' => '口座種別は必須です。',
            'account_type.integer' => '口座種別は数値で入力してください。',
            'account_type.in' => '口座種別は1（普通）、2（当座）、3（貯蓄）のいずれかを選択してください。',
            'account_holder.required' => '口座名義人は必須です。',
            'account_holder.max' => '口座名義人は30文字以内で入力してください。',
            'account_number.required' => '口座番号は必須です。',
            'account_number.max' => '口座番号は7文字以内で入力してください。',
            'account_number.regex' => '口座番号は半角数字で入力してください。',
            'comment.max' => 'コメントは1000文字以内で入力してください。',
            'mobile_phone.required' => '携帯電話番号は必須です。',
            'mobile_phone.regex' => '携帯電話番号は10桁または11桁の半角数字で入力してください。',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => 'required|integer|exists:members,id',
            'amount' => 'required|integer|min:1',
            'deposited_at' => 'required|dateTime',
            'deposit_reason' => 'nullable|string|max:255',
            'remark' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'member_id.required' => 'メンバーIDは必須です。',
            'member_id.exists' => '指定されたメンバーが存在しません。',
            'amount.required' => '入金額は必須です。',
            'amount.min' => '入金額は1以上で入力してください。',
            'deposit_date.required' => '入金日は必須です。',
            'deposit_reason.max' => '入金理由は255文字以内で入力してください。',
            'remark.max' => '備考は255文字以内で入力してください。',
        ];
    }
}

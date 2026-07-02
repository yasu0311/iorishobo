<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    /** @return list<string> */
    public static function inquiryTypes(): array
    {
        return [
            '商品について',
            '注文・配送について',
            '返品・交換について',
            '会員登録・ログインについて',
            'その他',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'inquiry_type' => ['required', 'string', 'max:50', Rule::in(self::inquiryTypes())],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'お名前は必須です。',
            'name.max' => 'お名前は:max文字以内で入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.max' => 'メールアドレスは:max文字以内で入力してください。',
            'inquiry_type.required' => 'お問い合わせ種類を選択してください。',
            'inquiry_type.in' => 'お問い合わせ種類の選択が正しくありません。',
            'message.required' => 'お問い合わせ内容は必須です。',
            'message.max' => 'お問い合わせ内容は:max文字以内で入力してください。',
        ];
    }
}

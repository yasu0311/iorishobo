<?php

namespace App\Http\Requests;

use App\Models\Contact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
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
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'inquiry_type' => ['required', 'string', 'max:50', Rule::in(Contact::getInquiryTypes())],
            'message' => 'required|string|max:1000',
        ];
    }

    /**
     * Get the validation error messages for the defined rules.
     *
     * @return array<string, string>
     */
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
            'inquiry_type.max' => 'お問い合わせ種類は:max文字以内で入力してください。',
            'message.required' => 'お問い合わせ内容は必須です。',
            'message.max' => 'お問い合わせ内容は:max文字以内で入力してください。',
        ];
    }
}

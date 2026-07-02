<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
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
            'product_id' => 'required|integer|exists:products,id',
            'title' => 'required|string|max:20',
            'public_sender' => 'required|integer|in:0,1',
            'message' => 'required|string|max:1000',
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
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは20文字以内で入力してください。',
            'user_id.required' => 'ユーザーIDは必須です。',
            'user_id.exists' => '指定されたユーザーが存在しません。',
            'message.required' => 'メッセージは必須です。',
            'message.max' => 'メッセージは1000文字以内で入力してください。',
            'public_sender.in' => '送信者公開フラグは0または1で入力してください。',
            'public_shop.in' => 'ショップ公開フラグは0または1で入力してください。',
        ];
    }
}

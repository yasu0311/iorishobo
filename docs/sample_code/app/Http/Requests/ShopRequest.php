<?php

namespace App\Http\Requests;

use App\Models\ConsumptionTax;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShopRequest extends FormRequest
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
            'shop_name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('shops', 'shop_name')->ignore(auth()->user()?->member?->shop?->id),
            ],
            'shop_status' => 'required|integer|in:1,2,3',
            'shop_icon' => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,svg,webp|max:2048',
            'shop_information' => 'nullable|string|max:1000',
            'shop_introduction' => 'nullable|string|max:1000',
            'receipt_description' => 'nullable|string|max:1000',
            'url' => 'nullable|string|max:255|url',
            'consumption_tax_classification_id' => [
                'required',
                'integer',
                Rule::in(array_keys(ConsumptionTax::getClassificationsForSelect())),
            ],
            'admin_reply' => 'required|integer|in:0,1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shop_name.required' => 'ショップ名は必須です。',
            'shop_name.unique' => 'ショップ名「:input」はすでに使用されています。',
            'shop_name.max' => 'ショップ名は20文字以内で入力してください。',
            'shop_status.required' => '開店状況の入力が不正です。',
            'shop_status.integer' => '開店状況の入力が不正です。',
            'shop_status.in' => '開店状況の入力が不正です。',
            'shop_icon.image' => 'ショップアイコンは画像ファイルで入力してください。',
            'shop_icon.mimes' => 'ショップアイコンはjpeg,jpg,png,gif,bmp,svg,webpのいずれかの形式で入力してください。',
            'shop_icon.max' => 'ショップアイコンは2MB以内にしてください。',
            'shop_information.max' => 'ショップ情報は1000文字以内で入力してください。',
            'shop_introduction.max' => 'ショップ紹介は1000文字以内で入力してください。',
            'receipt_description.max' => 'レシート説明は1000文字以内で入力してください。',
            'url.url' => 'URLの形式が正しくありません。',
            'consumption_tax_classification_id.required' => '消費税区分の入力が不正です。',
            'consumption_tax_classification_id.integer' => '消費税区分の入力が不正です。',
            'consumption_tax_classification_id.in' => '消費税区分の入力が不正です。',
            'admin_reply.required' => '管理者返信可否の入力が不正です。',
            'admin_reply.integer' => '管理者返信可否の入力が不正です。',
            'admin_reply.in' => '管理者返信可否の入力が不正です。',
        ];
    }
}

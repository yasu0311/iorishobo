<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
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
        $rules = [
            'nickname' => 'required|string|max:15|unique:members,nickname,' . auth()->user()?->member?->id,
            'company' => 'required|integer|in:0,1',
            'last_name' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'last_name_kana' => 'required|string|max:50|regex:/^[ァ-ヶー\s]+$/u',
            'first_name_kana' => 'required|string|max:50|regex:/^[ァ-ヶー\s]+$/u',
            'postal_code' => 'required|string|size:7|regex:/^\d{7}$/',
            'address_prefecture' => 'required|string|max:10',
            'address_city' => 'required|string|max:255',
            'address_block' => 'required|string|max:255',
            'address_building' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:16|regex:/^[0-9-]+$/',
            'member_icon' => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,svg,webp|max:2048',
            'message_notification' => 'required|integer|in:0,1',
        ];
        
        if ($this->company == 1) {
            $rules['company_name'] = 'required|string|max:50';
            $rules['company_name_kana'] = 'required|string|max:100|regex:/^[ァ-ヶー\s]+$/u';
            $rules['company_postal_code'] = 'required|string|size:7|regex:/^\d{7}$/';
            $rules['company_prefecture'] = 'required|string|max:10';
            $rules['company_city'] = 'required|string|max:255';
            $rules['company_block'] = 'required|string|max:255';
            $rules['company_building'] = 'nullable|string|max:255';
            $rules['company_phone_number'] = 'required|string|max:16|regex:/^[0-9-]+$/';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nickname.required' => '公開名は必須です。',
            'nickname.string' => '公開名は文字列で入力してください。',
            'nickname.max' => '公開名は15文字以内で入力してください。',
            'nickname.unique' => 'この公開名は既に使用されています。',
    
            'company.required' => '区分（個人・法人）は必須です。',
            'company.integer' => '区分（個人・法人）は整数で指定してください。',
            'company.in' => '区分（個人・法人）の値が不正です。',
    
            'last_name.required' => '姓は必須です。',
            'last_name.string' => '姓は文字列で入力してください。',
            'last_name.max' => '姓は50文字以内で入力してください。',
    
            'first_name.required' => '名は必須です。',
            'first_name.string' => '名は文字列で入力してください。',
            'first_name.max' => '名は50文字以内で入力してください。',
    
            'last_name_kana.required' => '姓（フリガナ）は必須です。',
            'last_name_kana.string' => '姓（フリガナ）は文字列で入力してください。',
            'last_name_kana.max' => '姓（フリガナ）は50文字以内で入力してください。',
            'last_name_kana.regex' => '姓（フリガナ）は全角カタカナで入力してください。',
    
            'first_name_kana.required' => '名（フリガナ）は必須です。',
            'first_name_kana.string' => '名（フリガナ）は文字列で入力してください。',
            'first_name_kana.max' => '名（フリガナ）は50文字以内で入力してください。',
            'first_name_kana.regex' => '名（フリガナ）は全角カタカナで入力してください。',
    
            'postal_code.required' => '郵便番号は必須です。',
            'postal_code.string' => '郵便番号は文字列で入力してください。',
            'postal_code.size' => '郵便番号は7桁の数字で入力してください。',
            'postal_code.regex' => '郵便番号は数字のみで入力してください。',
    
            'address_prefecture.required' => '都道府県は必須です。',
            'address_prefecture.string' => '都道府県は文字列で入力してください。',
            'address_prefecture.max' => '都道府県は10文字以内で入力してください。',
    
            'address_city.required' => '市区町村は必須です。',
            'address_city.string' => '市区町村は文字列で入力してください。',
            'address_city.max' => '市区町村は255文字以内で入力してください。',
    
            'address_block.required' => '番地・丁目は必須です。',
            'address_block.string' => '番地・丁目は文字列で入力してください。',
            'address_block.max' => '番地・丁目は255文字以内で入力してください。',
    
            'address_building.string' => '建物名は文字列で入力してください。',
            'address_building.max' => '建物名は255文字以内で入力してください。',
    
            'phone_number.required' => '電話番号は必須です。',
            'phone_number.string' => '電話番号は文字列で入力してください。',
            'phone_number.max' => '電話番号は16文字以内で入力してください。',
            'phone_number.regex' => '電話番号は数字とハイフンのみで入力してください。',
    
            'company_name.required' => '法人名は必須です。',
            'company_name.string' => '法人名は文字列で入力してください。',
            'company_name.max' => '法人名は50文字以内で入力してください。',
    
            'company_name_kana.required' => '法人名（フリガナ）は必須です。',
            'company_name_kana.string' => '法人名（フリガナ）は文字列で入力してください。',
            'company_name_kana.max' => '法人名（フリガナ）は100文字以内で入力してください。',
            'company_name_kana.regex' => '法人名（フリガナ）は全角カタカナで入力してください。',

            'company_postal_code.required' => '本店の郵便番号は必須です。',
            'company_postal_code.size' => '本店の郵便番号は7文字で入力してください。',
            'company_postal_code.regex' => '本店の郵便番号は数字のみで入力してください。',

            'company_prefecture.required' => '本店の都道府県は必須です。',
            'company_prefecture.string' => '本店の都道府県は文字列で入力してください。',
            'company_prefecture.max' => '本店の都道府県は10文字以内で入力してください。',
    
            'company_city.required' => '本店の市区町村は必須です。',
            'company_city.string' => '本店の市区町村は文字列で入力してください。',
            'company_city.max' => '本店の市区町村は255文字以内で入力してください。',
    
            'company_block.required' => '本店の本店の番地・丁目は必須です。',
            'company_block.string' => '本店の番地・丁目は文字列で入力してください。',
            'company_block.max' => '本店の番地・丁目は255文字以内で入力してください。',
    
            'company_building.string' => '本店の建物名は文字列で入力してください。',
            'company_building.max' => '本店の建物名は255文字以内で入力してください。',
    
            'company_phone_number.required' => '法人の電話番号は必須です。',
            'company_phone_number.string' => '法人の電話番号は数字とハイフンのみで入力してください。',
            'company_phone_number.max' => '法人の電話番号は16文字以内で入力してください。',
            'company_phone_number.regex' => '法人の電話番号は数字とハイフンのみで入力してください。',

            'member_icon.image' => 'アイコンは画像ファイルで指定してください。',
            'member_icon.mimes' => 'アイコンはjpeg, jpg, png, gif, bmp, svg, webp形式で指定してください。',
            'member_icon.max' => 'アイコンは2MB以内で指定してください。',

            'message_notification.required' => 'メッセージ通知設定は必須です。',
            'message_notification.integer' => 'メッセージ通知設定は整数で指定してください。',
            'message_notification.in' => 'メッセージ通知設定の値が不正です。',
            ];
    }
}

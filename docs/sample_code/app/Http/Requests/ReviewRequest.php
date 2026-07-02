<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
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
            'order_id' => 'required|integer|exists:orders,id',
            'rating' => 'required|integer|in:1,2,3,4,5',
            'review' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_id.required' => '注文IDは必須です。',
            'order_id.exists' => '指定された注文が存在しません。',
            'rating.required' => '評価は必須です。',
            'rating.in' => '評価は1から5のいずれかを選択してください。',
            'review.max' => 'レビューは500文字以内で入力してください。',
        ];
    }
}

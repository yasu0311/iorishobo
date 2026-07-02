<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;
use App\Models\Product;
use App\Models\Setting;


class ProductRequest extends FormRequest
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
        foreach (['price_for_personal', 'price_for_school', 'price_for_commercial'] as $key) {
            if ($this->has($key) && is_string($this->$key)) {
                $merge[$key] = str_replace(',', '', $this->$key);
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
        // storeメソッドの場合のみ、現在のユーザーのショップIDを自動設定
        if ($this->isMethod('post')) {
            $user = Auth::user();
            if ($user && $user->member && $user->member->shop) {
                $this->merge([
                    'shop_id' => $user->member->shop->id,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'product_status' => 'required|integer|in:0,1,2',


            'product_name' => 'required|string|max:20',
            'product_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,bmp,svg,webp|max:2048',
            'product_summary' => 'nullable|string|max:40',
            'product_description' => 'required|string|max:2000',
            'update_information' => 'nullable|string',
            // 0円は常に許可。0円以外の場合は minimum_listing_price 以上（withValidator で検証）
            'price_for_personal' => 'nullable|integer|min:0',
            'price_for_commercial' => 'nullable|integer|min:0',
            'price_for_school' => 'nullable|integer|min:0',
            'display_order' => 'nullable|integer|min:0',
            'subjects' => 'required|array|min:1',
            'subjects.*' => 'required|integer|exists:subjects,id',
            'grades' => 'required|array|min:1',
            'grades.*' => 'required|integer|exists:grades,id',
            'file_types' => 'required|array|min:1',
            'file_types.*' => 'required|integer|exists:file_types,id',
        ];

        // storeメソッドの場合のみshop_idを必須にする
        if ($this->isMethod('post')) {
            $rules['shop_id'] = 'required|integer|exists:shops,id';
        }

        return $rules;
    }



    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shop_id.required' => 'ショップIDは必須です。',
            'shop_id.exists' => '指定されたショップが存在しません。',
            'product_status.required' => '状態は必須です。',
            'product_status.in' => '状態は準備中、販売中、販売終了のいずれかを選択してください。',
            'product_name.required' => '商品名は必須です。',
            'product_name.max' => '商品名は20文字以内で入力してください。',
            'product_summary.max' => '商品概要は40文字以内で入力してください。',
            'product_description.required' => '商品説明は必須です。',
            'product_description.max' => '商品説明は2000文字以内で入力してください。',
            'product_image.image' => '商品画像は画像ファイルを選択してください。',
            'product_image.mimes' => '商品画像の形式はjpeg、jpg、png、gif、bmp、svg、webpのいずれかを選択してください。',
            'product_image.max' => '商品画像のサイズは2MB以内にしてください。',
            'price_for_personal.min' => '個人利用価格は0円以上で入力してください。',
            'price_for_commercial.min' => '商用利用価格は0円以上で入力してください。',
            'price_for_school.min' => '学校利用価格は0円以上で入力してください。',
            'subjects.required' => '教科は最低1つ選択してください。',
            'subjects.array' => '教科の選択形式が正しくありません。',
            'subjects.min' => '教科は最低1つ選択してください。',
            'subjects.*.exists' => '選択された教科が存在しません。',
            'grades.required' => '学年は最低1つ選択してください。',
            'grades.array' => '学年の選択形式が正しくありません。',
            'grades.min' => '学年は最低1つ選択してください。',
            'grades.*.exists' => '選択された学年が存在しません。',
            'file_types.required' => 'ファイル種類は最低1つ選択してください。',
            'file_types.array' => 'ファイル種類の選択形式が正しくありません。',
            'file_types.min' => 'ファイル種類は最低1つ選択してください。',
            'file_types.*.exists' => '選択されたファイル種類が存在しません。',
        ];
    }

    /**
     * ファイル数が0の場合、商品の状態は「販売中」に変できない
     */
    public function withValidator($validator)
    {
        $validator->after(function (Validator $validator) {
            $product = $this->route('product');
            $fileCount = 0;

            if ($product) {
                $fileCount = $product->productFiles()->count();
            }
            // ファイル数が 0 のとき、product_status = 1 を禁止
            if ($fileCount === 0 && (int)$this->input('product_status') === 1) {
                $validator->errors()->add(
                    'product_status',
                    '商品ファイルが 1 つも登録されていないため、状態を「販売中」にすることはできません。'
                );
            }

            // listing_limit: 販売中（status=1 かつ limited=0）の件数のみ。準備中→販売中へ変えるときに上限検証
            if ($product instanceof Product) {
                $wasSelling = (int) $product->product_status === 1 && (int) $product->product_limited === 0;
                $willBeSelling = (int) $this->input('product_status') === 1 && (int) $product->product_limited === 0;
                if (! $wasSelling && $willBeSelling) {
                    $shop = $product->shop;
                    if ($shop) {
                        $listingCap = $shop->listingLimitFromSettings();
                        if ($listingCap !== null) {
                            $otherSellingCount = $shop->sellingProductsCount($product->id);
                            if ($otherSellingCount >= $listingCap) {
                                $validator->errors()->add(
                                    'product_status',
                                    "販売中にできる商品は最大{$listingCap}件までです。"
                                );
                            }
                        }
                    }
                }
            }

            // 価格: 0円は常に許可。0円以外の場合は settings で定義した最小/最大範囲内であること
            $minimumListingPrice = (int) (Setting::getValue('minimum_listing_price') ?? 0);
            $maximumListingPrice = (int) (Setting::getValue('maximum_listing_price') ?? 2147483647);
            if ($maximumListingPrice < $minimumListingPrice) {
                $maximumListingPrice = $minimumListingPrice;
            }
            $priceKeys = ['price_for_personal', 'price_for_commercial', 'price_for_school'];
            $priceLabels = [
                'price_for_personal' => '個人利用価格',
                'price_for_commercial' => '商用利用価格',
                'price_for_school' => '学校利用価格',
            ];
            foreach ($priceKeys as $key) {
                $value = $this->input($key);
                if ($value !== null && $value !== '' && (int)$value !== 0) {
                    if ((int)$value < $minimumListingPrice) {
                        $validator->errors()->add(
                            $key,
                            "{$priceLabels[$key]}は0円、または{$minimumListingPrice}円以上で入力してください。"
                        );
                    }
                    if ((int)$value > $maximumListingPrice) {
                        $validator->errors()->add(
                            $key,
                            "{$priceLabels[$key]}は{$maximumListingPrice}円以下で入力してください。"
                        );
                    }
                }
            }
        });
    }
}

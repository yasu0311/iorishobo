<?php

namespace App\Http\Controllers\Member\Sell;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;

        // ショップ全体のアップロード容量（使用量 / 上限）
        $usedCapacityBytes = $shop->totalProductFilesBytes();
        $uploadCapBytes = $shop->totalUploadBytesCapFromSettings();
        $totalCapacityMb = $uploadCapBytes !== null ? (int) floor($uploadCapBytes / 1000000) : null;
        $usedCapacityMb = (int) floor($usedCapacityBytes / 1000000);
        
        // 販売中の商品（product_status = 1 かつ product_limited = 0）
        $sellingProducts = $shop->products()
            ->where('product_status', 1)
            ->where('product_limited', 0)
            ->withCount(['orders', 'messages'])
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // その他の商品（product_status != 1 または product_limited = 1）
        $otherProducts = $shop->products()
            ->where(function($query) {
                $query->where('product_status', '!=', 1)
                      ->orWhere('product_limited', 1);
            })
            ->withCount(['orders', 'messages'])
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('member.sell.products.index', compact(
            'sellingProducts',
            'otherProducts',
            'shop',
            'usedCapacityMb',
            'totalCapacityMb'
        ));
    }

    public function store(Request $request)
    {
        // 商品名のバリデーション
        $validated = $request->validate([
            'product_name' => 'required|string|max:20',
        ], [
            'product_name.required' => '商品名を入力してください。',
            'product_name.max' => '商品名は20文字以内で入力してください。',
        ]);
        
        // 現在のユーザーのショップを取得
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;

        // デフォルト値で商品を作成
        $product = Product::create([
            'shop_id' => $shop->id,
            'product_limited' => 0, // 販売可
            'product_status' => 0, // 準備中
            'product_name' => $validated['product_name'],
            'product_image' => null,
            'product_summary' => null,
            'product_description' => '', // NOT NULLなので空文字を設定
            'update_information' => null,
            'price_for_personal' => 1000,
            'price_for_commercial' => 1000,
            'price_for_school' => 1000,
            'display_order' => 0,
            'ranking' => null,
            'rating_average' => null,
        ]);
        
        return redirect()->route('member.sell.products.index')
            ->with('success', '商品「' . $validated['product_name'] . '」を登録しました。編集ボタンを押して商品を編集してください。');
    }
    public function edit(Product $product)
    {
        // 商品の所属ショップが本人のものであることを認証
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($product->shop_id !== $shop->id) {
            abort(403, 'この商品を編集する権限がありません。');
        }

        // リレーションを読み込む
        $product->load([
            'subjects', 
            'grades', 
            'fileTypes', 
            'productFiles' => function($query) {
                $query->orderedByDisplay();
            }
        ]);

        // 全教科、学年、ファイルタイプを取得
        $subjects = \App\Models\Subject::orderBy('display_order')->get();
        $grades = \App\Models\Grade::orderBy('display_order')->get();
        $fileTypes = \App\Models\FileType::orderBy('id')->get();

        // 現在選択されているIDを取得
        $selectedSubjects = $product->subjects->pluck('id')->toArray();
        $selectedGrades = $product->grades->pluck('id')->toArray();
        $selectedFileTypes = $product->fileTypes->pluck('id')->toArray();
        $minimumListingPrice = (int) (Setting::getValue('minimum_listing_price') ?? 0);
        $maximumListingPrice = (int) (Setting::getValue('maximum_listing_price') ?? 2147483647);
        if ($maximumListingPrice < $minimumListingPrice) {
            $maximumListingPrice = $minimumListingPrice;
        }

        $productFilesLimit = Setting::getValue('product_files_limit', $shop->id);
        $productFileCount = $product->productFiles->count();

        return view('member.sell.products.edit', compact(
            'product',
            'subjects',
            'grades',
            'fileTypes',
            'selectedSubjects',
            'selectedGrades',
            'selectedFileTypes',
            'minimumListingPrice',
            'maximumListingPrice',
            'productFilesLimit',
            'productFileCount'
        ));
    }
    public function update(ProductRequest $request, Product $product)
    {
        // 商品の所属ショップが本人のものであることを認証
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($product->shop_id !== $shop->id) {
            abort(403, 'この商品を編集する権限がありません。');
        }

        $validated = $request->validated();

        // 商品画像のアップロード処理
        if ($request->hasFile('product_image')) {
            // 既存の画像を削除
            if ($product->product_image && Storage::disk('public')->exists($product->product_image)) {
                Storage::disk('public')->delete($product->product_image);
            }

            // ディレクトリを作成
            $directory = "shops/{$shop->id}/products/{$product->id}";
            Storage::disk('public')->makeDirectory($directory, 0755, true);

            // ファイルを保存
            $file = $request->file('product_image');
            $filename = $file->getClientOriginalName();
            $path = $file->storeAs($directory, $filename, 'public');
            $validated['product_image'] = $path;
        } else {
            // 画像がアップロードされていない場合は、既存の画像を維持
            unset($validated['product_image']);
        }

        // 商品の基本情報を更新
        $product->update([
            'product_status' => $validated['product_status'],
            'product_name' => $validated['product_name'],
            'product_summary' => $validated['product_summary'] ?? null,
            'product_description' => $validated['product_description'],
            'update_information' => $validated['update_information'] ?? null,
            'price_for_personal' => $validated['price_for_personal'] ?? null,
            'price_for_commercial' => $validated['price_for_commercial'] ?? null,
            'price_for_school' => $validated['price_for_school'] ?? null,
            'display_order' => $validated['display_order'] ?? null,
            'product_image' => $validated['product_image'] ?? $product->product_image,
        ]);

        // リレーションの同期
        if (isset($validated['subjects'])) {
            $product->subjects()->sync($validated['subjects']);
        }
        if (isset($validated['grades'])) {
            $product->grades()->sync($validated['grades']);
        }
        if (isset($validated['file_types'])) {
            $product->fileTypes()->sync($validated['file_types']);
        }

        return redirect()->route('member.sell.products.edit', $product)
            ->with('success', '商品「' . $validated['product_name'] . '」を編集しました。');
    }
    public function destroy(Product $product)
    {
        // 商品の所属ショップが本人のものであることを認証
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($product->shop_id !== $shop->id) {
            abort(403, 'この商品を削除する権限がありません。');
        }

        $product->loadCount(['orders', 'messages']);
        if ($product->orders_count > 0 || $product->messages_count > 0) {
            return redirect()->route('member.sell.products.index')
                ->with('error', '商品「' . $product->product_name . '」は削除できません。注文やメッセージがあるため、削除ボタンではなく編集ボタンを押して「状態」を「販売終了」に変更してください。');
        }
        if ($product->product_limited) {
            return redirect()->route('member.sell.products.index')
                ->with('error', '商品「' . $product->product_name . '」は削除できません。販売不可の商品は削除についてはサイト管理者へお問い合わせください。');
        }
        
        $product->delete();
        return redirect()->route('member.sell.products.index')
            ->with('success', '商品「' . $product->product_name . '」を削除しました。');
    }

    public function download(ProductFile $productFile)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($productFile->product->shop_id == $shop->id) {
            return $productFile->downloadResponse();
        } else {
            abort(403, 'この商品ファイルをダウンロードする権限がありません。');
        }
        
    }
}

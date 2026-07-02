<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Product $product)
    {
        // 商品情報とショップ情報を読み込む
        $product->load(['shop.member']);
        
        // 削除されていないメッセージを取得（公開設定も考慮）
        // public_sender と public_shop の両方が1の場合のみ表示
        $messages = $product->messages()
            ->notDeleted()
            ->published()
            ->with([
                'user.member',
                'replies.user.member',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // dd($messages);
        return view('member.messages.index', compact('product', 'messages'));
    }
    public function create(Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member?->shop;
        // 自分が出品中の商品にはメッセージを投稿できない
        if ($shop && $product->shop_id === $shop->id) {
            return redirect()->route('member.messages.index', $product)
                ->with('error', '自分が出品中の商品にはメッセージを投稿できません。');
        }
        $input = session('member.messages.input', [
            'public_sender' => 1,
        ]);
        return view('member.messages.create', compact('product', 'member', 'input'));
    }
    public function confirm(MessageRequest $request, Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member?->shop;
        if ($shop && $product->shop_id === $shop->id) {
            return redirect()->route('member.messages.index', $product)
                ->with('error', '自分が出品中の商品にはメッセージを投稿できません。');
        }

        $data = $request->validated();

        if ((int)$data['product_id'] !== $product->id) {
            return redirect()->route('member.messages.create', $product)
                ->with('error', '不正な商品が指定されました。');
        }

        session(['member.messages.input' => $data]);

        return view('member.messages.confirm', [
            'product' => $product,
            'member' => $member,
            'input' => $data,
        ]);
    }
    public function store(Request $request, Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member?->shop;
        // 自分が出品中の商品にはメッセージを投稿できない
        if ($shop && $product->shop_id === $shop->id) {
            return redirect()->route('member.messages.index', $product)
                ->with('error', '自分が出品中の商品にはメッセージを投稿できません。');
        }
        $input = session('member.messages.input');

        if (!$input || (int)($input['product_id'] ?? 0) !== $product->id) {
            return redirect()->route('member.messages.create', $product)
                ->with('error', '送信内容が確認できませんでした。もう一度入力してください。');
        }

        $message = Message::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'title' => $input['title'] ?? '',
            'public_sender' => (int)($input['public_sender'] ?? 0),
            'public_shop' => 1,
            'message' => $input['message'],
            'ip_address' => $request->ip(),
        ]);
        session([
            'member.messages.completed_input' => $input,
        ]);
        session()->forget('member.messages.input');
        return redirect()->route('member.messages.complete', $product);
    }


    public function complete(Product $product)
    {
        $input = session()->pull('member.messages.completed_input');

        if (!$input) {
            return redirect()->route('member.messages.index', $product);
        }

        return view('member.messages.complete', [
            'product' => $product,
            'input' => $input,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageReplyRequest;
use App\Models\Message;
use App\Models\MessageReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageReplyController extends Controller
{
    /**
     * メッセージ一覧（対象メッセージ位置へ遷移）
     */
    public function index(Message $message)
    {
        $product = $message->product;
        $targetUrl = route('member.messages.index', $product) . '#message-' . $message->id;

        return redirect()->to($targetUrl);
    }

    /**
     * 返信入力
     */
    public function create(Message $message)
    {
        $user = Auth::user();
        $senderType = $this->determineSenderType($message, $user);

        $message->load([
            'product.shop.member.user',
            'user.member',
            'replies.user.member',
        ]);

        $input = session("member.message-replies.input.{$message->id}", [
            'reply' => '',
        ]);
        
        // 自分の投稿として閲覧済みにする
        $message->markAsReadBy($user->id);

        return view('member.message-replies.create', [
            'message' => $message,
            'product' => $message->product,
            'input' => $input,
            'isSender' => $message->isSender($user),
            'isShopOwner' => $message->isShopOwner($user),
        ]);
    }

    /**
     * 確認画面
     */
    public function confirm(MessageReplyRequest $request, Message $message)
    {
        $user = Auth::user();
        $senderType = $this->determineSenderType($message, $user);
        $data = $request->validated();

        session(["member.message-replies.input.{$message->id}" => $data]);

        return view('member.message-replies.confirm', [
            'message' => $message,
            'product' => $message->product,
            'senderType' => $senderType,
            'input' => $data,
        ]);
    }

    /**
     * 登録
     */
    public function store(Request $request, Message $message)
    {
        $user = Auth::user();
        $senderType = $this->determineSenderType($message, $user);
        $input = session("member.message-replies.input.{$message->id}");

        if (!$input) {
            return redirect()
                ->route('member.message-replies.create', $message)
                ->with('error', '送信内容が確認できませんでした。もう一度入力してください。');
        }

        MessageReply::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'sender_type' => $senderType,
            'reply' => $input['reply'],
            'ip_address' => $request->ip(),
        ]);

        // 自分の投稿として閲覧済みにする
        $message->markAsReadBy($user->id);

        session(["member.message-replies.completed.{$message->id}" => $input]);
        session()->forget("member.message-replies.input.{$message->id}");

        return redirect()->route('member.message-replies.complete', $message);
    }

    /**
     * 完了画面
     */
    public function complete(Message $message)
    {
        $input = session()->pull("member.message-replies.completed.{$message->id}");

        if (!$input) {
            return redirect()->route('member.message-replies.create', $message);
        }

        return view('member.message-replies.complete', [
            'message' => $message,
            'product' => $message->product,
            'input' => $input,
        ]);
    }

    /**
     * 返信削除（投稿者のみ）
     */
    public function destroy(Message $message, MessageReply $reply)
    {
        $user = Auth::user();

        if ($reply->message_id !== $message->id) {
            abort(404);
        }

        if ($reply->user_id !== $user->id) {
            abort(403);
        }

        $reply->update(['deleted_by_sender_at' => now()]);

        return back()->with('status', '返信を削除しました。');
    }

    /**
     * 公開設定更新
     */
    public function updatePublicSetting(Request $request, Message $message)
    {
        $user = Auth::user();
        $request->validate([
            'public' => 'required|integer|in:0,1',
        ]);

        // 権限チェック：ショップまたは送信者のみ更新可能
        if (!$message->canEditPublicSettingBy($user)) {
            abort(403, '公開設定を変更する権限がありません。');
        }

        // 送信者の公開設定を更新
        if ($message->isSender($user)) {
            $message->update(['public_sender' => (int)$request->public]);
        }

        // ショップの公開設定を更新
        if ($message->isShopOwner($user)) {
            $message->update(['public_shop' => (int)$request->public]);
        }

        return back()->with('status', '公開設定を更新しました。');
    }

    /**
     * 権限確認と送信者種別決定
     */
    private function determineSenderType(Message $message, $user): int
    {
        if ($message->isDeleted()) {
            abort(404);
        }

        // 管理者
        if ($user->role === 1) {
            return 3;
        }

        $shop = $user->member?->shop;

        // 販売者（対象商品のショップ）
        if ($shop && $message->product?->shop_id === $shop->id) {
            return 1;
        }

        // メッセージ投稿者
        if ($message->user_id === $user->id) {
            return 2;
        }

        abort(403, 'このメッセージに返信する権限がありません。');
    }
}


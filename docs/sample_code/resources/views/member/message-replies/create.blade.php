@extends('layouts.member')

@section('title', '質問・メッセージ返信')

@section('content')
<h1>質問・メッセージ返信</h1>
<x-alert />
<div class="center">商品：<a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a></div>

{{-- 送信者の公開設定 --}}
<div>
  <img src="{{ $message->user->user_icon_url }}" class="icon" alt="会員アイコン">
  <a href="{{ route('member.members.show', $message->user->member) }}">{{ $message->user->user_name ?? 'ユーザー' }}</a>：@if($isSender)
    {{-- 送信者の場合：編集可能 --}}
    <form method="post" action="{{ route('member.message-replies.update-public-setting', $message) }}" class="d-inline" style="display: inline;" id="sender-public-form" data-original="{{ $message->public_sender ? 1 : 0 }}">
      @csrf
      @method('patch')
      <label><input type="radio" name="public" value="1" {{ $message->public_sender ? 'checked' : '' }} onchange="alertUser('「公開可」に変更してもよろしいですか？') && this.form.submit() || (this.checked=false, this.form.querySelector('input[value='+this.form.dataset.original+']').checked=true)">公開可</label>
      <label><input type="radio" name="public" value="0" {{ !$message->public_sender ? 'checked' : '' }} onchange="alertUser('「非公開」に変更してもよろしいですか？') && this.form.submit() || (this.checked=false, this.form.querySelector('input[value='+this.form.dataset.original+']').checked=true)">非公開</label>
    </form>
  @else
    {{-- 送信者でない場合：表示のみ --}}
    <span class="bold">{{ $message->public_sender_text }}</span>
  @endif
</div>

{{-- ショップの公開設定 --}}
@if($product->shop)
  <div>
    <img src="{{ $product->shop->shop_icon_url }}" class="icon" alt="ショップアイコン">
    <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop->shop_name }}</a>：@if($isShopOwner)
      {{-- ショップの場合：編集可能 --}}
      <form method="post" action="{{ route('member.message-replies.update-public-setting', $message) }}" class="d-inline" style="display: inline;" id="shop-public-form" data-original="{{ $message->public_shop ? 1 : 0 }}">
        @csrf
        @method('patch')
        <label><input type="radio" name="public" value="1" {{ $message->public_shop ? 'checked' : '' }} onchange="alertUser('「公開可」に変更してもよろしいですか？') && this.form.submit() || (this.checked=false, this.form.querySelector('input[value='+this.form.dataset.original+']').checked=true)">公開可</label>
        <label><input type="radio" name="public" value="0" {{ !$message->public_shop ? 'checked' : '' }} onchange="alertUser('「非公開」に変更してもよろしいですか？') && this.form.submit() || (this.checked=false, this.form.querySelector('input[value='+this.form.dataset.original+']').checked=true)">非公開</label>
      </form>
    @else
      {{-- ショップでない場合：表示のみ --}}
      <span class="bold">{{ $message->public_shop_text }}</span>
    @endif
  </div>
@endif

<div class="text-sm gray">自分と相手方の両方が公開可を選択した場合に、質問・メッセージ・返信内容が公開されます。</div>

<div class="card card-shadow" id="message-{{ $message->id }}">
  <div>
    @if($message->isPublished())
      <span class="badge badge-green m-1">公開中</span>
    @else
      <span class="badge badge-gray m-1">非公開</span>
    @endif
  </div>
  <img src="{{ $message->user->user_icon_url }}" alt="会員アイコン" class="icon">
  @if($message->user->role == 1)
    <span class="bg-light-gray bold p-1">管理人</span>
  @else
    <a href="{{ route('member.members.show', $message->user->member) }}">{{ $message->user->user_name ?? 'ユーザー' }}</a>
  @endif
  <div class="p-2">
    {!! nl2br(e($message->message)) !!}
  </div>
  <div class="flex justify-between">
    <div class="light-gray text-xs">
      投稿日：{{ $message->created_at->format('Y/m/d') }}
    </div>
    <div>
      @php
        $messageReportText = implode("\n", [
            '違反投稿の報告です。',
            '',
            '対象種別: メッセージ',
            '対象番号: ' . $message->message_number,
            '商品名: ' . $product->product_name,
            '対象URL: ' . route('member.message-replies.create', $message),
            '',
            '問題の内容:',
        ]);
      @endphp
      <a href="{{ route('contacts.create', ['inquiry_type' => '違反投稿の報告', 'message' => $messageReportText]) }}" class="no-decoration light-gray text-xs">不適切投稿を報告</a>
    </div>
  </div>

  @foreach($message->replies as $reply)
    <div class="card ml-3">
      @if($reply->isDeleted())
        <div class="text-sm light-gray p-1">
          投稿が削除されました。
        </div>
        <div class="flex justify-between">
          <div class="light-gray text-xs">
            投稿日：{{ $reply->created_at->format('Y/m/d') }}　削除日：{{ $reply->deleted_at->format('Y/m/d') }}
          </div>
        </div>
      @else
        @if($reply->sender_type == 1)
          <img src="{{ $product->shop?->shop_icon_url }}" class="icon" alt="ショップアイコン">
          <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop?->shop_name }}</a>
        @elseif($reply->sender_type == 2)
          <img src="{{ $reply->user?->user_icon_url }}" class="icon" alt="ユーザーアイコン">
          <a href="{{ route('member.members.show', $reply->user?->member) }}">{{ $reply->user?->user_name }}</a>
        @elseif($reply->sender_type == 3)
          <span class="bg-light-gray bold p-1">管理人</span>
        @endif
        <div class="p-1">
          {!! nl2br(e($reply->reply)) !!}
        </div>
        <div class="flex justify-between">
          <div class="light-gray text-xs">
            投稿日：{{ $reply->created_at->format('Y/m/d') }}
          </div>
          <div>
            @php
              $messageReplyReportText = implode("\n", [
                  '違反投稿の報告です。',
                  '',
                  '対象種別: メッセージ返信',
                  '対象番号: ' . $reply->message_reply_number,
                  '親メッセージ番号: ' . $message->message_number,
                  '商品名: ' . $product->product_name,
                  '対象URL: ' . route('member.message-replies.create', $message),
                  '',
                  '問題の内容:',
              ]);
            @endphp
            <a href="{{ route('contacts.create', ['inquiry_type' => '違反投稿の報告', 'message' => $messageReplyReportText]) }}" class="no-decoration light-gray text-xs">不適切投稿を報告</a>
          </div>
        </div>
      @endif
    </div>
  @endforeach
  
  <form method="post" action="{{ route('member.message-replies.confirm', $message) }}" class="mt-4">
    @csrf
    <div class="width-sm">
      <label for="reply">返信内容</label>
      <textarea id="reply" name="reply" rows="6" placeholder="返信内容を入力してください">{{ old('reply', $input['reply'] ?? '') }}</textarea>
    </div>
    <div class="center mt-3">
      <button class="btn btn-primary" type="submit">
        返信内容を確認
      </button>
    </div>
    <div class="center mt-3">
      <button class="btn btn-white" type="button" onclick="location.href='{{ route('member.message-box.index') }}'">
        メッセージ一覧へ
      </button>
    </div>
  </form>
</div>

@endsection
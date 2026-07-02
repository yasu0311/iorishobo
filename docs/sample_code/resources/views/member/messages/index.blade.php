@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', '質問・メッセージ一覧')

@section('content')
<h1>質問・メッセージ</h1>
<div class="center">商品：<a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a></div>

@forelse($messages as $message)
<div class="card card-shadow" id="message-{{ $message->id }}">
  <img src="{{ $message->user?->user_icon_url }}" alt="会員アイコン" class="icon">
  @if($message->user->role == 1)
    <span class="bg-light-gray bold p-1">管理人</span>
  @else
    <a href="{{ route('member.members.show', $message->user?->member) }}">{{ $message->user?->user_name ?? 'ユーザー' }}</a>
  @endif
  @if(!empty($message->title))
    <div class="font-bold mt-2">{{ $message->title }}</div>
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
            '対象URL: ' . route('member.messages.index', $product) . '#message-' . $message->id,
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
        {{-- 削除済み返信の表示 --}}
        <div class="text-sm light-gray p-1">
          投稿が削除されました。
        </div>
        <div class="flex justify-between">
          <div class="light-gray text-xs">
            投稿日：{{ $reply->created_at->format('Y/m/d') }}　削除日：{{ $reply->deleted_at->format('Y/m/d') }}
          </div>
        </div>
      @else        
        {{-- 通常の返信表示 --}}
        @if($reply->sender_type == 1)
          {{-- 販売者 --}}
          <img src="{{ $message->product?->shop?->shop_icon_url }}" alt="会員アイコン" class="icon">
          
          <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop?->shop_name }}</a>
        @elseif($reply->sender_type == 2)
          {{-- メッセージ投稿者 --}}
          <img src="{{ $reply->user?->user_icon_url }}" alt="会員アイコン" class="icon">
          
          <a href="{{ route('member.members.show', $reply->user?->member) }}">{{ $reply->user?->user_name }}</a>
        @elseif($reply->sender_type == 3)
          {{-- サイト管理者 --}}
          <img src="{{ $reply->user?->user_icon_url }}" alt="会員アイコン" class="icon">
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
                  '対象URL: ' . route('member.messages.index', $product) . '#message-' . $message->id,
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
</div>
@empty
<div class="card card-shadow">
  <div class="p-2">
    まだメッセージがありません。
  </div>
</div>
@endforelse

<x-pagination :paginator="$messages" />

<div class="center">
  <div class="center">
    <button class="btn btn-white" onclick="location.href='{{ route('member.buy.products.show', $product) }}'">
      商品ページへ
    </button>
    <button class="btn btn-primary" onclick="location.href='{{ route('member.messages.create', $product) }}'">
      質問・メッセージを新規投稿
    </button>
    @guest
    <div class="center gray text-sm">
      質問・メッセージを新規投稿をするにはログインが必要です。
    </div>
    @endguest
  </div>
  @auth
    <div class="center mt-2">
      質問・メッセージに対する返信は<a href="{{ route('member.message-box.index') }}">メッセージボックス</a>からお願いします。
    </div>
  @endauth
</div>
@endsection

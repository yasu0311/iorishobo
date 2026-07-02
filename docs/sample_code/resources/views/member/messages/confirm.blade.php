@extends('layouts.member')

@section('title', '質問・メッセージ内容確認')

@section('content')

<h1>質問・メッセージ内容確認</h1>
      <div class="width-md table-vertical-responsive">
        <table>
          <tbody>
            <tr>
              <th>商品</th>
              <td>
                <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
              </td>
            </tr>
            <tr>
              <th>販売者</th>
              <td>
                @if($product->shop)
                  <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop->shop_name }}</a>
                @else
                  -
                @endif
              </td>
            </tr>
            <tr>
              <th>名前</th>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="{{ $member->member_icon_url }}" class="icon" alt="アイコン">
                  <span>{{ $member->nickname ?? optional($member->user)->name ?? 'ゲスト' }}</span>
                </div>
              </td>
            </tr>
            <tr>
              <th>公開設定</th>
              <td>{{ (int)($input['public_sender'] ?? 1) === 1 ? '公開可' : '公開不可' }}</td>
            </tr>
            <tr>
              <th>タイトル</th>
              <td>{{ $input['title'] ?? '' }}</td>
            </tr>
            <tr>
              <th>内容</th>
              <td>{!! nl2br(e($input['message'] ?? '')) !!}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="center">
        <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.messages.create', $product) }}'">修正する</button>
        <form method="post" action="{{ route('member.messages.store', $product) }}" class="d-inline" style="display: inline;">
          @csrf
          <button class="btn btn-primary" type="submit">送信する</button>
        </form>
      </div>

@endsection
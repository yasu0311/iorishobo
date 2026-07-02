@extends('layouts.member')

@section('title', '質問・メッセージ返信内容確認')

@section('content')
<h1>質問・メッセージ返信内容確認</h1>
<div class="width-md table-vertical-responsive">
  <table>
    <tbody>
      <tr>
        <th>商品</th>
        <td>{{ $product->product_name }}</td>
      </tr>
      <tr>
        <th>販売者</th>
        <td>{{ $product->shop->shop_name ?? '-' }}</td>
      </tr>
      <tr>
        <th>送信者</th>
        <td>{{ auth()->user()->user_name}}</td>
      </tr>
      <tr>
        <th>返信内容</th>
        <td>{!! nl2br(e($input['reply'])) !!}</td>
      </tr>
    </tbody>
  </table>
</div>
<div class="center">
  <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.message-replies.create', $message) }}'">
    もどる
  </button>
  <form method="post" action="{{ route('member.message-replies.store', $message) }}" class="d-inline" style="display:inline;">
    @csrf
    <button class="btn btn-primary" type="submit">送信する</button>
  </form>
</div>
@endsection
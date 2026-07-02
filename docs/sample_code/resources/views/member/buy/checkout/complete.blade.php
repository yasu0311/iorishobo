@extends('layouts.member')

@section('title', '注文を受け付けました')

@section('content')
<h1>注文を受け付けました</h1>
<x-alert/>
<div class="center">
  @if ((int) $order->amount_paid <= 0)
    <p>注文が確定しました。注文一覧からダウンロード可能です。</p>
  @else
    <p>決済を確認しています。しばらくすると注文一覧に表示され、確定後にダウンロード可能になります。</p>
  @endif
  <p class="mt-2">ダウンロードした教材のご利用には、<a href="{{ route('static.terms') }}">利用規約</a>と<a href="{{ route('static.copyright-purchaser') }}">著作権上の注意点（購入者）</a>をお守りください。</p>
  <p class="mt-2">注文番号：<strong>{{ $order->order_number }}</strong></p>
</div>
<div class="center mt-4">
  <a href="{{ route('member.buy.orders.index') }}" class="btn btn-white">注文一覧</a>
  <a href="{{ route('member.buy.products.show', $order->product) }}" class="btn btn-white">商品ページ</a>
</div>
@endsection
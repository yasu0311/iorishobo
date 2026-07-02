@extends('layouts.front')

@section('title', 'お問い合わせ送信完了 - '.config('shop.name'))

@section('content')
    <h1>お問い合わせを送信しました</h1>

    <p>お問い合わせありがとうございます。内容を確認のうえ、担当者よりご連絡いたします。</p>

    <p><a href="{{ route('home') }}" class="btn btn--secondary">トップページへ戻る</a></p>
@endsection

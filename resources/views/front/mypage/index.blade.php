@extends('layouts.front')

@section('title', 'マイページ - '.config('shop.name'))

@section('content')
    <div class="mypage-header">
        <h1>マイページ</h1>
        <p>こんにちは、{{ Auth::user()->name }} さん</p>
    </div>

    <ul class="mypage-nav">
        <li><a href="{{ route('mypage.profile.edit') }}">プロフィール編集</a></li>
        <li><a href="{{ route('mypage.orders.index') }}">注文履歴</a></li>
    </ul>

    <form method="post" action="{{ route('logout') }}" style="margin-top: 1.5rem;">
        @csrf
        <button type="submit" class="btn btn--secondary">ログアウト</button>
    </form>
@endsection

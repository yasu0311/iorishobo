@extends('layouts.front')

@section('title', 'マイページ - '.config('shop.name'))

@section('content')
    <h1>マイページ</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <p>こんにちは、{{ Auth::user()->name }} さん</p>

    <ul>
        <li><a href="{{ route('mypage.profile.edit') }}">プロフィール編集</a></li>
        <li><a href="{{ route('mypage.orders.index') }}">注文履歴</a></li>
    </ul>

    <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit">ログアウト</button>
    </form>
@endsection

@extends('layouts.front')

@section('title', 'メール認証 - '.config('shop.name'))

@section('content')
    <h1>メール認証</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <p>ご登録のメールアドレスに確認リンクを送信しました。メール内のリンクをクリックして認証を完了してください。</p>
    <p>認証が完了するまでログインできません。</p>

    <h2>確認メールを再送する</h2>
    <form method="post" action="{{ route('verification.send') }}">
        @csrf
        <p>
            <label>メールアドレス<br>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
        </p>
        <button type="submit">再送する</button>
    </form>

    <p><a href="{{ route('login') }}">ログインへ</a></p>
@endsection

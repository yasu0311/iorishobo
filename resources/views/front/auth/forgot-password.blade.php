@extends('layouts.auth')

@section('title', 'パスワードリセット')

@section('content')
    <h1>パスワードリセット</h1>

    <p>登録済みのメールアドレスを入力してください。リセット用のリンクをお送りします。</p>

    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <label>メールアドレス
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>
        <x-input-error :messages="$errors->get('email')" />
        <button type="submit" class="btn">リセット用メールを送信</button>
    </form>

    <div class="auth-footer">
        <p><a href="{{ route('login') }}">ログインへ戻る</a></p>
    </div>
@endsection

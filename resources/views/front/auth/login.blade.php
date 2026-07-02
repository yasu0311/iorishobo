@extends('layouts.auth')

@section('title', 'ログイン')

@section('content')
    <h1>ログイン</h1>

    <form method="post" action="{{ route('login') }}">
        @csrf
        <label>メールアドレス
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        <x-input-error :messages="$errors->get('email')" />
        <label>パスワード
            <input type="password" name="password" required>
        </label>
        <x-input-error :messages="$errors->get('password')" />
        <label>
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            ログイン状態を保持する
        </label>
        <button type="submit" class="btn">ログイン</button>
    </form>

    <div class="auth-footer">
        <p><a href="{{ route('password.request') }}">パスワードをお忘れですか？</a></p>
        <p><a href="{{ route('register') }}">会員登録</a></p>
    </div>
@endsection

@extends('layouts.auth')

@section('title', '会員登録')

@section('content')
    <h1>会員登録</h1>

    <form method="post" action="{{ route('register') }}">
        @csrf
        <label>氏名
            <input type="text" name="name" value="{{ old('name') }}" required>
        </label>
        <x-input-error :messages="$errors->get('name')" />
        <label>メールアドレス
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>
        <x-input-error :messages="$errors->get('email')" />
        <label>パスワード
            <input type="password" name="password" required>
        </label>
        <x-input-error :messages="$errors->get('password')" />
        <label>パスワード（確認）
            <input type="password" name="password_confirmation" required>
        </label>
        <button type="submit" class="btn">登録する</button>
    </form>

    <div class="auth-footer">
        <p><a href="{{ route('login') }}">ログインはこちら</a></p>
    </div>
@endsection

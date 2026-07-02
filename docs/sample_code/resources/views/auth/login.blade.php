@extends('layouts.auth')

@section('title', 'ログイン')

@section('styles')
{{-- auth.blade.phpに既にstyle/auth.cssが読み込まれているため、追加のスタイルは不要であれば空でOKです --}}
@endsection

@section('content')
    <div class="center">
        {{-- 画像パスをLaravelのassetヘルパーで修正 --}}
        <a href="{{ url('/') }}"><img src="{{ asset('images/common/logo.svg') }}" alt="教材マーケット"></a>
    </div>
    <h1>ログイン</h1>

    <x-auth-session-status :status="session('status')" />

    {{-- ログインフォーム --}}
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">メールアドレス:</label><br>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus><br>
        <x-input-error :messages="$errors->get('email')" />
        
        <label for="password">パスワード:</label><br>
        {{-- inputタグにnameを追加 --}}
        <input type="password" id="password" name="password" required autocomplete="current-password"><br>
        <x-input-error :messages="$errors->get('password')" />

        <div class="small">
            <label>
                {{-- checkboxにname="remember"を追加 --}}
                <input type="checkbox" name="remember" id="remember_me">
                ログイン状態を保存する
            </label>
        </div>
        
        <button type="submit" class="btn">ログイン</button>
    </form>

    {{-- パスワードリセットリンク --}}
    <div class="center small">
        {{-- hrefをroute('password.request')に修正 --}}
        <a href="{{ route('password.request') }}">パスワードを忘れた方はこちら</a>
    </div>

    {{-- 新規登録リンク --}}
    <div class="center small">
        <a href="{{ route('register') }}">新規登録はこちら</a>
    </div>
@endsection
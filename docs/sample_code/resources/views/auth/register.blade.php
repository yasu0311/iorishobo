@extends('layouts.auth')

@section('title', '新規登録')

@section('content')
    <div class="center">
        <a href="{{ url('/') }}"><img src="{{ asset('images/common/logo.svg') }}" alt="教材マーケット"></a>
    </div>

    <h1>新規登録</h1>

	{{-- ステータス表示 --}}
	<x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('register') }}" id="register-form">
        @csrf

        <label for="email">メールアドレス:</label><br>
        {{-- Laravelのバリデーションエラー表示を考慮し、inputタグを修正 --}}
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus><br>
        @error('email')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <label for="password">パスワード:</label><br>
        <input type="password" id="password" name="password" required><br>
        @error('password')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        

        <label for="password_confirmation">パスワード(再入力確認):</label><br>
        <input type="password" id="password_confirmation" name="password_confirmation" required><br>
        

        <button type="submit" class="btn" id="register-submit">新規登録</button>
    </form>

    <div class="center small">
        すでにアカウントをお持ちの方は<a href='{{ route('login') }}'>こちら</a>
    </div>

    <script>
        document.getElementById('register-form')?.addEventListener('submit', function () {
            const submitButton = document.getElementById('register-submit');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = '送信中...';
            }
        });
    </script>
@endsection
@extends('layouts.auth')

@section('title', 'パスワードを忘れた方へ')

@section('content')
    <div class="center">
        <a href="{{ url('/') }}"><img src="{{ asset('images/common/logo.svg') }}" alt="教材マーケット"></a>
    </div>

    <h1>パスワード再設定</h1>

    <div class="small" style="margin-bottom: 20px;">
        パスワード再設定用のリンクを記載したメールを送信します。<br>
        ご登録済みのメールアドレスを入力してください。
    </div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="alert alert-success" style="margin-bottom: 20px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="email">メールアドレス:</label><br>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus><br>
        @error('email')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn">送信</button>
    </form>

    <div class="center small" style="margin-top: 20px;">
        <a href="{{ route('login') }}">ログイン画面に戻る</a>
    </div>
@endsection
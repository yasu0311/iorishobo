@extends('layouts.auth')

@section('title', 'パスワードの再設定')

@section('content')
    <div class="center">
        <a href="{{ url('/') }}"><img src="{{ asset('images/common/logo.svg') }}" alt="教材マーケット"></a>
    </div>

    <h1>パスワードの再設定</h1>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        {{-- パスワードリセットに必要なトークンとメールアドレスを hidden で送信 --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label for="email">メールアドレス:</label><br>
        {{-- メールアドレスはクエリパラメータから取得 --}}
        <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}" required autofocus><br>
        @error('email')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        
        <label for="password">新しいパスワード:</label><br>
        <input type="password" id="password" name="password" required autocomplete="new-password"><br>
        @error('password')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <label for="password_confirmation">新しいパスワード確認:</label><br>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"><br>
        @error('password_confirmation')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn">再設定</button>
    </form>
@endsection
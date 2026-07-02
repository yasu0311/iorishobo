@extends('layouts.auth')

@section('title', 'パスワードの確認')

@section('content')
    <div class="center">
        <a href="{{ url('/') }}"><img src="{{ asset('images/common/logo.svg') }}" alt="教材マーケット"></a>
    </div>

    <h1>パスワードの確認</h1>

    <div class="small" style="margin-bottom: 20px;">
        続行するには、現在のパスワードを確認してください。
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <label for="password">パスワード:</label><br>
        <input type="password" id="password" name="password" required autocomplete="current-password" autofocus><br>
        @error('password')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div style="margin-top: 20px;">
            <button type="submit" class="btn">確認</button>
        </div>

        {{-- パスワードを忘れた場合のリンク（オプション） --}}
        @if (Route::has('password.request'))
            <div class="center small" style="margin-top: 20px;">
                <a href="{{ route('password.request') }}">
                    パスワードを忘れた場合
                </a>
            </div>
        @endif
    </form>
@endsection
@extends('layouts.auth')

@section('title', '新しいパスワード')

@section('content')
    <h1>新しいパスワード</h1>

    <form method="post" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>メールアドレス
            <input type="email" name="email" value="{{ old('email', $email) }}" required>
        </label>
        <x-input-error :messages="$errors->get('email')" />
        <label>新しいパスワード
            <input type="password" name="password" required>
        </label>
        <x-input-error :messages="$errors->get('password')" />
        <label>新しいパスワード（確認）
            <input type="password" name="password_confirmation" required>
        </label>
        <button type="submit" class="btn">パスワードを変更</button>
    </form>
@endsection

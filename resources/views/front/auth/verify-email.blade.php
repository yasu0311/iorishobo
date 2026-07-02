@extends('layouts.auth')

@section('title', 'メール認証')

@section('content')
    <h1>メール認証</h1>

    <p>ご登録のメールアドレスに確認リンクを送信しました。メール内のリンクをクリックして認証を完了してください。</p>
    <p>認証が完了するまでログインできません。</p>

    <h2 style="font-size: 1rem; margin: 1.5rem 0 0.75rem; color: var(--color-accent);">確認メールを再送する</h2>
    <form method="post" action="{{ route('verification.send') }}">
        @csrf
        <label>メールアドレス
            <input type="email" name="email" value="{{ old('email') }}" required>
        </label>
        <x-input-error :messages="$errors->get('email')" />
        <button type="submit" class="btn">再送する</button>
    </form>

    <div class="auth-footer">
        <p><a href="{{ route('login') }}">ログインへ</a></p>
    </div>
@endsection

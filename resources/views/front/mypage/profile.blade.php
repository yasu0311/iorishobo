@extends('layouts.front')

@section('title', 'プロフィール編集 - '.config('shop.name'))

@section('content')
    <a href="{{ route('mypage.index') }}" class="back-link">← マイページへ戻る</a>

    <h1>プロフィール編集</h1>

    @if ($user->pending_email)
        <div class="panel" style="margin-bottom: 1.5rem;">
            <p style="margin: 0 0 0.75rem;">
                <strong>{{ $user->pending_email }}</strong> への変更を確認待ちです。
                確認が完了するまで、ログイン用メールは <strong>{{ $user->email }}</strong> のままです。
            </p>
            <form method="post" action="{{ route('mypage.profile.pending-email.resend') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn--secondary">確認メールを再送</button>
            </form>
            <form method="post" action="{{ route('mypage.profile.pending-email.cancel') }}" style="display: inline; margin-left: 0.5rem;">
                @csrf
                <button type="submit" class="btn btn--secondary">変更を取り消す</button>
            </form>
        </div>
    @endif

    <form method="post" action="{{ route('mypage.profile.update') }}" class="panel">
        @csrf
        @method('PUT')
        <div class="form-field">
            <label>氏名</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="form-field">
            <label>フリガナ</label>
            <input type="text" name="name_kana" value="{{ old('name_kana', $user->customer?->name_kana) }}">
        </div>
        <div class="form-field">
            <label>メールアドレス</label>
            <input type="email" name="email" value="{{ old('email', $user->pending_email ?? $user->email) }}" required>
            <x-input-error :messages="$errors->get('email')" />
            <p class="form-hint" style="margin-top: 0.35rem; font-size: 0.875rem; color: var(--color-muted, #666);">
                変更すると新しいアドレスに確認メールが届きます。認証完了までログイン用アドレスは変わりません。
            </p>
        </div>
        <div class="form-field">
            <label>電話番号</label>
            <input type="text" name="phone" value="{{ old('phone', $user->customer?->phone) }}">
        </div>
        <div class="form-field">
            <label>携帯番号</label>
            <input type="text" name="mobile" value="{{ old('mobile', $user->customer?->mobile) }}">
        </div>
        <div class="form-field">
            <label>郵便番号</label>
            <input type="text" name="postal_code" value="{{ old('postal_code', $user->customer?->postal_code) }}" maxlength="7">
        </div>
        <div class="form-field">
            <label>都道府県</label>
            <input type="text" name="prefecture" value="{{ old('prefecture', $user->customer?->prefecture) }}">
        </div>
        <div class="form-field">
            <label>住所</label>
            <input type="text" name="address_line1" value="{{ old('address_line1', $user->customer?->address_line1) }}">
        </div>
        <div class="form-field">
            <label>建物名・部屋番号</label>
            <input type="text" name="address_line2" value="{{ old('address_line2', $user->customer?->address_line2) }}">
        </div>
        <button type="submit" class="btn btn--primary">保存</button>
    </form>

    <h2 style="margin-top: 2.5rem;">パスワード変更</h2>
    <form method="post" action="{{ route('mypage.password.update') }}" class="panel">
        @csrf
        @method('PUT')
        <div class="form-field">
            <label>現在のパスワード</label>
            <input type="password" name="current_password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('current_password')" />
        </div>
        <div class="form-field">
            <label>新しいパスワード</label>
            <input type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <div class="form-field">
            <label>新しいパスワード（確認）</label>
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn--primary">パスワードを変更</button>
    </form>
@endsection

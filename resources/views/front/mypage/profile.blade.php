@extends('layouts.front')

@section('title', 'プロフィール編集 - '.config('shop.name'))

@section('content')
    <a href="{{ route('mypage.index') }}" class="back-link">← マイページへ戻る</a>

    <h1>プロフィール編集</h1>

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
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            <x-input-error :messages="$errors->get('email')" />
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
@endsection

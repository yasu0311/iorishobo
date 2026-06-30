@extends('layouts.front')

@section('title', 'プロフィール編集 - '.config('shop.name'))

@section('content')
    <h1>プロフィール編集</h1>

    <p><a href="{{ route('mypage.index') }}">マイページへ戻る</a></p>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="post" action="{{ route('mypage.profile.update') }}">
        @csrf
        @method('PUT')
        <p>
            <label>氏名<br>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </label>
        </p>
        <p>
            <label>フリガナ<br>
                <input type="text" name="name_kana" value="{{ old('name_kana', $user->customer?->name_kana) }}">
            </label>
        </p>
        <p>
            <label>メールアドレス<br>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </label>
        </p>
        <p>
            <label>電話番号<br>
                <input type="text" name="phone" value="{{ old('phone', $user->customer?->phone) }}">
            </label>
        </p>
        <p>
            <label>携帯番号<br>
                <input type="text" name="mobile" value="{{ old('mobile', $user->customer?->mobile) }}">
            </label>
        </p>
        <p>
            <label>郵便番号<br>
                <input type="text" name="postal_code" value="{{ old('postal_code', $user->customer?->postal_code) }}" maxlength="7">
            </label>
        </p>
        <p>
            <label>都道府県<br>
                <input type="text" name="prefecture" value="{{ old('prefecture', $user->customer?->prefecture) }}">
            </label>
        </p>
        <p>
            <label>住所<br>
                <input type="text" name="address_line1" value="{{ old('address_line1', $user->customer?->address_line1) }}">
            </label>
        </p>
        <p>
            <label>建物名・部屋番号<br>
                <input type="text" name="address_line2" value="{{ old('address_line2', $user->customer?->address_line2) }}">
            </label>
        </p>
        <button type="submit">保存</button>
    </form>
@endsection

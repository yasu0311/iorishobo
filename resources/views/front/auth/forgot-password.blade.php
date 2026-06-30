@extends('layouts.front')

@section('title', 'パスワードリセット - '.config('shop.name'))

@section('content')
    <h1>パスワードリセット</h1>

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

    <form method="post" action="{{ route('password.email') }}">
        @csrf
        <p>
            <label>メールアドレス<br>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
        </p>
        <button type="submit">リセット用メールを送信</button>
    </form>

    <p><a href="{{ route('login') }}">ログインへ戻る</a></p>
@endsection

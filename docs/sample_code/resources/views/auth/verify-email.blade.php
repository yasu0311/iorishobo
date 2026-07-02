@extends('layouts.auth')

@section('title', 'メールアドレスの確認')

@section('content')
    <div class="center">
        <a href="{{ url('/') }}"><img src="{{ asset('images/common/logo.svg') }}" alt="教材マーケット"></a>
    </div>

    <h1>メールアドレスの確認</h1>

    <div class="small gray">
        ご登録ありがとうございます！<br>
        ご登録メールアドレス宛てに送信されたリンクをクリックして認証を完了させてください。<br>
        メールが届かない場合や有効期限が切れた場合は、再送信のボタンをクリックして再送信してください。
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">
        新しい確認リンクが登録メールアドレス宛てに送信されました。<br>
        受信メールに記載されたリンクをクリックしてください。
        </div>
    @endif  

    <div>
        {{-- 確認メール再送信フォーム --}}
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn">再送信</button>
        </form>

        <form id="logout-form" action="{{ route('logout') }}" method="POST">
            @csrf
        </form>
        <div class= "small center">
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                ログアウト
            </a>
        </div>
    </div>
@endsection
@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', 'お問い合わせ送信完了')

@section('content')
<h1>お問い合わせ送信完了</h1>
      <div class="center">
        お問い合わせありがとうございました。<br>
        内容を確認の上、担当者よりご連絡いたします。
      </div>
      <div class="center">
        <button class="btn btn-white" onclick="location.href='{{ route('home') }}'">
          トップページへ戻る
        </button>
      </div>
@endsection
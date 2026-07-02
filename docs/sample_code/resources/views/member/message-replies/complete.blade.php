@extends('layouts.member')

@section('title', '質問・メッセージ返信完了')

@section('content')
<h1>質問・メッセージ返信完了</h1>
<div class="center">
  質問・メッセージの返信が完了しました。
</div>
<div class="center">
  <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.message-box.index') }}'">メッセージ一覧</button>
</div>
@endsection
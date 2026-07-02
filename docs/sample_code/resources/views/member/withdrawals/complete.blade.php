@extends('layouts.member')

@section('title', '出金依頼完了')

@section('content')

<h1>出金依頼完了</h1>
      <div class="center">
        出金のお申込みありがとうございました。<br>
        出金手続きをいたしますので、今しばらくお待ちください。
      </div>
      <div class="center">
        <button class="btn btn-white" onclick="location.href='{{ route('member.passbook.index') }}'">
          通帳ページへ戻る
        </button>
      </div>

@endsection
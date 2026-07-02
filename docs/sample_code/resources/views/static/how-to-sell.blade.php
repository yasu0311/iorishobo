@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', '教材販売の方法')

@section('content')

<div class="static-guide static-guide--sell">
  <h1>教材販売の方法</h1>

  <p class="static-guide__lead">あおば教材マーケットでは、@guest<a href="{{ route('register') }}">会員登録（無料）</a>@else「会員登録（無料）」@endguest後、わずか4ステップで教材の販売を始めていただけます。ご自身で作成した教材を、全国の先生方にお届けしませんか。</p>

  <section class="static-guide__section">
    <h2>Step 1　新規登録</h2>
    <ul class="static-guide__list">
      <li>販売を開始するには、まず@guest<a href="{{ route('register') }}">新規登録</a>@else「新規登録」@endguestにて会員登録を行ってください（無料）</li>
    </ul>
  </section>

  <section class="static-guide__section">
    <h2>Step 2　ショップ設定</h2>
    <ul class="static-guide__list">
      <li>@auth<a href="{{ route('member.sell.shop.show') }}">ショップ設定</a>@else「ショップ設定」@endauthより、ショップ名や販売者情報などの詳細を入力し、保存してください</li>
    </ul>
  </section>

  <section class="static-guide__section">
    <h2>Step 3　商品登録</h2>
    <ul class="static-guide__list">
      <li>@auth<a href="{{ route('member.sell.products.index') }}">商品登録・変更</a>@else「商品登録・変更」@endauthの「商品新規登録」より、販売したい教材の情報を登録してください</li>
      <li>同じ画面下部の「商品ファイル新規追加」から、教材ファイル（PDFなど）をアップロードしてください</li>
      <li>商品の状態を「販売中」に設定すると、教材検索の結果に表示され、購入者が検索できるようになります</li>
      <li>購入者からのお問い合わせには、@auth<a href="{{ route('member.message-box.index') }}">メッセージ</a>@else「メッセージ」@endauth機能からご返信いただけます</li>
    </ul>
  </section>

  <section class="static-guide__section">
    <h2>Step 4　売上確認・出金</h2>
    <ul class="static-guide__list">
      <li>@auth<a href="{{ route('member.sell.sales.index') }}">売上</a>@else「売上」@endauth画面では、売上金額や売れた商品の一覧をご確認いただけます</li>
      <li>@auth<a href="{{ route('member.passbook.index') }}">通帳</a>@else「通帳」@endauthでは、入金・出金の履歴を明細としてご確認いただけます</li>
      <li>売上金を振り込みたい場合は、@auth<a href="{{ route('member.withdrawals.create') }}">出金</a>@else「出金」@endauthよりご登録の口座へ送金手続きをお申し込みください</li>
    </ul>
  </section>
</div>

@endsection
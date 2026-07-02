@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', '教材入手の方法')

@section('content')

<div class="static-guide static-guide--buy">
  <h1>教材入手の方法</h1>

  <p class="static-guide__lead">あおば教材マーケットでは、@guest<a href="{{ route('register') }}">会員登録（無料）</a>@else「会員登録（無料）」@endguest後、3ステップで教材を入手していただけます。全国の先生方が作成した教材を、すぐにご活用いただけます。</p>

  <section class="static-guide__section">
    <h2>Step 1　教材を探す</h2>
    <ul class="static-guide__list">
      <li><a href="{{ route('member.buy.products.index') }}">教材検索</a>より、科目やキーワードなどで条件を絞り込んで教材をお探しいただけます。</li>
      <li>検索結果一覧の教材名や販売者名をクリックすると、詳細ページで内容をご確認いただけます。</li>
    </ul>
  </section>

  <section class="static-guide__section">
    <h2>Step 2　教材を購入する</h2>
    <ul class="static-guide__list">
      <li>購入手続きには、まず@guest<a href="{{ route('register') }}">新規登録</a>@else「新規登録」@endguestにて会員登録を行ってください（無料）。</li>
      <li>ご注文時に、<a href="{{ route('static.copyright-purchaser') }}">利用方法（個人・学校・商用）</a>をお選びください。</li>
      <li>お支払い方法は、現在クレジットカード払いのみとなっております。</li>
    </ul>
  </section>

  <section class="static-guide__section">
    <h2>Step 3　購入した教材をダウンロードする</h2>
    <ul class="static-guide__list">
      <li>購入済みの教材は、@auth<a href="{{ route('member.buy.orders.index') }}">入手済教材</a>@else「入手済教材」@endauthに一覧表示されます。</li>
      <li>ダウンロードしたい教材の商品名をクリックし、商品詳細ページを開いてください。</li>
      <li>商品ファイル名をクリックし、「ダウンロード」ボタンを押して教材ファイルをダウンロードいただけます。</li>
      <li>ダウンロードした教材をご利用の際は、<a href="{{ route('static.terms') }}">利用規約</a>と<a href="{{ route('static.copyright-purchaser') }}">著作権上の注意点（購入者）</a>をご確認のうえお守りください。</li>
    </ul>
  </section>
</div>

@endsection

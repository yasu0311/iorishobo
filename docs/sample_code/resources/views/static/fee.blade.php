@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', 'ご利用料金')

@section('content')

<div class="static-guide static-guide--fee">
  <h1>ご利用料金</h1>

  <p class="static-guide__lead">あおば教材マーケットをご利用いただくには、会員登録（無料）が必要です。教材の購入・販売ともに、月額料金や出品手数料はいただいておりません。</p>

  <section class="static-guide__section">
    <h2>教材を購入する場合</h2>
    <ul class="static-guide__list">
      <li>月額使用料はかかりません</li>
    </ul>
  </section>

  <section class="static-guide__section">
    <h2>教材を販売する場合</h2>
    <ul class="static-guide__list">
      <li>月額使用料・販売システム利用料・出品手数料はかかりません</li>
      <li>教材が売れた際に、手数料（教材価格の15％）が発生します</li>
      <li>売上金を出金する際に、1回あたり手数料200円が発生します</li>
    </ul>
  </section>

  <div class="static-guide__note">
    <p>※ 教材が売れて初めて料金が発生します</p>
    <p>※ 教材を出品するだけで発生する料金はありません</p>
  </div>
</div>

@endsection

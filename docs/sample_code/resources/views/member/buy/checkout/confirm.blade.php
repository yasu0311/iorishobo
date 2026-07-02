@extends('layouts.member')

@section('title', '注文情報確認')

@section('content')

<h1>注文情報確認</h1>
<x-alert/>
<div class="width-md table-vertical-responsive">
  <table>
    <tbody>
      <tr>
        <th>
          商品名
        </th>
        <td>
          {{ $order->product->product_name }}
        </td>
      </tr>
      <tr>
        <th>
          販売者
        </th>
        <td>
          {{ $order->product->shop?->shop_name }}
        </td>
      </tr>
      <tr>
        <th>
          注文者
        </th>
        <td>
          {{ $order->member->nickname }}
        </td>
      </tr>
      <tr>
        <th>
          利用
        </th>
        <td>
          {{ $order->usage_text }}
        </td>
      </tr>
      <tr>
        <th>
          購入権利者
        </th>
        <td>
          {{ $order->licence }}
        </td>
      </tr>
      <tr>
        <th>
          単価(税抜)
        </th>
        <td>
          {{ number_format($order->price) }}円
          <div class="hidden" id="tax-rate">{{ $order->tax_rate }}</div>
          <input type="hidden" name="tax_rate" value="{{ $order->tax_rate }}">
        </td>
      </tr>
      <tr>
        <th>
          数量
        </th>
        <td>
          {{ $order->quantity }}
        </td>
      </tr>
      <tr>
        <th>
          消費税額
        </th>
        <td>
          {{ number_format($order->tax_amount) }}円
        </td>
      </tr>
      <tr>
        <th>
          合計金額
        </th>
        <td>
          {{ number_format($order->total_amount) }}円
        </td>
      </tr>
      @if($order->points_paid > 0)
      <tr>
        <th>
          残高利用
        </th>
        <td>
          −{{ number_format($order->points_paid) }}円
        </td>
      </tr>
      @endif
      <tr>
        <th>
          お支払い金額
        </th>
        <td>
          <strong>{{ number_format($order->amount_paid) }}円</strong>
          @if($order->amount_paid > 0)
            <span class="gray text-sm">（カードでお支払いいただく金額）</span>
          @else
            <span class="gray text-sm">（0円のためクレジットカード決済は不要です）</span>
          @endif
        </td>
      </tr>
      @if($order->amount_paid > 0)
        <tr>
          <th>
            クレジットカード情報
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>カード情報は暗号化され決済代行業者（Square）に送信されます。当サイトではカード番号を保存しません。セキュリティコード（CVV/CVC）は、Visa・Mastercard・JCB などではカード裏面の署名欄付近にある3桁の番号です。American Express はカード表面の4桁です。</span>
            </span>
          </th>
          <td>
            <div id="card-container"></div>
            <div id="card-errors" class="mt-2 red"></div>
          </td>
        </tr>
      @endif
    </tbody>
  </table>
</div>
@if($order->amount_paid > 0)
  <div class="mt-4 center">
    <a href="{{ route('member.buy.checkout.create', ['product_number' => $order->product->product_number, 'usage' => $order->usage]) }}" class="btn btn-white">もどる</a>
    <button id="submit-button" class="btn btn-primary">購入する</button>
  </div>
@else
  <form method="POST" action="{{ route('member.buy.checkout.process') }}" class="mt-4 center" id="checkout-process-form">
    @csrf
    <input type="hidden" name="order_id" value="{{ $order->id }}">
    <a href="{{ route('member.buy.checkout.create', ['product_number' => $order->product->product_number, 'usage' => $order->usage]) }}" class="btn btn-white">もどる</a>
    <button type="submit" class="btn btn-primary" id="checkout-process-submit">購入を確定する</button>
  </form>
@endif

@if(session('insufficient_balance'))
  <div class="mt-2 center">
    <a href="{{ route('member.buy.products.show', ['product' => $order->product]) }}" class="btn btn-secondary">
      商品ページに戻る
    </a>
  </div>
@endif
@endsection

@section('script')
  @if($order->amount_paid > 0)
    @if(($squareEnvironment ?? 'sandbox') === 'production')
      <script type="text/javascript" src="https://web.squarecdn.com/v1/square.js"></script>
    @else
      <script type="text/javascript" src="https://sandbox.web.squarecdn.com/v1/square.js"></script>
    @endif
    <script>
      const applicationId = @json($squareApplicationId);
      const locationId = @json($squareLocationId);

      const submitButton = document.getElementById('submit-button');
      let card;

      async function initializeSquare() {
        if (!window.Square) {
          document.getElementById('card-errors').innerText = '決済SDKの読み込みに失敗しました。';
          return;
        }
        try {
          const payments = window.Square.payments(applicationId, locationId);
          card = await payments.card();
          await card.attach('#card-container');
        } catch (e) {
          console.error("Square init error:", e);
          document.getElementById('card-errors').innerText = '決済の初期化に失敗しました。';
        }
      }

      async function tokenizeAndPay() {
        const errorsDiv = document.getElementById('card-errors');
        errorsDiv.innerText = '';
        // 以前の「商品ページに戻る」リンクがあれば消す
        const oldLink = document.getElementById('insufficient-balance-link-inline');
        if (oldLink) {
          oldLink.remove();
        }
        submitButton.disabled = true;
        if (typeof showSpinner === 'function') {
          showSpinner('決済処理中です。しばらくお待ちください...');
        }
        try {
          const result = await card.tokenize();
          if (result.status !== 'OK') {
            const message = (result.errors && result.errors[0] && result.errors[0].message) || 'カードの検証に失敗しました。';
            errorsDiv.innerText = message;
            submitButton.disabled = false;
            if (typeof hideSpinner === 'function') hideSpinner();
            return;
          }

          const res = await fetch(@json(route('member.buy.checkout.process')), {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': @json(csrf_token()),
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              order_id: @json($order->id),
              source_id: result.token,
            })
          });

          if (res.redirected) {
            window.location.href = res.url;
            return;
          }

          if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            errorsDiv.innerText = data.message || '決済に失敗しました。';

            // 残高不足など、商品ページからの再手続きを促す場合はリンクを表示
            if (data.redirect_url && data.message && data.message.indexOf('商品ページから再度ご注文手続きを行ってください') !== -1) {
              const link = document.createElement('a');
              link.id = 'insufficient-balance-link-inline';
              link.href = @json(route('member.buy.products.show', ['product' => $order->product]));
              link.className = 'btn btn-secondary mt-2 inline-block';
              link.textContent = '商品ページに戻る';
              errorsDiv.insertAdjacentElement('afterend', link);
            }

            submitButton.disabled = false;
            if (typeof hideSpinner === 'function') hideSpinner();
            return;
          }

          // 正常時（Laravelのredirectレスポンスなど）
          const data = await res.json().catch(() => null);
          if (data && data.redirect_url) {
            window.location.href = data.redirect_url;
          } else {
            // 直接リダイレクトされない場合の予備
            window.location.href = @json(route('member.buy.checkout.complete', $order));
          }
        } catch (e) {
          errorsDiv.innerText = '決済処理中にエラーが発生しました。';
          submitButton.disabled = false;
          if (typeof hideSpinner === 'function') hideSpinner();
        }
      }

      submitButton.addEventListener('click', function (e) {
        e.preventDefault();
        tokenizeAndPay();
      });

      window.addEventListener('load', initializeSquare);
    </script>
  @else
    <script>
      document.getElementById('checkout-process-form')?.addEventListener('submit', function () {
        const submitButton = document.getElementById('checkout-process-submit');
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.textContent = '処理中...';
        }
      });
    </script>
  @endif
@endsection

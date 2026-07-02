@extends('layouts.member')

@section('title', '注文情報入力')

@section('content')

<h1>注文情報入力</h1>

<x-alert/>
<form action="{{ route('member.buy.checkout.store') }}" method="POST" id="checkout-create-form">
  @csrf
  <input type="hidden" name="product_id" value="{{ $product->id }}">
  <input type="hidden" name="member_id" value="{{ auth()->user()->member->id }}">
<div class="width-md table-vertical-responsive">
  <table>
    <tbody>
      <tr>
        <th>
          商品名
        </th>
        <td>
          {{ $product->product_name }}
        </td>
      </tr>
      <tr>
        <th>
          販売者
        </th>
        <td>
          {{ $product->shop->shop_name }}            
        </td>
      </tr>            
      <tr>
        <th>
          注文者
        </th>
        <td>
          {{ auth()->user()->member->nickname }}
        </td>
      </tr>
      <tr>
        <th>
          利用
        </th>
        <td>
          <input type="hidden" name="usage" value="{{ $usage }}">
          {{ \App\Models\Order::usageList()[$usage] }}
          <div class="gray text-xs">
            @php
              $usageList = \App\Models\Order::usageList();
              $otherUsages = array_diff_key($usageList, [$usage => '']);
            @endphp
            {{ implode('・', $otherUsages) }}の場合は、<a href="{{ route('member.buy.products.show', $product) }}">商品ページ</a>に戻って選択しなおしてください。
          </div>
        </td>
      </tr>
      <tr>
        <th>
          <div class="badge badge-red">必須</div>購入権利者
        </th>
        <td>
          <textarea name="licence" required>{{ auth()->user()->member->name }}</textarea>
          <div class="gray text-xs">
            領収書・明細に記載され、利用範囲（個人/組織）の根拠になります。
            教員名・学校名・塾名・個人名など利用形態に合わせて入力してください。
            詳しくは<a href="{{ route('static.copyright-purchaser') }}" target="_blank">著作権上の注意点（購入者）</a>をご確認ください。
          </div>
        </td>
      </tr>

      <tr>
        <th>
          単価(税抜)
        </th>
        <td>
          <input type="hidden" name="price" value="{{ $product->getPrice($usage) }}">
          {{ number_format($product->getPrice($usage)) }}円
          <div class="hidden" id="tax-rate">{{ $product->shop?->getConsumptionTaxRate() }}</div>
          <div class="hidden" id="balance">{{ $balance }}</div>
          <input type="hidden" name="tax_rate" value="{{ $product->shop?->getConsumptionTaxRate() }}">
        </td>
      </tr>
      <tr>
        <th>
          <div class="badge badge-red">必須</div>数量
        </th>
        <td>
          <input type="number" class="wd-4" name="quantity" value="1" min="1" required>
        </td>
      </tr>
      @if($balance > 0)
      <tr>
        <th>
          残高利用
          <span class="help">
            <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
            <span class="help__content" role="tooltip" hidden>
              通帳の残高を支払いに充当できます。
            </span>
          </span>
        </th>
        <td>
          現在の残高：{{ number_format($balance) }}円<br>
          <input type="text" class="wd-4 amount-input" name="points_paid" value="">円
        </td>
      </tr>
      @else
        {{-- 残高が0円の場合は入力欄を表示せず、サーバ側では0円として扱う --}}
        <input type="hidden" name="points_paid" value="0">
      @endif
      <tr>
        <th>
          お支払い金額
          <span class="help">
            <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
            <span class="help__content" role="tooltip" hidden>単価×数量＋消費税−残高利用で計算されます。カードでお支払いいただく金額です。</span>
          </span>
        </th>
        <td>
          <div id="calculating-formula" class="text-sm gray"></div>
          <div id="total-amount" class="text-lg bold"></div>
          <button type="button" onclick="calculateTotal()">更新</button>
        </td>
      </tr>
    </tbody>
  </table>
</div>
<div class="center">
  <div>
    <label><input type="checkbox" name="agree" required><a href="{{ route('static.terms') }}" target="_blank">利用規約</a>に同意します</label>
  </div>
  <div class="mt-1 text-sm gray">
    
  </div>
  <a href="{{ route('member.buy.products.show', $product) }}" class="btn btn-white">
    商品ページへもどる
  </a>
  <button class="btn btn-primary" type="submit" id="checkout-create-submit">注文内容を確認する</button>
</form>
</div>

@endsection

@section('script')
<script>
  function calculateTotal() {
    let price = parseInt(document.querySelector('input[name="price"]').value, 10) || 0;
    let quantity = parseInt(document.querySelector('input[name="quantity"]').value, 10) || 1;
    let pointsPaidInput = document.querySelector('input[name="points_paid"]');
    let pointsPaidVal = pointsPaidInput ? pointsPaidInput.value.replace(/,/g, '') : '0';
    let pointsPaid = parseInt(pointsPaidVal, 10) || 0;
    let taxRateEl = document.querySelector('div[id="tax-rate"]');
    let taxRate = taxRateEl ? parseFloat(taxRateEl.innerText) : 0;
    if (Number.isNaN(taxRate) || taxRate < 0) {
      taxRate = 0;
    }
    let taxAmount = Math.floor(price * quantity * taxRate);
    let total = Math.max(0, price * quantity + taxAmount - pointsPaid);
    if (Number.isNaN(total)) {
      total = 0;
    }
    document.getElementById('total-amount').innerText = total.toLocaleString() + '円';
    let balanceEl = document.querySelector('div[id="balance"]');
    let balance = balanceEl ? parseInt(balanceEl.innerText, 10) || 0 : 0;
    let formula = `${price.toLocaleString()}円[単価] × ${quantity}[数量] + ${taxAmount.toLocaleString()}円[消費税額]`;
    if (balance > 0) {
      formula += ` - ${pointsPaid.toLocaleString()}円[残高利用]`;
    }
    document.getElementById('calculating-formula').innerText = formula;
  }

  document.querySelector('input[name="quantity"]').addEventListener('input', calculateTotal);
  (function () {
    let pointsPaidInput = document.querySelector('input[name="points_paid"]');
    if (pointsPaidInput && pointsPaidInput.type !== 'hidden') {
      pointsPaidInput.addEventListener('input', calculateTotal);
    }
  })();

  document.getElementById('checkout-create-form').addEventListener('submit', function () {
    const submitButton = document.getElementById('checkout-create-submit');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = '送信中...';
    }
  });

  window.onload = calculateTotal;
</script>
@endsection
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />  
    <title>{{ config('app.name') }} 注文明細</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <style>
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        padding: 2em;
        background-color: #fff;
        font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
        font-size: 11pt;
        line-height: 1.5;
        color: #333;
      }
      .invoice {
        max-width: 210mm;
        margin: 0 auto;
        padding: 0;
      }

      /* ヘッダー */
      .invoice-header {
        text-align: center;
        border-bottom: 2px solid #222;
        padding-bottom: 1em;
        margin-bottom: 1.5em;
      }
      .invoice-title {
        font-size: 1.6em;
        font-weight: bold;
        letter-spacing: 0.2em;
        margin: 0 0 0.3em;
      }
      .invoice-subtitle {
        font-size: 0.9em;
        color: #666;
        margin: 0;
      }

      /* 購入者（上部） */
      .invoice-buyer {
        padding: 1em;
        border: 1px solid #ccc;
        background: #fafafa;
        margin-bottom: 1.5em;
      }
      .invoice-party {
        padding: 1em;
        border: 1px solid #ccc;
        background: #fafafa;
      }
      .invoice-party-label {
        font-size: 0.85em;
        color: #666;
        margin-bottom: 0.5em;
        border-bottom: 1px solid #ddd;
        padding-bottom: 0.3em;
      }
      .invoice-party-name {
        font-weight: bold;
        font-size: 1.05em;
        margin-bottom: 0.5em;
      }
      .invoice-party-detail {
        font-size: 0.9em;
        color: #555;
        white-space: pre-line;
      }

      /* 注文情報 */
      .invoice-meta {
        display: flex;
        gap: 2em;
        margin-bottom: 1.5em;
        font-size: 0.95em;
      }
      .invoice-meta-item {
        display: flex;
        gap: 0.5em;
      }
      .invoice-meta-label {
        font-weight: bold;
        min-width: 5em;
      }

      /* 明細テーブル */
      .invoice-items {
        margin-bottom: 1.5em;
      }
      .invoice-items table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95em;
      }
      .invoice-items th {
        padding: 0.6em 0.8em;
        text-align: left;
        font-weight: bold;
        font-size: 0.9em;
        border-bottom: 2px solid #333;
      }
      .invoice-items td {
        padding: 0.6em 0.8em;
        border-bottom: 1px solid #ddd;
      }
      .invoice-items tbody tr:hover {
        background: #f9f9f9;
      }
      .invoice-items .text-right {
        text-align: right;
      }
      .invoice-items .text-center {
        text-align: center;
      }

      /* 金額サマリー */
      .invoice-summary {
        margin-left: auto;
        width: 280px;
        border: 1px solid #333;
        margin-bottom: 1.5em;
      }
      .invoice-summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5em 1em;
        border-bottom: 1px solid #ddd;
      }
      .invoice-summary-row:last-child {
        border-bottom: none;
        background: #333;
        color: #fff;
        font-weight: bold;
        font-size: 1.1em;
      }
      .invoice-summary-row.total {
        background: #333;
        color: #fff;
        font-weight: bold;
        font-size: 1.15em;
      }

      /* お支払い情報 */
      .invoice-payment {
        padding: 1em;
        background: #f5f5f5;
        border-left: 4px solid #333;
        margin-bottom: 1.5em;
      }
      .invoice-payment-title {
        font-weight: bold;
        margin-bottom: 0.5em;
      }
      .invoice-payment-detail {
        font-size: 0.9em;
        color: #555;
      }

      /* 販売者（ページ下部） */
      .invoice-seller-block {
        padding: 1em;
        border: 1px solid #ccc;
        background: #fafafa;
        margin-top: 2em;
        margin-bottom: 1em;
      }

      /* 販売者情報 */
      .invoice-seller {
        padding: 1em;
        border: 1px solid #ddd;
        margin-bottom: 1.5em;
      }
      .invoice-seller-title {
        font-weight: bold;
        font-size: 0.95em;
        margin-bottom: 0.5em;
      }
      .invoice-seller-content {
        font-size: 0.9em;
        color: #555;
        white-space: pre-line;
      }

      /* フッター */
      .invoice-footer {
        margin-top: 2em;
        padding-top: 1em;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 0.85em;
        color: #666;
      }

      /* 印刷ボタン */
      .print-button {
        display: block;
        width: 120px;
        margin: 0 auto 1.5em;
        padding: 0.6em 1em;
        text-align: center;
        cursor: pointer;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 4px;
        font-size: 0.95em;
      }
      .print-button:hover {
        background: #555;
      }
      .invoice-divider {
        border: none;
        border-top: 2px solid #222;
        margin: 0 0 1.5em;
      }
      @media print {
        body {
          padding: 0;
        }
        .print-button,
        .invoice-divider {
          display: none !important;
        }
      }
    </style>
  </head>
  <body>
    <div class="invoice">
      <button class="print-button" onclick="window.print()">印刷する</button>
      <hr class="invoice-divider">

      {{-- ヘッダー --}}
      <header class="invoice-header">
        <h1 class="invoice-title">注文明細書</h1>
        <p class="invoice-subtitle">Invoice / Order Statement</p>
      </header>

      {{-- 購入者（上部） --}}
      <div class="invoice-buyer">
        <div class="invoice-party-label">お客様（購入者）</div>
        <div class="invoice-party-name">{{ $order->member?->nickname ?? 'お客様' }}</div>
        <div class="invoice-party-detail">注文番号: {{ $order->order_number }}</div>
      </div>

      {{-- 注文情報 --}}
      <div class="invoice-meta">
        <div class="invoice-meta-item">
          <span class="invoice-meta-label">発行日:</span>
          <span>{{ $order->ordered_at->format('Y年n月j日') }}</span>
        </div>
        <div class="invoice-meta-item">
          <span class="invoice-meta-label">発行時刻:</span>
          <span>{{ $order->ordered_at->format('H:i') }}</span>
        </div>
      </div>

      {{-- 明細テーブル --}}
      <section class="invoice-items">
        <table>
          <thead>
            <tr>
              <th>商品名</th>
              <th class="text-center">利用方法</th>
              <th class="text-center">購入権利者</th>
              <th class="text-right">単価(税抜)</th>
              <th class="text-center">数量</th>
              <th class="text-right">小計(税抜)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <strong>{{ $order->product_name }}</strong>
                <div style="font-size: 0.85em; color: #666;">商品ID: {{ $order->product->product_number }}</div>
              </td>
              <td class="text-center">{{ $order->usage_text }}</td>
              <td class="text-center">{{ $order->licence }}</td>
              <td class="text-right">{{ number_format($order->price) }}円</td>
              <td class="text-center">{{ number_format($order->quantity) }}</td>
              <td class="text-right">{{ number_format($order->price * $order->quantity) }}円</td>
            </tr>
          </tbody>
        </table>
      </section>

      {{-- 金額サマリー --}}
      <div class="invoice-summary">
        <div class="invoice-summary-row">
          <span>小計(税抜)</span>
          <span>{{ number_format($order->price * $order->quantity) }}円</span>
        </div>
        <div class="invoice-summary-row">
          <span>消費税({{ $order->tax_rate_text }})</span>
          <span>{{ number_format($order->tax_amount) }}円</span>
        </div>
        <div class="invoice-summary-row">
          <span>合計金額(税込)</span>
          <span>{{ number_format($order->total_amount) }}円</span>
        </div>
        <div class="invoice-summary-row total">
          <span>合計</span>
          <span>{{ number_format($order->total_amount) }}円</span>
        </div>
      </div>

      {{-- お支払い情報 --}}
      <div class="invoice-payment">
        <div class="invoice-payment-title">お支払いについて</div>
        <div class="invoice-payment-detail">
          <div>残高利用: {{ number_format($order->points_paid) }}円</div>
          <div>クレジットカード決済: {{ number_format($order->amount_paid) }}円</div>
        </div>
      </div>

      {{-- 販売者（ページ下部） --}}
      <div class="invoice-seller-block">
        <div class="invoice-party-label">販売者</div>
        <div class="invoice-party-name">{{ $order->product->shop->shop_name }}</div>
        <div class="invoice-party-detail">{{ $order->product->shop->shop_information }}</div>
      </div>
      @if($order->product->shop->receipt_description)
      <div class="invoice-seller">
        <div class="invoice-seller-title">販売者からのご案内</div>
        <div class="invoice-seller-content">{{ $order->product->shop->receipt_description }}</div>
      </div>
      @endif

      {{-- フッター --}}
      <footer class="invoice-footer">
        <div>{{ config('app.name') }}</div>
        <div>{{ config('app.url') }}</div>
        <div style="margin-top: 0.5em;">本明細書は{{ config('app.name') }}にて発行されたものです。</div>
      </footer>
    </div>
  </body>
</html>

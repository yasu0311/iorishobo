@php
    /** @var array{method: \App\Models\ShippingMethod, fee: int}|null $option */
    $method = $option['method'] ?? null;
    $fee = $option['fee'] ?? null;
    $threshold = $method?->free_shipping_threshold;
@endphp
@if ($method === null || $fee === null)
@elseif ($fee === 0)
    <span class="checkout-shipping-notice__free">この配送方法は<strong>送料無料</strong>です。</span>
@elseif ($threshold !== null)
    あと<strong>{{ number_format(max(0, $threshold - $goodsTotal)) }}円</strong>で送料無料になります（商品合計{{ number_format($threshold) }}円以上）。
@else
    この配送方法の送料は{{ number_format($fee) }}円です。
@endif

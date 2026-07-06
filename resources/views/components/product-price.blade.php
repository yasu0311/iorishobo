@props(['product'])

@if ($product->formattedPrice() !== null)
    <p {{ $attributes->merge(['class' => 'product-card__price']) }}>
        {{ $product->formattedPrice() }}<span class="product-card__tax">（税込）</span>
    </p>
@endif

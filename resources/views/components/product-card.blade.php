@props(['product'])

<article class="product-card">
    <a href="{{ route('products.show', $product->slug) }}" class="product-card__link">
        <div class="product-card__image">
            @if ($product->mainImage)
                <img src="{{ $product->mainImage->url() }}" alt="{{ $product->name }}" loading="lazy">
            @else
                <span class="product-card__placeholder" aria-hidden="true">No image</span>
            @endif
            @if (! $product->hasPurchasableVariant())
                <span class="product-card__badge">売り切れ</span>
            @endif
        </div>
        <h3 class="product-card__name">{{ $product->name }}</h3>
        <x-product-price :product="$product" />
    </a>
</article>

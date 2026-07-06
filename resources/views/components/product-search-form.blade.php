@props([
    'value' => '',
    'inputId' => 'product-search-q',
])

<form method="get" action="{{ route('products.index') }}" {{ $attributes->merge(['class' => 'product-search']) }}>
    <label for="{{ $inputId }}" class="sr-only">商品名で検索</label>
    <input
        type="search"
        id="{{ $inputId }}"
        name="q"
        class="product-search__input"
        value="{{ $value }}"
        placeholder="商品名で検索"
        aria-label="商品名で検索"
    >
    <button type="submit" class="product-search__submit">検索</button>
</form>

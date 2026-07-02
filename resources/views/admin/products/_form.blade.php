@php
    $isEdit = $product !== null;
@endphp

<form method="post" action="{{ $action }}" class="panel">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="flash flash--error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="form-section">
        <h3 class="form-section__title">基本情報</h3>
        <div class="form-grid">
            <div class="form-field">
                <label for="product-name">商品名 <span class="form-hint">必須</span></label>
                <input type="text" id="product-name" name="name" value="{{ old('name', $product?->name) }}" required maxlength="255">
            </div>

            <div class="form-field">
                <label for="product-category">カテゴリ</label>
                <select id="product-category" name="category_id">
                    <option value="">— 未設定 —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>
                            @if ($category->parent)
                                {{ $category->parent->name }} / {{ $category->name }}
                            @else
                                {{ $category->name }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="product-base-price">基本価格（税込） <span class="form-hint">必須</span></label>
                <input type="number" id="product-base-price" name="base_price" value="{{ old('base_price', $product?->base_price ?? 0) }}" min="0" required>
            </div>

            <div class="form-field">
                <label for="product-sort-order">表示順</label>
                <input type="number" id="product-sort-order" name="sort_order" value="{{ old('sort_order', $product?->sort_order ?? 0) }}" min="0">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section__title">説明文</h3>
        <div class="form-field">
            <label for="product-short-description">簡易説明</label>
            <textarea id="product-short-description" name="short_description" rows="2">{{ old('short_description', $product?->short_description) }}</textarea>
        </div>

        <div class="form-field">
            <label for="product-description">商品説明 <span class="form-hint">HTML 可</span></label>
            <textarea id="product-description" name="description" rows="6">{{ old('description', $product?->description) }}</textarea>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section__title">公開設定</h3>
        <div class="form-checkboxes">
            <label>
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $product?->is_published ?? false))>
                掲載する
            </label>
            <label>
                <input type="checkbox" name="stock_managed" value="1" @checked(old('stock_managed', $product?->stock_managed ?? false))>
                在庫管理する
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit">{{ $isEdit ? '更新' : '登録' }}</button>
    </div>
</form>

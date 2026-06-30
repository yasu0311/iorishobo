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

    <div class="form-grid">
        <label>
            商品名 *
            <input type="text" name="name" value="{{ old('name', $product?->name) }}" required maxlength="255">
        </label>

        <label>
            カテゴリ
            <select name="category_id">
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
        </label>

        <label>
            基本価格（税込） *
            <input type="number" name="base_price" value="{{ old('base_price', $product?->base_price ?? 0) }}" min="0" required>
        </label>

        <label>
            表示順
            <input type="number" name="sort_order" value="{{ old('sort_order', $product?->sort_order ?? 0) }}" min="0">
        </label>
    </div>

    <label>
        簡易説明
        <textarea name="short_description" rows="2">{{ old('short_description', $product?->short_description) }}</textarea>
    </label>

    <label>
        商品説明（HTML 可）
        <textarea name="description" rows="6">{{ old('description', $product?->description) }}</textarea>
    </label>

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

    <button type="submit">{{ $isEdit ? '更新' : '登録' }}</button>
</form>

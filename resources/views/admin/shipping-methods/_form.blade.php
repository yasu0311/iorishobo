@php
    $isEdit = isset($shippingMethod);
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
            名称 *
            <input type="text" name="name" value="{{ old('name', $shippingMethod->name ?? '') }}" required maxlength="255">
        </label>
        <label>
            slug *
            <input type="text" name="slug" value="{{ old('slug', $shippingMethod->slug ?? '') }}" required maxlength="50" @readonly($isEdit)>
        </label>
        <label>
            基本送料（円） *
            <input type="number" name="base_fee" value="{{ old('base_fee', $shippingMethod->base_fee ?? 0) }}" min="0" required>
        </label>
        <label>
            送料無料ライン（円）
            <input type="number" name="free_shipping_threshold" value="{{ old('free_shipping_threshold', $shippingMethod->free_shipping_threshold ?? '') }}" min="0" placeholder="なし">
        </label>
        <label>
            表示順
            <input type="number" name="sort_order" value="{{ old('sort_order', $shippingMethod->sort_order ?? 0) }}" min="0">
        </label>
    </div>

    <label>
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shippingMethod->is_active ?? true))>
        有効（チェックアウトに表示）
    </label>

    <button type="submit">{{ $isEdit ? '更新' : '登録' }}</button>
</form>

@php
    $isEdit = $coupon !== null;
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
                <label for="coupon-code">クーポンコード <span class="form-hint">必須</span></label>
                <input type="text" id="coupon-code" name="code" value="{{ old('code', $coupon?->code) }}" required maxlength="50">
            </div>
            <div class="form-field">
                <label for="coupon-name">表示名 <span class="form-hint">必須</span></label>
                <input type="text" id="coupon-name" name="name" value="{{ old('name', $coupon?->name) }}" required maxlength="255">
            </div>
            <div class="form-field">
                <label for="coupon-discount">割引額（円） <span class="form-hint">必須</span></label>
                <input type="number" id="coupon-discount" name="discount_amount" value="{{ old('discount_amount', $coupon?->discount_amount ?? 100) }}" min="1" required>
            </div>
            <div class="form-field">
                <label for="coupon-min-order">最低注文金額（円）</label>
                <input type="number" id="coupon-min-order" name="min_order_amount" value="{{ old('min_order_amount', $coupon?->min_order_amount) }}" min="0" placeholder="制限なし">
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section__title">利用条件</h3>
        <div class="form-grid">
            <div class="form-field">
                <label for="coupon-starts">開始日時</label>
                <input type="datetime-local" id="coupon-starts" name="starts_at" value="{{ old('starts_at', $coupon?->starts_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="form-field">
                <label for="coupon-ends">終了日時</label>
                <input type="datetime-local" id="coupon-ends" name="ends_at" value="{{ old('ends_at', $coupon?->ends_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="form-field">
                <label for="coupon-max-uses">利用上限回数 <span class="form-hint">全ユーザー合計・空欄=無制限</span></label>
                <input type="number" id="coupon-max-uses" name="max_uses" value="{{ old('max_uses', $coupon?->max_uses) }}" min="1" placeholder="無制限">
            </div>
        </div>

        @if ($isEdit)
            <p class="text-muted">利用済み回数: {{ $coupon->used_count }}（変更不可）</p>
        @endif

        <div class="form-checkboxes">
            <label>
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon?->is_active ?? true))>
                有効
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit">{{ $isEdit ? '更新' : '登録' }}</button>
    </div>
</form>

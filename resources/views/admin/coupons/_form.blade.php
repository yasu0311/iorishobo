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

    <div class="form-grid">
        <label>
            クーポンコード *
            <input type="text" name="code" value="{{ old('code', $coupon?->code) }}" required maxlength="50">
        </label>
        <label>
            表示名 *
            <input type="text" name="name" value="{{ old('name', $coupon?->name) }}" required maxlength="255">
        </label>
        <label>
            割引額（円） *
            <input type="number" name="discount_amount" value="{{ old('discount_amount', $coupon?->discount_amount ?? 100) }}" min="1" required>
        </label>
        <label>
            最低注文金額（円）
            <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon?->min_order_amount) }}" min="0" placeholder="制限なし">
        </label>
        <label>
            開始日時
            <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon?->starts_at?->format('Y-m-d\TH:i')) }}">
        </label>
        <label>
            終了日時
            <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $coupon?->ends_at?->format('Y-m-d\TH:i')) }}">
        </label>
        <label>
            利用上限回数（全ユーザー合計）
            <input type="number" name="max_uses" value="{{ old('max_uses', $coupon?->max_uses) }}" min="1" placeholder="無制限">
        </label>
    </div>

    @if ($isEdit)
        <p>利用済み回数: {{ $coupon->used_count }}（変更不可）</p>
    @endif

    <label>
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon?->is_active ?? true))>
        有効
    </label>

    <button type="submit">{{ $isEdit ? '更新' : '登録' }}</button>
</form>

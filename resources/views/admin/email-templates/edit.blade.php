@extends('layouts.admin')

@section('title', 'メールテンプレート編集')

@section('content')
    <p><a href="{{ route('admin.email-templates.index') }}">← テンプレート一覧</a></p>
    <h1>{{ $template->label }}</h1>

    @if ($template->description)
        <p class="notice" style="color: #6b7280; margin-bottom: 1rem;">{{ $template->description }}</p>
    @endif

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <form method="post" action="{{ route('admin.email-templates.update', $template) }}">
        @csrf
        @method('PUT')

        <div class="form-field">
            <label for="subject">件名</label>
            <input type="text" id="subject" name="subject" value="{{ old('subject', $template->subject) }}" required>
            @error('subject') <p class="input-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-field">
            <label for="body">本文</label>
            <textarea id="body" name="body" rows="30" style="font-family: monospace; white-space: pre; overflow-x: auto; min-height: 20rem;" required>{{ old('body', $template->body) }}</textarea>
            @error('body') <p class="input-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">保存</button>
        </div>
    </form>

    <section class="panel" style="margin-top: 2rem;">
        <h2>使用可能な変数</h2>
        <p style="color: #6b7280; font-size: 0.875rem;">
            識別キー: <code>{{ $template->slug }}</code>
        </p>
        @if (str_starts_with($template->slug, 'order-'))
            <ul style="font-size: 0.875rem; color: #374151;">
                <li><code>@{{ $order->order_number }}</code> — 注文番号</li>
                <li><code>@{{ $order->buyer_name }}</code> — 購入者名</li>
                <li><code>@{{ $order->buyer_email }}</code> — メールアドレス</li>
                <li><code>@{{ $order->total }}</code> — 合計金額</li>
                <li><code>@{{ $order->tracking_number }}</code> — 追跡番号</li>
                <li><code>@{{ $order->shipping_name }}</code> — 配送先名</li>
                <li><code>@{{ number_format($order->total) }}</code> — フォーマット済み金額</li>
                <li><code>@{{ config('shop.name') }}</code> — ショップ名</li>
            </ul>
        @elseif (str_starts_with($template->slug, 'contact-'))
            <ul style="font-size: 0.875rem; color: #374151;">
                <li><code>@{{ $contact['name'] }}</code> — お名前</li>
                <li><code>@{{ $contact['email'] }}</code> — メールアドレス</li>
                <li><code>@{{ $contact['inquiry_type'] }}</code> — お問い合わせ種類</li>
                <li><code>@{{ $contact['message'] }}</code> — お問い合わせ内容</li>
                <li><code>@{{ config('shop.name') }}</code> — ショップ名</li>
            </ul>
        @endif
    </section>
@endsection

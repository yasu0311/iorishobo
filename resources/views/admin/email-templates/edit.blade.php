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
            <label for="subject">件名（固定文言）</label>
            <input type="text" id="subject" name="subject" value="{{ old('subject', $template->subject) }}" required>
            @error('subject') <p class="input-error">{{ $message }}</p> @enderror
            <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.35rem;">
                @if (! $template->usesSubjectInEnvelope())
                    このテンプレートは注文確認メール本文への差し込み専用です。件名は送信に使われません。
                @elseif ($template->slug === 'order-confirmation')
                    送信時の件名: 【ショップ名】<strong>入力内容</strong>（注文番号: …）
                @elseif (str_starts_with($template->slug, 'order-'))
                    送信時の件名: <strong>入力内容</strong>　ショップ名
                @else
                    送信時の件名: 【ショップ名】<strong>入力内容</strong>
                @endif
            </p>
        </div>

        <div class="form-field">
            <label for="body">本文（固定文言）</label>
            <textarea id="body" name="body" rows="20" style="font-family: monospace; white-space: pre; overflow-x: auto; min-height: 16rem;" required>{{ old('body', $template->body) }}</textarea>
            @error('body') <p class="input-error">{{ $message }}</p> @enderror
            <p style="color: #6b7280; font-size: 0.875rem; margin-top: 0.35rem;">
                編集可能な案内文はここだけです。Blade変数（<code>@{{ $order }}</code> 等）は使えません。注文番号・明細・お問い合わせ内容などは本文の前後に自動で付きます。
                @if ($template->appendsBankTransferAmount())
                    送信時、本文の末尾に「お振込金額: （注文総合計）円」が自動で付きます。
                @endif
            </p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">保存</button>
        </div>
    </form>
@endsection

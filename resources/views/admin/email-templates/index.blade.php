@extends('layouts.admin')

@section('title', 'メールテンプレート')

@section('content')
    <h1>メールテンプレート</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <p class="notice" style="color: #6b7280; margin-bottom: 1rem;">
        件名や本文を編集できます。Bladeの変数（<code>@{{ $order->order_number }}</code> 等）はそのまま使用できます。
    </p>

    <table class="admin-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>識別キー</th>
                <th>件名</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($templates as $template)
                <tr>
                    <td>{{ $template->label }}</td>
                    <td><code>{{ $template->slug }}</code></td>
                    <td>{{ Str::limit($template->subject, 60) }}</td>
                    <td><a href="{{ route('admin.email-templates.edit', $template) }}">編集</a></td>
                </tr>
            @empty
                <tr><td colspan="4">テンプレートがありません。</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

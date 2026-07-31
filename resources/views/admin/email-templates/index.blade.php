@extends('layouts.admin')

@section('title', 'メールテンプレート')

@section('content')
    <h1>メールテンプレート</h1>

    @if (session('status'))
        <p class="flash">{{ session('status') }}</p>
    @endif

    <p class="notice" style="color: #6b7280; margin-bottom: 1rem;">
        件名・本文は固定文言のみ編集できます。注文番号やお客様名などの差し込みはシステム側で自動挿入されます。
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

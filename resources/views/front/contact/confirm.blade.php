@extends('layouts.front')

@section('title', 'お問い合わせ内容の確認 - '.config('shop.name'))

@section('content')
    <h1>お問い合わせ内容の確認</h1>

    <div class="panel">
        <table class="data-table">
            <tr>
                <th scope="row">お名前</th>
                <td>{{ $contact['name'] }}</td>
            </tr>
            <tr>
                <th scope="row">メールアドレス</th>
                <td>{{ $contact['email'] }}</td>
            </tr>
            <tr>
                <th scope="row">お問い合わせ種類</th>
                <td>{{ $contact['inquiry_type'] }}</td>
            </tr>
            <tr>
                <th scope="row">お問い合わせ内容</th>
                <td>{!! nl2br(e($contact['message'])) !!}</td>
            </tr>
        </table>
    </div>

    <p style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
        <form method="post" action="{{ route('contacts.back') }}">
            @csrf
            <button type="submit" class="btn btn--secondary">内容を修正する</button>
        </form>
        <form method="post" action="{{ route('contacts.store') }}">
            @csrf
            <button type="submit" class="btn btn--primary">送信する</button>
        </form>
    </p>
@endsection

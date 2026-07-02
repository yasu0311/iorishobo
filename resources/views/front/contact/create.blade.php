@extends('layouts.front')

@section('title', 'お問い合わせ - '.config('shop.name'))

@section('content')
    <h1>お問い合わせ</h1>

    <p>商品・ご注文・配送などに関するお問い合わせは、下記フォームよりお送りください。</p>

    <form method="post" action="{{ route('contacts.confirm') }}" class="panel">
        @csrf

        <div class="form-field">
            <label for="name">お名前 <span class="text-muted">（必須）</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $contact['name'] ?? '') }}" required autofocus>
        </div>

        <div class="form-field">
            <label for="email">メールアドレス <span class="text-muted">（必須）</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $contact['email'] ?? '') }}" required>
        </div>

        <div class="form-field">
            <label for="inquiry_type">お問い合わせ種類 <span class="text-muted">（必須）</span></label>
            <select id="inquiry_type" name="inquiry_type" required>
                <option value="" disabled {{ old('inquiry_type', $contact['inquiry_type'] ?? '') === '' ? 'selected' : '' }}>選択してください</option>
                @foreach (\App\Http\Requests\ContactRequest::inquiryTypes() as $type)
                    <option value="{{ $type }}" {{ old('inquiry_type', $contact['inquiry_type'] ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-field">
            <label for="message">お問い合わせ内容 <span class="text-muted">（必須・1000文字以内）</span></label>
            <textarea id="message" name="message" rows="8" maxlength="1000" required>{{ old('message', $contact['message'] ?? '') }}</textarea>
        </div>

        <p>
            <button type="submit" class="btn btn--primary">送信内容を確認する</button>
        </p>
    </form>
@endsection

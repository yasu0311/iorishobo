@extends('layouts.mail-text')

@section('content')

新しいお問い合わせが届きました。内容を確認の上、ご対応ください。

================================================
お名前: {{ $contact->name }}
メールアドレス: {{ $contact->email }}
お問い合わせ種類: {{ $contact->inquiry_type }}
送信日時: {{ $contact->created_at?->format('Y年n月j日 H:i') }}
================================================

お問い合わせ内容:
{{ $contact->message }}

================================================

@endsection

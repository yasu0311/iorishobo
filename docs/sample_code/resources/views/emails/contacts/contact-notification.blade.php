@extends('layouts.mail-text')

@section('content')

お問い合わせありがとうございました。

================================================
お名前: {{ $contact->name }}
メールアドレス: {{ $contact->email }}
お問い合わせ種類: {{ $contact->inquiry_type }}
お問い合わせ内容: {{ $contact->message }}
================================================
内容を確認の上、担当者よりご連絡いたしますので、今しばらくお待ちください。

@endsection
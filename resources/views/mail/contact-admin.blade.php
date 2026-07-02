{{ config('shop.name') }} お問い合わせ（管理者通知）

お名前: {{ $contact['name'] }}
メールアドレス: {{ $contact['email'] }}
お問い合わせ種類: {{ $contact['inquiry_type'] }}

--- お問い合わせ内容 ---
{{ $contact['message'] }}

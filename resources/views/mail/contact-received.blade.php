{{ $contact['name'] }} 様

{{ config('shop.name') }} へのお問い合わせを受け付けました。
{{ $body }}

お問い合わせ種類: {{ $contact['inquiry_type'] }}

--- お問い合わせ内容 ---
{{ $contact['message'] }}

{{ config('shop.name') }}

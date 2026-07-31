{{ $contact['name'] }} 様

{{ config('shop.name') }} へのお問い合わせを受け付けました。
@if (filled($body ?? null))
{{ $body }}
@else
内容を確認のうえ、担当者よりご連絡いたします。
※このメールは自動送信です。返信いただいてもお答えできない場合があります。
@endif

お問い合わせ種類: {{ $contact['inquiry_type'] }}

--- お問い合わせ内容 ---
{{ $contact['message'] }}

{{ config('shop.name') }}

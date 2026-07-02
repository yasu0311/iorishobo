{{ $contact['name'] }} 様

{{ config('shop.name') }} へのお問い合わせを受け付けました。
内容を確認のうえ、担当者よりご連絡いたします。

お問い合わせ種類: {{ $contact['inquiry_type'] }}

--- お問い合わせ内容 ---
{{ $contact['message'] }}

※このメールは自動送信です。返信いただいてもお答えできない場合があります。

{{ config('shop.name') }}

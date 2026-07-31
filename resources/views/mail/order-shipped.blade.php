@if (filled($body ?? null))
{{ $body }}
@elseif ($partial ?? false)
ご注文の一部を発送いたしました。
残りの商品は準備ができ次第、あらためて発送いたします。

到着まで今しばらくお待ちください。
よろしくお願いいたします。
@else
このたびはご注文ありがとうございました。
商品の発送手続きをいたしましたのでご連絡いたします。
商品の到着まで今しばらくお待ちください。
よろしくお願いいたします。

＜ゆうパックを選択された方＞
到着日の目安は次の通りです。
関東・近畿・中部・四国・中国・九州地方…翌日
北海道・沖縄・東北地方…翌々日
離島や山間部等、一部地域ではさらに数日かかる場合もございます。

＜クリックポストorレターパックorゆうパケットを選択された方＞
到着日の目安は次の通りです。
近畿・中部地方…翌日～２日後
関東・四国・中国・九州地方…翌日～３日後
北海道・沖縄・東北地方…２～４日後
離島や山間部等、一部地域ではさらに数日かかる場合もございます。
ポスト取集の遅延等により、発送翌日の引受扱いとなる場合もございます。
厚さ制限等の理由でゆうパックでお送りしたり、複数便に分けて発送する場合もございます。

詳しい配達状況は日本郵便のウェブサイト等でご確認ください。なお、伝票番号(お問い合わせ番号)で配送状況を確認できるようになるまで、時間がかかる場合がございます。
上記の到着までの日数はあくまで目安であり、到着日を保障するものではございませんのでご了承ください。
@endif

注文番号: {{ $order->order_number }}
@if ($order->tracking_number)
追跡番号: {{ $order->tracking_number }}
@endif

【ご注文内容】
@foreach ($order->items as $item)
- {{ $item->product_name }}@if ($item->variant_label)（{{ $item->variant_label }}）@endif × {{ $item->quantity }}{{ config('shop.quantity_unit') }}
@endforeach

【配送先】
{{ $order->shipping_name }}
〒{{ $order->shipping_postal_code }} {{ $order->shipping_prefecture }}{{ $order->shipping_address_line1 }}@if ($order->shipping_address_line2) {{ $order->shipping_address_line2 }}@endif

================================
{{ config('shop.name') }}　{{ config('app.url') }}
================================

@extends('layouts.front')

@section('title', '特定商取引法に基づく表記 - '.config('shop.name'))

@section('head_meta')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <h1>特定商取引法に基づく表記</h1>

    <div class="panel static-content">
        <table class="data-table">
            <tr>
                <th scope="row">販売業者</th>
                <td>{{ config('shop.operator_name') }}</td>
            </tr>
            @if (config('shop.representative'))
                <tr>
                    <th scope="row">代表者</th>
                    <td>{{ config('shop.representative') }}</td>
                </tr>
            @endif
            <tr>
                <th scope="row">所在地</th>
                <td>
                    @php $addr = config('shop.address'); @endphp
                    @if (! empty($addr['postal_code']) || ! empty($addr['prefecture']))
                        @if (! empty($addr['postal_code']))〒{{ $addr['postal_code'] }} @endif
                        {{ $addr['prefecture'] ?? '' }}{{ $addr['address_line1'] ?? '' }}{{ $addr['address_line2'] ?? '' }}
                    @else
                        —
                    @endif
                </td>
            </tr>
            <tr>
                <th scope="row">連絡先</th>
                <td>
                    @if (config('shop.phone'))
                        電話: {{ config('shop.phone') }}<br>
                    @endif
                    @if (config('shop.email'))
                        メール: <a href="mailto:{{ config('shop.email') }}">{{ config('shop.email') }}</a><br>
                    @endif
                    <a href="{{ route('contacts.create') }}">お問い合わせフォーム</a>よりもご連絡いただけます。
                </td>
            </tr>
            <tr>
                <th scope="row">販売価格</th>
                <td>各商品ページに税込価格を表示しております。</td>
            </tr>
            <tr>
                <th scope="row">商品代金以外の必要料金</th>
                <td>
                    送料（配送方法により異なります。チェックアウト画面に表示されます）<br>
                    代金引換をご利用の場合、代引手数料 {{ number_format((int) config('shop.cod_fee')) }}円（税込）
                    @if (config('shop.cod_free_threshold'))
                        <br>※商品合計（クーポン適用後）が {{ number_format((int) config('shop.cod_free_threshold')) }}円以上の場合、代引手数料は無料です。
                    @endif
                </td>
            </tr>
            <tr>
                <th scope="row">支払方法</th>
                <td>{{ $paymentMethods }}</td>
            </tr>
            <tr>
                <th scope="row">支払時期</th>
                <td>
                    クレジットカード: ご注文時にお支払いが確定します。<br>
                    代金引換: 商品お届け時に配送業者へお支払いください。<br>
                    銀行振込: ご注文後7日以内にお振込みください。
                </td>
            </tr>
            <tr>
                <th scope="row">商品の引渡時期</th>
                <td>ご入金確認後（クレジットカード決済の場合は決済完了後）、通常3営業日以内に発送いたします。配送状況により到着日は異なります。</td>
            </tr>
            <tr>
                <th scope="row">返品・交換</th>
                <td>
                    お客様都合による返品・交換はお受けしておりません。<br>
                    商品に瑕疵がある場合、またはご注文内容と異なる商品が届いた場合は、商品到着後7日以内に<a href="{{ route('contacts.create') }}">お問い合わせ</a>ください。
                </td>
            </tr>
        </table>
    </div>
@endsection

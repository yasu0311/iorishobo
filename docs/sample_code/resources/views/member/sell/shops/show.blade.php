@extends('layouts.member')

@section('title', 'ショップ情報')

@section('content')

    <h1>ショップ情報</h1>
    
    <x-alert/>
    
    <div class="table-vertical-responsive th-center">
        <table>
            <tbody>
                <tr>
                    <th>ショップID</th>
                    <td>{{ $shop->shop_number }}</td>
                </tr>
                <tr>
                    <th>ショップ名</th>
                    <td>
                        <img src="{{ $shop->shop_icon_url }}" alt="ショップ画像" class="icon">
                        <span class="big bold">{{ $shop->shop_name }}</span>
                    </td>
                </tr>
                <tr>
                    <th>開店状況</th>
                    <td>{{ $shop->shop_status_text }}</td>
                </tr>
                <tr>
                    <th>販売可否</th>
                    <td>{{ $shop->shop_limited_text }}</td>
                </tr>
                @if($shop->shop_introduction)
                <tr>
                    <th>紹介文</th>
                    <td>{!! nl2br(e($shop->shop_introduction)) !!}</td>
                </tr>
                @endif
                @if($shop->shop_information)
                <tr>
                    <th>ショップ情報</th>
                    <td>{!! nl2br(e($shop->shop_information)) !!}</td>
                </tr>
                @endif
                @if($shop->url)
                <tr>
                    <th>URL</th>
                    <td>
                        <a href="{{ $shop->url }}" target="_blank">{{ $shop->url }}</a>
                    </td>
                </tr>
                @endif
                @if($shop->receipt_description)
                <tr>
                    <th>領収書明細</th>
                    <td>{!! nl2br(e($shop->receipt_description)) !!}</td>
                </tr>
                @endif
                <tr>
                    <th>消費税区分</th>
                    <td>
                        {{ $shop->getConsumptionTaxRateText() }}
                    </td>
                </tr>
                <tr>
                    <th>管理者返信可否</th>
                    <td>{{ $shop->admin_reply_text }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="center">
        <a href="{{ route('member.buy.shops.show', $shop) }}" target="_blank" rel="noopener noreferrer" class="btn btn-white">公開中ページを見る</a>
        <button type="button" class="btn btn-primary" onclick="location.href='{{ route('member.sell.shop.edit') }}'">ショップ編集</button>
        <button type="button" class="btn btn-primary" onclick="location.href='{{ route('member.sell.products.index') }}'">商品登録・変更</button>
    </div>

@endsection
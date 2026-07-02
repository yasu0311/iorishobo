@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', 'ショップ情報')

@section('content')

<h1>ショップ情報</h1>
@if(!$isShopActive)
    <div class="center">
        @if($shop->shop_status === 3)
            このショップは閉店済です。
        @elseif($shop->shop_status === 2)
            このショップは準備中です。
        @else
            現在販売停止中です。
        @endif
    </div>
@else
    <div class="table-vertical-responsive th-center">
        <table>
            <tbody>
                <tr>
                    <th>ショップ名</th>
                    <td>
                        <img src="{{ $shop->shop_icon_url }}" class="icon" alt="ショップアイコン">
                        <span class="big bold">{{ $shop->shop_name }}</span>
                    </td>
                </tr>
                @if($shop->shop_introduction)
                <tr>
                    <th>紹介文</th>
                    <td>{{ $shop->shop_introduction }}</td>
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
            </tbody>
        </table>
    </div>

    <h3>販売中の商品一覧</h3>
    @if($products->count() > 0)
        <div class="table-horizontal-responsive td-2-right td-3-right td-4-right">
            <table>
                <thead>
                    <tr>
                        <th>商品名</th>
                        <th>価格(個人利用)</th>
                        <th>価格(学校利用)</th>
                        <th>価格(商用利用)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td data-label="商品名">
                            <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
                        </td>
                        <td data-label="価格(個人利用)">
                            @if($product->price_for_personal == 0)
                                無料
                            @else
                                {{ number_format($product->price_for_personal) }}円
                            @endif
                        </td>
                        <td data-label="価格(学校利用)">
                            @if($product->price_for_school == 0)
                                無料
                            @else
                                {{ number_format($product->price_for_school) }}円
                            @endif
                        </td>
                        <td data-label="価格(商用利用)">
                            @if($product->price_for_commercial == 0)
                                無料
                            @else
                                {{ number_format($product->price_for_commercial) }}円
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>現在販売中の商品はありません。</p>
    @endif
@endif

@endsection
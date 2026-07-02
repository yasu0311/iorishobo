@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', $product->isAvailable() ? $product->product_name : '商品')

@section('content')

@if($product->isAvailable())
<h1>{{ $product->product_name }}</h1>
@else
<h1>商品</h1>
@endif
<x-alert/>

@if(!$product->isAvailable())
<div class="card card-shadow p-4 width-lg">

  <div class="center text-lg mb-2">
    @if($product->product_status === 2)
      この商品は販売終了いたしました。
    @elseif($product->product_status === 0)
      この商品は準備中です。
    @elseif($product->product_limited)
      この商品は販売停止中です。
    @elseif($product->shop && !$product->shop->available())
      この商品を販売しているショップは開店しておりません。
    @endif
  </div>
  <div class="center text-lg mb-2">
    現在、この商品の詳細の表示および購入・ダウンロードができません。
  </div>
  <div class="center">
    <a href="{{ route('member.buy.products.index') }}" class="btn btn-primary">教材をさがす</a>
  </div>
</div>
@else
<div class="product-detail table-vertical-responsive th-center">
    <table>
        <tbody>
        <tr>
            <th>商品名</th>
            <td>
            <span class="bold text-lg">{{ $product->product_name }}</span>
            </td>
        </tr>
        <tr>
            <th>価格(税込)</th>
            <td>
            個人利用：<span class="bold big">{{ $product->price_for_personal_text }}</span><br>
            学校利用：<span class="bold big">{{ $product->price_for_school_text }}</span><br>
            商用利用：<span class="bold big">{{ $product->price_for_commercial_text }}</span>
            </td>
        </tr>
        <tr>
            <th>レビュー</th>
            <td>
                @if($product->rating_average)
                <span class="review-stars">
                    <!-- --scoreの変数の分だけ★の色が変わる -->
                    <span class="gold-stars" style ="--score:{{ $product->rating_average }}">★★★★★</span>
                    <span class="gray-stars" style ="--score:{{ $product->rating_average }}">★★★★★</span>
                </span>
                ({{ number_format($product->rating_average, 1) }})
                @endif
                <a href="{{ route('member.reviews.index', $product) }}">レビュー</a>
        </tr>
        <tr>
            <th>学年</th>
            <td>{{ $product->grades->pluck('grade')->join(', ')}}</td>
        </tr>
        <tr>
            <th>科目</th>
            <td>{{ $product->subjects->pluck('subject')->join(', ')}}</td>
        </tr>
        <tr>
            <th>ファイル種類</th>
            <td>{{ $product->fileTypes->pluck('file_type_name')->join(', ')}}</td>
        </tr>
        <tr>
            <th>商品説明</th>
            <td>{!! nl2br(e($product->product_description)) !!}</td>
        </tr>
        <tr>
            <th>販売者</th>
            <td>
                <img src="{{ $product->shop->shop_icon_url }}" alt="ショップ画像" class="icon">
                <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop->shop_name }}</a>
            </td>
        </tr>
        <tr>
            <th>商品登録日</th>
            <td>{{ $product->created_at->format('Y年n月j日') }}</td>
        </tr>
        @if($product->update_information)
        <tr>
            <th>更新情報</th>
            <td>{{ $product->updated_at->format('Y年n月j日') }}: {!! nl2br(e($product->update_information)) !!}</td>
        </tr>
        @endif
        </tbody>
    </table>
    @if($product->product_image_url)
    <div class="product-img">
        <span class="text-sm">商品イメージ</span>
        <img src="{{ $product->product_image_url }}" alt="{{ $product->product_name }}">
    </div>
    @endif
</div>
<div class="right">
    @auth
        @if(auth()->user()?->member?->hasFavorite($product))
        <button onclick="location.href='{{ route('member.buy.favorites.index') }}'" class="btn btn-small btn-white">
            お気に入り登録済
        </button>
        </form>
        @else

        <form action="{{ route('member.buy.favorites.store') }}" method="post">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <button type="submit" class="btn btn-small btn-white">
                お気に入りに追加
            </button>
        </form>
        @endif
    @endauth
</div>

<div class="file-list">
・商品ファイル一覧
    @forelse($product->productFiles as $file)
    <details>
        <summary>
            <span class="summary-inner">
                <span class="file-list-icon"></span><span>{{ $file->file_name }}</span>
                @if($file->sample)
                    <span class="badge badge-green">見本</span>
                @endif
            </span>
        </summary>
        <div class="detail">
            @if($file->sample || auth()->user()?->member?->hasPurchasedProduct($product))
            <div class="center">
                <button class="btn btn-small btn-green" onclick="location.href='{{ route('member.buy.products.download', $file) }}'">ダウンロード</button>
            </div>
            @endif
            <div class="width-lg th-center table-vertical-responsive">
                <table>
                    <tbody>
                        <tr>
                        <th>ファイル名</th>
                        <td>
                            {{ $file->file_name }}
                        </td>
                        </tr>
                        <tr>
                        <th>説明</th>
                        <td>
                            {!! nl2br(e($file->file_description)) !!}
                        </td>
                        </tr>
                        <tr>
                        <th>ファイル更新日</th>
                        <td>
                            {{ $file->file_updated_at->format('Y年n月j日') }}
                        </td>
                        </tr>
                        <tr>
                        <th>拡張子</th>
                        <td>
                            {{ pathinfo($file->file_path, PATHINFO_EXTENSION) }}
                        </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>    
    </details>
    @empty
    <p>ファイルが登録されていません。</p>
    @endforelse
</div>
<div class="center">
    <button class="btn btn-white" onclick="location.href='{{ route('member.messages.index', $product) }}'">
        質問・メッセージを見る
    </button>
    <button class="btn btn-white" onclick="location.href='{{ route('member.reviews.index', $product) }}'">
        評価・レビューを見る
    </button>
</div>

@if($product->price_for_personal !== null || $product->price_for_school !== null || $product->price_for_commercial !== null)
@php
    $isOwnProduct = auth()->check() && auth()->user()?->member && (int) $product->shop->member_id === (int) auth()->user()->member->id;
@endphp

@if($isOwnProduct)
<div class="purchase-option">
    <div class="center gray text-xs">
        ご自身が出品した商品のため、購入手続きはできません。
    </div>
</div>
@else
<form action="{{ route('member.buy.checkout.create') }}" method="get">
    <input type="hidden" name="product_number" value="{{ $product->product_number }}">
    <div class="purchase-option">
        @if($product->price_for_personal === null)
        <div class="center gray text-xs">
            本商品は個人利用での購入はできません。
        </div>
        @endif
        @if($product->price_for_school === null)
        <div class="center gray text-xs">
            本商品は学校利用での購入はできません。
        </div>
        @endif
        @if($product->price_for_commercial === null)
        <div class="center gray text-xs">
            本商品は商用利用での購入はできません。
        </div>
        @endif
        <div>
            @if($product->price_for_personal !== null)
            <label>
                <input type="radio" name="usage" value="1" required>
                個人利用　{{ $product->price_for_personal_text }}
            </label><br>
            @endif
            @if($product->price_for_school !== null)
            <label>
                <input type="radio" name="usage" value="2" required>
                学校利用　{{ $product->price_for_school_text }}
            </label><br>
            @endif
            @if($product->price_for_commercial !== null)
            <label>
                <input type="radio" name="usage" value="3" required>
                商用利用　{{ $product->price_for_commercial_text }}
            </label>
            @endif
        </div>
        @if(auth()->user()?->member?->hasPurchasedProduct($product))
        <div class="red p-3 text-xs">
            すでにこの商品を購入済みです。
            上記商品ファイルをクリックし、商品ファイルをダウンロードすることができます。
            詳しくは<a href="{{ route('static.copyright-purchaser')}}" target="_blank">著作権上の注意点（購入者）</a>をご確認のうえ、追加購入の必要がある場合のみ、追加購入手続きをしてください。

        </div>

        @endif
        @guest
            <div class="center gray text-xs">
                購入手続きをするには、会員登録が必要です。
            </div>
        @endguest
        <div class="center">
            <button type="submit" class="btn btn-primary">購入手続きへ進む</button>
        </div>
    </div>
</form>
@endif
@endif
</div>
@endif

@endsection
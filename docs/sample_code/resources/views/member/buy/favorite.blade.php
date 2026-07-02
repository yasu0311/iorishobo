@extends('layouts.member')

@section('title', 'お気に入り教材')

@section('content')
<h1>お気に入り教材</h1>

<x-alert/>

<div class="width-xl td-1-center td-4-right td-5-right td-6-right td-7-right text-sm table-horizontal-responsive">
  <table>
    <thead>
      <tr>
        <th>削除</th>
        <th>商品名</th>
        <th>販売者</th>
        <th>購入済数</th>
        <th>個人利用<br>価格</th>
        <th>学校利用<br>価格</th>
        <th>商用利用<br>価格</th>
      </tr>
    </thead>
    <tbody>
      @forelse($favorites as $favorite)
        @php
          $product = $favorite->product;
          $shop = $product->shop ?? null;
        @endphp
        @if($product)
          <tr>
            <td data-label="削除">
              <form action="{{ route('member.buy.favorites.destroy', $favorite) }}" method="POST" style="display: inline;" onsubmit="return confirm('お気に入りから「{{ $product->product_name }}」を削除してもよろしいですか？');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-small btn-white">×</button>
              </form>
            </td>
            <td data-label="商品名">
              <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
            </td>
            <td data-label="販売者">
              <img src="{{ $product->shop->shop_icon_url }}" alt="ショップ画像" class="icon">
              {{ $product->shop->shop_name }}
            </td>
            <td data-label="購入済数">{{ $favorite->purchased_count ?? 0 }}</td>
            <td data-label="個人利用価格">{{ $product->price_for_personal_text }}</td>
            <td data-label="学校利用価格">{{ $product->price_for_school_text }}</td>
            <td data-label="商用利用価格">{{ $product->price_for_commercial_text }}</td>
          </tr>
        @endif
      @empty
        <tr>
          <td colspan="7" class="center">お気に入りに登録されている商品はありません。</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
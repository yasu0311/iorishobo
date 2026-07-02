@extends('layouts.member')

@section('title', '商品一覧')

@section('content')


<h1>商品一覧</h1>
<x-alert/>

@if(isset($totalCapacityMb))
<p class="text-sm gray">
  アップロードファイル容量：{{ number_format($usedCapacityMb) }}MB / {{ number_format($totalCapacityMb) }}MB
</p>
@endif

@if($shop->shop_status != 1)
<div class="alert alert-warning">
  現在、ショップは{{ $shop->shop_status_text }}のため、すべての商品の販売は停止中です。ショップを開店中とするためには<a href="{{ route('member.sell.shop.edit') }}">ショップ設定</a>から設定してください。
</div>
@endif

<h2>
  販売中商品
</h2>

<div class="table-horizontal-responsive td-1-center td-3-center td-4-right td-5-right td-6-right">
  <table>
    <thead>
      <tr>
        <th>処理</th>
        <th>商品名</th>
        <th>状態</th>
        <th>個人利用<br>価格</th>
        <th>学校利用<br>価格</th>
        <th>商用利用<br>価格</th>
      </tr>
    </thead>
    <tbody>
      @forelse($sellingProducts as $product)
      <tr>
        <td data-label="処理">
          <button class="btn btn-small btn-primary" onclick="window.location.href='{{ route('member.sell.products.edit', $product) }}'">編集</button>
          @if($product->orders_count == 0 && $product->messages_count == 0)
          <form action="{{ route('member.sell.products.destroy', $product) }}" method="POST" style="display: inline;" data-product-name="{{ e($product->product_name) }}" onsubmit="return confirmProductDelete(this);">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-small btn-red">削除</button>
          </form>
          @endif
        </td>
        <td data-label="商品名">
          {{ $product->product_name }}
        </td>
        <td data-label="状態">
          {{ $product->product_status_text }}
          @if($product->product_limited == 1)
          <br><span class="red">販売不可</span>
          @endif
        </td>
        <td data-label="価格(個人利用)">{{ $product->price_for_personal_text }}</td>
        <td data-label="価格(学校利用)">{{ $product->price_for_school_text }}</td>
        <td data-label="価格(商用利用)">{{ $product->price_for_commercial_text }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="6" class="text-center">販売中の商品はありません</td>
      </tr>
      @endforelse
    </tbody>
  </table>
  <p class="p-2 text-sm gray">
    商品の注文がある場合や、商品に対する質問・メッセージがある場合は、その商品を削除をすることができません。
    その場合は、削除ボタンではなく「編集」ボタンを押し、「状態」を「販売終了」に変更してください。
  </p>
</div>


<h2>
  準備中・販売終了・販売停止中の商品
</h2>


  <form action="{{ route('member.sell.products.store') }}" method="post">
    @csrf
    <div class="center card">
      <label for="product_name">商品名：</label>
      <input type="text" id="product_name" name="product_name" maxlength="20" value="{{ old('product_name') }}">
      <span class="text-sm gray">20文字以内</span>
    <button type="submit" class="btn btn-primary">商品新規登録</button>
    </div>
  </form>

<div class="table-horizontal-responsive td-1-center td-3-center td-4-right td-5-right td-6-right">
  <table>
    <thead>
      <tr>
        <th>処理</th>
        <th>商品名</th>
        <th>状態</th>
        <th>個人利用<br>価格</th>
        <th>学校利用<br>価格</th>
        <th>商用利用<br>価格</th>
      </tr>
    </thead>
    <tbody>
      @forelse($otherProducts as $product)
      <tr>
        <td data-label="処理">
          <button class="btn btn-small btn-primary" onclick="window.location.href='{{ route('member.sell.products.edit', $product) }}'">編集</button>
          @if($product->orders_count == 0 && $product->messages_count == 0)
          <form action="{{ route('member.sell.products.destroy', $product) }}" method="POST" style="display: inline;" data-product-name="{{ e($product->product_name) }}" onsubmit="return confirmProductDelete(this);">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-small btn-red">削除</button>
          </form>
          @endif
        </td>
        <td data-label="商品名">
          {{ $product->product_name }}
        </td>
        <td data-label="状態">
          {{ $product->product_status_text }}
          @if($product->product_limited == 1)
          <br><span class="red">販売不可</span>
          @endif
        </td>
        <td data-label="価格(個人利用)">{{ $product->price_for_personal_text }}</td>
        <td data-label="価格(学校利用)">{{ $product->price_for_school_text }}</td>
        <td data-label="価格(商用利用)">{{ $product->price_for_commercial_text }}</td>
      </tr>
      @empty
      <tr>
        <td colspan="6" class="text-center">準備中・販売終了・販売停止中の商品はありません</td>
      </tr>
      @endforelse
    </tbody>
  </table>
  <p class="p-2 text-sm gray">
    商品の注文がある場合や、商品に対する質問・メッセージがある場合は、その商品を削除をすることができません。
    その場合は、削除ボタンではなく「編集」ボタンを押し、「状態」を「販売終了」に変更してください。
  </p>
</div>

<script>
function confirmProductDelete(form) {
  var name = form.getAttribute('data-product-name');
  if (!name) return confirm('この商品を削除してもよろしいですか？');
  var escaped = name.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\r/g, '\\r').replace(/\n/g, '\\n');
  return confirm(escaped + 'を削除してもよろしいですか？');
}
</script>

@endsection
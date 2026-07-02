@extends('layouts.member')

@section('title', '入手済み教材')

@section('content')
<h1>入手済み教材</h1>
      <div class="filtering sales">
        <form action="{{ route('member.buy.orders.index') }}" method="GET">
          <div class="flex">
            <div class="filtering-column">
              <label>商品名：
                <input name="product_name" type="text" value="{{ request('product_name') }}">
              </label>
            </div>
            <div class="filtering-column">
              <label for="shop">販売者：</label>
              <input name="shop" id="shop" type="text" value="{{ request('shop') }}">
            </div>
            <div class="filtering-column">
              <label>注文番号：
                <input name="order_id" type="text" inputmode="numeric" value="{{ request('order_id') }}">
              </label>
            </div>
            <div class="filtering-column">
              利用法：
              <select name="usage">
                @foreach($options['usage'] as $value => $label)
                  <option value="{{ $value }}" @selected((string)request('usage', \App\Filters\Member\Buy\OrderFilter::DEFAULT_USAGE) === (string)$value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="filtering-column">
              <label for="grade">学年：</label>
              <select name="grade" id="grade">
                <option value="">すべて</option>
                @foreach($options['grade'] as $value => $label)
                  <option value="{{ $value }}" @selected(request('grade') === (string) $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="filtering-column">
              <label for="subject">教科：</label>
              <select name="subject" id="subject">
                <option value="">すべて</option>
                @foreach($options['subject'] as $value => $label)
                  <option value="{{ $value }}" @selected(request('subject') === (string) $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="filtering-column">
              <label for="per_page">表示件数：</label>
              <select name="per_page" id="per_page">
                @foreach($options['per_page'] as $value => $label)
                  <option value="{{ $value }}" @selected((string) request('per_page', (string) \App\Filters\Member\Buy\OrderFilter::DEFAULT_PER_PAGE) === (string) $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="filtering-column">
              <label for="sort">表示順：</label>
              <select name="sort" id="sort">
                @foreach($options['sort'] as $value => $label)
                  <option value="{{ $value }}" @selected(request('sort', \App\Filters\Member\Buy\OrderFilter::DEFAULT_SORT) === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="center">
            <button type="submit" class="btn btn-primary">検索</button>
          </div>
        </form>
      </div>
      @php
        $conditions = [];
        if (filled(request('product_name'))) {
            $conditions[] = '【商品名】' . e(request('product_name'));
        }
        if (filled(request('shop'))) {
            $conditions[] = '【販売者】' . e(request('shop'));
        }
        if (filled(request('order_id'))) {
            $conditions[] = '【注文番号】' . e(request('order_id'));
        }
        if (filled(request('usage')) && isset($options['usage'][request('usage')])) {
            $conditions[] = '【利用法】' . e($options['usage'][request('usage')]);
        }
        if (filled(request('grade')) && isset($options['grade'][request('grade')])) {
            $conditions[] = '【学年】' . e($options['grade'][request('grade')]);
        }
        if (filled(request('subject')) && isset($options['subject'][request('subject')])) {
            $conditions[] = '【教科】' . e($options['subject'][request('subject')]);
        }
        $sortKey = request('sort', \App\Filters\Member\Buy\OrderFilter::DEFAULT_SORT);
        $conditions[] = '【表示順】' . e($options['sort'][$sortKey] ?? '');
      @endphp
      <div class="text-sm gray center">
        現在の条件：{!! $conditions ? implode('　', $conditions) : 'すべて' !!}
      </div>
      <div class="table-horizontal-responsive td-1-center td-3-center td-4-center td-5-center">
        <table>
          <thead>
            <tr>
              <th>注文番号</th>
              <th>商品名</th>
              <th>注文日</th>
              <th>ステータス</th>
              <th>利用</th>
              <th>評価</th>
            </tr>
          </thead>
          <tbody>
            @forelse($orders as $order)
              <tr>
                <td data-label="注文番号">
                  <a href="{{ route('member.buy.orders.show', $order) }}" target="_blank">{{ $order->order_number }}</a>
                </td>
                <td data-label="商品名">
                  @if($order->product)
                    @if($order->product->isAvailable())
                      <a href="{{ route('member.buy.products.show', $order->product) }}">{{ $order->product->product_name }}</a>
                    @else
                      <a href="{{ route('member.buy.orders.show', $order) }}">{{ $order->product->product_name }}</a>
                      <span class="text-xs gray">（販売終了のため注文詳細からダウンロード）</span>
                    @endif
                  @else
                    <span>-</span>
                  @endif
                </td>
                <td data-label="注文日">
                  {{ optional($order->ordered_at ?? $order->created_at)->format('Y/m/d') }}
                </td>
                <td data-label="ステータス">
                  {{ $order->status_label }}
                </td>
                <td data-label="利用">
                  {{ $order->usage_text ?? '-' }}
                </td>
                <td data-label="評価">
                  @if($order->status !== 'completed')
                    確定後に評価できます
                  @elseif($order->reviews->isNotEmpty())
                    評価済
                  @elseif($order->product)
                    <a href="{{ route('member.reviews.create', ['product' => $order->product_id]) }}" class="btn btn-small btn-primary">評価</a>
                  @else
                    <span>-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="center">該当する注文はありません。</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
        <x-pagination :paginator="$orders" />
@endsection
@extends('layouts.member')

@section('title', '個別売上')

@section('content')

<h1>売上</h1>
<div class="center flex justify-between width-sm">
  <div><a href="{{ route('member.sell.sales.index') }}">個別売上</a></div>
  <div><a href="{{ route('member.sell.sales.summary-monthly') }}">売上集計(月別)</a></div>
  <div><a href="{{ route('member.sell.sales.summary-product') }}">売上集計(商品別)</a></div>
</div>

  <h3>個別売上</h3>
  <div class="filtering">
    <form method="get" action="{{ route('member.sell.sales.index') }}">
      <div class="flex">
        <div class="filtering-column">
          利用法：
          @foreach($options['usage'] as $value => $label)
            <label>
              <input
                type="radio"
                name="usage"
                value="{{ $value }}"
                @checked((string)request('usage', '') === (string)$value)
              >{{ $label }}
            </label>
          @endforeach
        </div>
        <div class="filtering-column">
          <label for="order_id">注文番号：</label>
          <input name="order_id" id="order_id" type="text" inputmode="numeric" value="{{ request('order_id') }}"></input>
        </div>
        <div class="filtering-column">
          <label for="customer">注文者：</label>
          <input name="customer" id="customer" type="text" value="{{ request('customer') }}"></input>
        </div>
        <div class="filtering-column">
          注文日範囲：
          <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}">～            
          <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}">
        </div>

        <div class="filtering-column">
          <label for="display_count">表示件数：</label>
          <select name="display_count" id="display_count">
            @foreach($options['display_count'] as $value => $label)
              <option value="{{ $value }}" @selected((int)request('display_count', 10) === (int)$value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>


        <div class="filtering-column">
          <label for="sort_order">表示順：</label>
          <select name="sort_order" id="sort_order">
            @foreach($options['sort_order'] as $value => $label)
              <option value="{{ $value }}" @selected(request('sort_order', 'new') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="center">
        <button type="submit" class="btn btn-primary">検索</button>
      </div>
    </form>
  </div>
  <div class="center text-sm gray">
    現在の条件：
    【利用法】{{ $options['usage'][request('usage', '')] ?? 'すべて' }}
    【表示順】{{ $options['sort_order'][request('sort_order', 'new')] ?? '' }}
  </div>
  <div class="table-horizontal-responsive td-1-center td-3-center td-4-right td-5-center td-6-center">
    <table>
      <thead>
        <tr>
          <th>注文番号</th>
          <th>商品名</th>
          <th>注文日</th>
          <th>金額</th>
          <th>利用</th>
          <th>注文者</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sales as $sale)
          <tr>
            <td data-label="注文番号"><a href="{{ route('member.sell.sales.show', $sale) }}" target="_blank" rel="noopener noreferrer">{{ $sale->order_number }}</a></td>
            <td data-label="商品名">@if($sale->product)<a href="{{ route('member.buy.products.show', $sale->product) }}">{{ $sale->product->product_name }}</a>@else{{ optional($sale->product)->product_name }}@endif</td>
            <td data-label="注文日">{{ optional($sale->ordered_at)->format('Y/m/d') }}</td>
            <td data-label="金額">{{ number_format($sale->amount_paid ?? $sale->total_amount) }}円</td>
            <td data-label="利用">{{ $sale->usage_text }}</td>
            <td data-label="注文者">@if($sale->member)<a href="{{ route('member.members.show', $sale->member) }}">{{ $sale->member->nickname }}</a>@else{{ optional($sale->member)->nickname }}@endif</td>
          </tr>
        @empty
          <tr><td colspan="6" class="center">該当する売上はありません。</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <x-pagination :paginator="$sales" />

@endsection
@extends('layouts.member')

@section('title', '売上集計(商品別)')

@section('content')

<h1>売上</h1>
<div class="center flex justify-between width-sm">
  <div><a href="{{ route('member.sell.sales.index') }}">個別売上</a></div>
  <div><a href="{{ route('member.sell.sales.summary-monthly') }}">売上集計(月別)</a></div>
  <div><a href="{{ route('member.sell.sales.summary-product') }}">売上集計(商品別)</a></div>
</div>
<h3>売上集計(商品別)</h3>
<div class="width-md td-2-right td-3-right">
  <div class="right">
    <form method="get" action="{{ route('member.sell.sales.summary-product') }}" id="yearForm">
      <select name="year" id="year" onchange="document.getElementById('yearForm').submit();">
        <option value="" @selected(empty($selectedYear))>すべて</option>
        @if(isset($availableYears) && count($availableYears) > 0)
          @foreach($availableYears as $availableYear)
            <option value="{{ $availableYear }}" @selected(!empty($selectedYear) && (int)$selectedYear === (int)$availableYear)>{{ $availableYear }}年売上</option>
          @endforeach
        @endif
      </select>
    </form>
  </div>
  <table>
    <thead>
      <tr>
        <th>商品名</th>
        <th>件数</th>
        <th>売上金額</th>
      </tr>
    </thead>
    <tbody>
      @if(isset($productData) && count($productData) > 0)
        @foreach($productData as $row)
          <tr>
            <td>{{ $row['product_name'] }}</td>
            <td>{{ $row['count'] }}件</td>
            <td>{{ number_format($row['amount']) }}円</td>
          </tr>
        @endforeach
        <tr>
          <td class="total-sales">合計</td>
          <td>{{ $totalCount ?? 0 }}件</td>
          <td>{{ number_format($totalAmount ?? 0) }}円</td>
        </tr>
      @else
        <tr>
          <td colspan="3" class="center">該当する売上データがありません。</td>
        </tr>
      @endif
    </tbody>
  </table>
</div>

@endsection
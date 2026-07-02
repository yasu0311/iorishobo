@extends('layouts.member')

@section('title', '売上集計(月別)')

@section('content')
<h1>売上</h1>
<div class="center flex justify-between width-sm">
  <div><a href="{{ route('member.sell.sales.index') }}">個別売上</a></div>
  <div><a href="{{ route('member.sell.sales.summary-monthly') }}">売上集計(月別)</a></div>
  <div><a href="{{ route('member.sell.sales.summary-product') }}">売上集計(商品別)</a></div>
</div>
<h3>売上集計(月別)</h3>
<div class="width-md td-right">
  <div class="right">
    <form method="get" action="{{ route('member.sell.sales.summary-monthly') }}" id="yearForm">
      <select name="year" id="year" onchange="document.getElementById('yearForm').submit();">
        @if(isset($availableYears) && count($availableYears) > 0)
          @foreach($availableYears as $availableYear)
            <option value="{{ $availableYear }}" @selected($year == $availableYear)>{{ $availableYear }}年売上</option>
          @endforeach
        @else
          <option value="{{ date('Y') }}">{{ date('Y') }}年売上</option>
        @endif
      </select>
    </form>
  </div>
  <table>
    <thead>
      <tr>
        <th>月</th>
        <th>件数</th>
        <th>売上金額</th>
      </tr>
    </thead>
    <tbody>
      @if(isset($monthlyData) && count($monthlyData) > 0)
        @foreach($monthlyData as $data)
          <tr>
            <td>{{ $year }}年{{ $data['month'] }}月</td>
            <td>{{ $data['count'] }}件</td>
            <td>{{ number_format($data['amount']) }}円</td>
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
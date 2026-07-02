@extends('layouts.member')

@section('title', '通帳')

@section('content')
<h1>通帳</h1>

<div class="card bold big center width-sm">
  現在の残高：{{ $currentBalance }}円
</div>

@if($transactions->isEmpty())
  <p class="center">取引履歴はまだありません。</p>
@else
  <div class="width-lg td-3-right td-4-right td-5-right td-6-center text-sm table-horizontal-responsive">
    <div class="right">
      表示件数：
      <form method="get">
        <select name="count" onchange="this.form.submit()">
          @foreach([10, 20, 50, 100] as $num)
            <option value="{{ $num }}" {{ request('count', 10) == $num ? 'selected' : '' }}>{{ $num }}件</option>
          @endforeach
        </select>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>日付</th>
          <th>取引内容</th>
          <th>入金額</th>
          <th>出金額</th>
          <th>残高</th>
          <th>備考</th>
        </tr>
      </thead>
      <tbody>
        @foreach($transactions as $t)
          <tr>
            <td data-label="日付">{{ ($t['date'])->format('Y年n月j日') }}</td>
            <td data-label="取引内容">{{ $t['type'] }}</td>
            <td data-label="入金額">{{ $t['deposit'] ? number_format($t['deposit']).'円' : '-' }}</td>
            <td data-label="出金額">{{ $t['withdraw'] ? number_format($t['withdraw']).'円' : '-' }}</td>
            <td data-label="残高">{{ number_format($t['balance']) }}円</td>
            <td data-label="備考">{{ $t['remark'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <x-pagination :paginator="$transactions" />
  </div>
@endif
@endsection
@extends('layouts.mail-html')

@section('content')
<h1>出金依頼</h1>
<div class="width-md table-vertical-responsive">
  <table>
    <tbody>
      <tr>
        <th>出金額</th>
        <td>{{ number_format($withdrawal->amount) }}円</td>
      </tr>
      <tr>
        <th>出金手数料</th>
        <td>{{ number_format($withdrawal->withdrawal_fee) }}円</td>
      </tr>
      <tr>
        <th>振込金額</th>
        <td>{{ number_format($withdrawal->amount - $withdrawal->withdrawal_fee) }}円</td>
      </tr>
      @if($withdrawal->comment)
      <tr>
        <th>コメント</th>
        <td>{{ $withdrawal->comment }}</td>
      </tr>
      @endif
    </tbody>
  </table>
</div>
@endsection

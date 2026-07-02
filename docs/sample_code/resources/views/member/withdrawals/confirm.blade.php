@extends('layouts.member')

@section('title', '出金依頼確認')

@section('content')
<h1>出金依頼確認</h1>

<x-alert/>
  <form action="{{ route('member.withdrawals.store') }}" method="POST">
    @csrf
    {{-- Hidden inputs で全データを送信 --}}
    <input type="hidden" name="amount" value="{{ $withdrawal_data['amount'] }}">
    <input type="hidden" name="bank_name" value="{{ $withdrawal_data['bank_name'] }}">
    <input type="hidden" name="branch_name" value="{{ $withdrawal_data['branch_name'] }}">
    <input type="hidden" name="account_type" value="{{ $withdrawal_data['account_type'] }}">
    <input type="hidden" name="account_number" value="{{ $withdrawal_data['account_number'] }}">
    <input type="hidden" name="account_holder" value="{{ $withdrawal_data['account_holder'] }}">
    <input type="hidden" name="mobile_phone" value="{{ $withdrawal_data['mobile_phone'] }}">
    @if (isset($withdrawal_data['comment']))
    <input type="hidden" name="comment" value="{{ $withdrawal_data['comment'] }}">
    @endif
    
    <div class="width-md table-vertical-responsive">
      <table>
        <tr>
          <th>金融機関名</th>
          <td>
            {{ $withdrawal_data['bank_name'] }}
          </td>
        </tr>
        <tr>
          <th>支店名</th>
          <td>
            {{ $withdrawal_data['branch_name'] }}
          </td>
        </tr>
        <tr>
          <th>口座種別</th>
          <td>
            {{ \App\Models\Withdrawal::getAccountTypeText($withdrawal_data['account_type']) }}
          </td>
        </tr>
        <tr>
          <th>口座番号</th>
          <td>
            {{ $withdrawal_data['account_number'] }}
          </td>
        </tr>
        <tr>
          <th>口座名義人</th>
          <td>
            {{ $withdrawal_data['account_holder'] }}
          </td>
        </tr>
        <tr>
          <th>携帯電話番号</th>
          <td>
            <div>{{ $withdrawal_data['mobile_phone'] }}</div>
          </td>
        </tr>
        <tr>
          <th>出金額</th>
          <td>
            {{ number_format($withdrawal_data['amount']) }}円
          </td>
        </tr>
        <tr>
          <th>出金手数料</th>
          <td>
            {{ number_format($withdrawal_fee) }}円
        </td>
        </tr>
        <tr>
          <th>振込金額</th>
          <td>
            {{ number_format($withdrawal_data['amount'] - $withdrawal_fee) }}円
          </td>
        </tr>
        @if (isset($withdrawal_data['comment']) && $withdrawal_data['comment'])
        <tr>
          <th>コメント</th>
          <td>
            {{ $withdrawal_data['comment'] }}
          </td>
        </tr>
        @endif
      </table>
      
      <div class="center">
        <button type="button" class="btn btn-white" onclick="history.back()">
          戻る
        </button>
        <button type="submit" class="btn btn-primary">
          出金依頼を行う
        </button>
      </div>
    </div>
  </form>
@endsection
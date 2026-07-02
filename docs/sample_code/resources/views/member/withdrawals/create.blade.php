@extends('layouts.member')

@section('title', '出金依頼')

@section('content')
<h1>出金依頼</h1>
@if (!$canWithdraw)
  <p class="center">現在、お引き出しできる金額がありません。出金依頼はお手続きいただけません。</p>
@else
@if ($errors->any())
  <div class="alert alert-danger">
    <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form action="{{ route('member.withdrawals.confirm') }}" method="POST">
  @csrf
  <div class="width-md table-vertical-responsive">
    <table>
      <tbody>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>出金額
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>
                出金可能額の範囲でご指定ください。有効期限内の残高のみ出金可能です（発生日から{{ $balanceExpiryMonths }}か月）。
                振込手数料を差し引いた金額が振り込まれます。振込手数料は200円(3万円以上引き出しの場合は無料)です。
              </span>
            </span>
          </th>
          <td>
            <label>
              <input type="checkbox" onchange="inputValue(this, 'withdrawal-amount', {{ $withdrawableAmount }})">
              出金可能額全額</label><br>
            <input type="text" id="withdrawal-amount" name="amount" class="wd-5 amount-input" value="{{ old('amount') }}" required autofocus>円<br>
            出金可能額は<span class="bold text-lg">{{ number_format($withdrawableAmount) }}円</span>です。<br>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>金融機関名
          </th>
          <td>
            <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="三井住友銀行" required><br>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>支店名
          </th>
          <td>
            <input type="text" name="branch_name" value="{{ old('branch_name') }}" placeholder="新宿支店" required><br>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>口座種別
          </th>
          <td>
            <label><input type="radio" name="account_type" value="1" {{ old('account_type', '1') == '1' ? 'checked' : '' }} required>普通</label>
            <label><input type="radio" name="account_type" value="2" {{ old('account_type') == '2' ? 'checked' : '' }} required>当座</label>
            <label><input type="radio" name="account_type" value="3" {{ old('account_type') == '3' ? 'checked' : '' }} required>貯蓄</label>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>口座番号
          </th>
          <td>
            <input type="text" name="account_number" maxlength="7" value="{{ old('account_number') }}" class="wd-5" required>
            <span class="text-sm gray">７桁半角数字</span>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>口座名義人(カナ)
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>振込先の口座名義は、ご登録の会員名（氏名または法人名）と同一である必要があります。カタカナでご入力ください。</span>
            </span>
          </th>
          <td>
            <input type="text" id="account_holder" name="account_holder" value="{{ old('account_holder') }}" placeholder="ヤマダ　タロウ" required><br>
            <span class="text-sm gray">登録した会員名と同じものに限ります。</span>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>携帯電話番号
          </th>
          <td>
            <input type="tel" name="mobile_phone" class="wd-8" value="{{ old('mobile_phone') }}" placeholder="0900000000" required><br>
            <span class="text-sm gray">ハイフンなしの半角数字で入力してください。</span>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-gray">任意</span>コメント
          </th>
          <td>
            <textarea name="comment" rows="4">{{ old('comment') }}</textarea>
          </td>
        </tr>
      </tbody>
    </table>
    <div class="center">
      <button type="submit" class="btn btn-primary">出金依頼確認</button>
    </div>
  </div>
</form>
@endif
@endsection
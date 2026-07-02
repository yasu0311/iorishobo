@extends('layouts.member')

@section('title', '登録情報詳細')

@section('content')

<h1>登録情報詳細</h1>
<x-alert/>




<div class="width-md table-vertical-responsive">
  <table>
    <tbody>
      <tr>
        <th>
          公開名
        </th>
        <td>
          <span class="badge badge-green">公開</span>
          {{ $member->nickname }}
        </td>
      </tr>
      <tr>
        <th>アイコン</th>
        <td>
          @if(!empty($member->member_icon))
          <div><span class="badge badge-green">公開</span><span>現在のアイコン：</span><img src="{{ asset('storage/'.$member->member_icon) }}" class="icon"></div>
          @else
          <div><span>現在のアイコン：</span><span class="gray">未設定</span></div>
          @endif
        </td>
      </tr>

      <tr>
        <th>
          法人・個人
        </th>
        <td>
          {{ $member->company_text }}
        </td>
      </tr>
      <tr>
        <th>氏名</th>
        <td>
          {{ $member->last_name }}　{{ $member->first_name }}
        </td>
      </tr>
      <tr>
        <th>氏名(カナ)</th>
        <td>
          {{ $member->last_name_kana }}　{{ $member->first_name_kana }}
        </td>
      </tr>
      <tr>
        <th>住所</th>
        <td>
          〒{{ $member->postal_code }}<br>
          {{ $member->address_prefecture }}
          {{ $member->address_city }}
          {{ $member->address_block }}
          {{ $member->address_building }}
        </td>
      </tr>
      <tr>
        <th>
          電話番号
        </th>
        <td>
          {{ $member->phone_number }}
        </td>
      </tr>
      @if($member->company == 1)
      <tr>
        <th>法人名</th>
        <td>
          {{ $member->company_name ?? '—' }}
        </td>
      </tr>
      <tr>
        <th>法人名（カナ）</th>
        <td>
          {{ $member->company_name_kana ?? '—' }}
        </td>
      </tr>
      <tr>
        <th>本店の所在地</th>
        <td>
          〒{{ $member->company_postal_code }}<br>
          {{ $member->company_prefecture }}
          {{ $member->company_city }}
          {{ $member->company_block }}
          {{ $member->company_building }}
        </td>
      </tr>
      @endif
      <tr>
        <th>メール通知設定</th>
        <td>
          <span class="small grat">(メッセージ・質問・レビュー・返信が投稿されたとき)<br>
          {{ $member->message_notification_text }}
        </td>
      </tr>
    </tbody>
  </table>
  <div class="center">
    <button onclick="window.location.href='{{ route('member.profile.edit') }}'" class="btn btn-primary">編集</button>
  </div>
</div>

@endsection
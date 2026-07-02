@extends('layouts.member')

@section('title', '登録情報変更')

@section('content')
<h1>登録情報変更</h1>
<div class="center gray text-sm">必要に応じてご変更ください。</div>
<x-alert/>

@if(!$member)
<p>登録情報を変更するには、ログインしてください。</p>
@else
<form action="{{ route('member.profile.update') }}" method="POST" enctype="multipart/form-data">
  <div class="width-md table-vertical-responsive">
  @csrf
  @method('PUT')
  @php
  $prefectures = [
    "北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県",
    "茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県",
    "新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県",
    "三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県",
    "鳥取県","島根県","岡山県","広島県","山口県",
    "徳島県","香川県","愛媛県","高知県",
    "福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県",
    "日本国外"
  ];
  @endphp
    <table>
      <tbody>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>公開名
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>他のユーザーに表示される名前です。ニックネームや屋号をご入力ください。</span>
            </span>
          </th>
          <td>
            <span class="badge badge-green">公開</span>
            <input type="text" name="nickname" class="wd-10" value="{{ old('nickname', $member->nickname) }}" maxlength="15">
            <span class="gray text-sm">15文字以内</span>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-gray">任意</span>アイコン
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>プロフィールに表示される画像です。他のユーザーに公開されます。</span>
            </span>
          </th>
          <td>
            <div><span>現在のアイコン：</span>
              @if($member->member_icon_url)
                <img src="{{ $member->member_icon_url }}" class="icon" id="current-member-icon">
              @else
                <span class="gray">未設定</span>
              @endif
            </div>            
            <div id="member-icon-preview"></div>              
            <span class="badge badge-green">公開</span>
            <input type="file" name="member_icon" accept=".jpg, .jpeg, .png, .gif, .bmp, .svg, .webp" onChange="imgPreView(event,'member-icon-preview','member-icon-preview-img')">
            <div class="gray text-sm">対応形式：jpeg, jpg, png, gif, bmp, svg, webp</div>
          </td>
        </tr>

        <tr>
          <th>法人・個人</th>
          <td>
            {{ $member->company_text }}
            <input type="hidden" name="company" value="{{ (int)$member->company }}">
          </td>
        </tr>
        @if($member->company == 1)
        <tr>
          <th>法人名</th>
          <td>
            {{ $member->company_name }}
            <input type="hidden" name="company_name" value="{{ $member->company_name }}">
          </td>
        </tr>
        <tr>
          <th>法人名(カナ)</th>
          <td>
            {{ $member->company_name_kana ?? '—' }}
            <input type="hidden" name="company_name_kana" value="{{ $member->company_name_kana }}">
          </td>
        </tr>
        <tr>
          <th><span class="badge badge-red">必須</span>本店の所在地</th>
          <td>
            〒<input type="text" name="company_postal_code" class="wd-4" value="{{ old('company_postal_code', $member->company_postal_code) }}" placeholder="1234567" pattern="[0-9]*" inputmode="numeric" maxlength="7">
            <span class="gray text-sm">7桁</span><br>
            都道府県：
            <select name="company_prefecture">
              <option value=""></option>

              @foreach($prefectures as $prefecture)
                <option value="{{ $prefecture }}"
                  {{ old('company_prefecture', $member->company_prefecture) == $prefecture ? 'selected' : '' }}>
                  {{ $prefecture }}
                </option>
              @endforeach
            </select><br>
            市区町村：<input type="text" name="company_city" class="wd-15" value="{{ old('company_city', $member->company_city) }}" placeholder="〇〇市〇〇区"><br>
            番地：<input type="text" name="company_block" class="wd-15" value="{{ old('company_block', $member->company_block) }}" placeholder="１－２－３"><br>
            建物：<input type="text" name="company_building" class="wd-15" value="{{ old('company_building', $member->company_building) }}" placeholder="〇〇ビル101号室"><br>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-gray">任意</span>法人の電話番号
          </th>
          <td>
            <input type="tel" name="company_phone_number" class="wd-8" value="{{ old('company_phone_number', $member->company_phone_number) }}" placeholder="03-1234-5678">
          </td>
        </tr>
        <tr>
          <td colspan="2">以下、法人の代表者についてご入力ください。</td>
        </tr>
        @endif
        <tr>
          <th>氏名</th>
          <td>
            {{ $member->last_name }} {{ $member->first_name }}
            <input type="hidden" name="last_name" value="{{ $member->last_name }}">
            <input type="hidden" name="first_name" value="{{ $member->first_name }}">
          </td>
        </tr>
        <tr>
          <th>氏名(カナ)</th>
          <td>
            {{ $member->last_name_kana }} {{ $member->first_name_kana }}
            <input type="hidden" name="last_name_kana" value="{{ $member->last_name_kana }}">
            <input type="hidden" name="first_name_kana" value="{{ $member->first_name_kana }}">
          </td>
        </tr>
        <tr>
          <th><span class="badge badge-red">必須</span>住所</th>
          <td>
            〒<input type="text" name="postal_code" class="wd-4" value="{{ old('postal_code', $member->postal_code) }}" placeholder="1234567" pattern="[0-9]*" inputmode="numeric" maxlength="7">
            <span class="gray text-sm">7桁</span><br>
            都道府県：
            <select name="address_prefecture">
              <option value=""></option>
              @foreach($prefectures as $prefecture)
                <option value="{{ $prefecture }}"
                  {{ old('address_prefecture', $member->address_prefecture) == $prefecture ? 'selected' : '' }}>
                  {{ $prefecture }}
                </option>
              @endforeach
            </select><br>
            市区町村：<input type="text" name="address_city" class="wd-15" value="{{ old('address_city', $member->address_city) }}" placeholder="〇〇市〇〇区"><br>
            番地：<input type="text" name="address_block" class="wd-15" value="{{ old('address_block', $member->address_block) }}" placeholder="１－２－３"><br>
            建物：<input type="text" name="address_building" class="wd-15" value="{{ old('address_building', $member->address_building) }}" placeholder="〇〇ビル101号室"><br>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>電話番号
          </th>
          <td>
            <input type="tel" name="phone_number" class="wd-8" value="{{ old('phone_number', $member->phone_number) }}" placeholder="03-1234-5678">
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>メール通知設定
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>取引相手からのメッセージや、商品への質問・レビュー・返信が届いたときにメールでお知らせするかどうかを選択します。</span>
            </span>
          </th>
          <td>
            メッセージ・質問・レビュー・返信が投稿されたとき<br>
            <label>
              <input type="radio" name="message_notification" value="1" {{ old('message_notification', $member->message_notification ?? 1) == 1 ? 'checked' : '' }}> メール通知を受け取る
            </label>
            <label>
              <input type="radio" name="message_notification" value="0" {{ old('message_notification', $member->message_notification ?? 1) == 0 ? 'checked' : '' }}> メール通知を受け取らない
            </label>
          </td>
        </tr>
      </tbody>
    </table>
    <div class="gray text-sm">
      名称変更・氏名変更・法人解散の場合は書面での手続きが必要ですので、
      <a href="{{ route('contacts.create') }}">お問い合わせフォーム</a>からご連絡ください。
    </div>
  </div>

  <div class="center">
    <button class="btn btn-primary" type="submit">保存する</button>
  </div>
</form>
@endif
@endsection
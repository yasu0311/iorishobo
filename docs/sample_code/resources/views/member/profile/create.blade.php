@extends('layouts.member')

@section('title', '会員情報登録')

@section('content')
<h1>会員情報登録</h1>
<div class="center gray text-sm">必要な情報をご入力ください。</div>
<x-alert/>

<form action="{{ route('member.profile.store') }}" method="POST" enctype="multipart/form-data">
  <div class="width-md table-vertical-responsive">
  @csrf
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
            <input type="text" name="nickname" class="wd-10" value="{{ old('nickname') }}" maxlength="15" required>
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
              <span class="gray">未設定</span>
            </div>            
            <div id="member-icon-preview"></div>              
            <span class="badge badge-green">公開</span>
            <input type="file" name="member_icon" accept=".jpg, .jpeg, .png, .gif, .bmp, .svg, .webp" onChange="imgPreView(event,'member-icon-preview','member-icon-preview-img')">
            <div class="gray text-sm">対応形式：jpeg, jpg, png, gif, bmp, svg, webp</div>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>法人・個人
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>個人として取引する場合は「個人」、会社や法人として取引する場合は「法人」を選択してください。</span>
            </span>
          </th>
          <td>
          <span class="badge badge-green">公開</span>
              <label>
                <input type="radio" name="company" value="0" {{ old('company', 0) == 0 ? 'checked' : '' }} id="company-type-individual" required>
                個人
              </label>
              <label>
                <input type="radio" name="company" value="1" {{ old('company', 0) == 1 ? 'checked' : '' }} id="company-type-corporate">
                法人
              </label>
          </td>
        </tr>
        <tr class="company-matter">
          <th><span class="badge badge-red">必須</span>法人名</th>
          <td>
            <input type="text" name="company_name" value="{{ old('company_name') }}" class="width-lg" maxlength="50">
            <span class="gray text-sm">50字以内</span>
          </td>
        </tr>
        <tr class="company-matter">
          <th><span class="badge badge-red">必須</span>法人名(カナ)</th>
          <td>
            <input type="text" name="company_name_kana" value="{{ old('company_name_kana') }}" class="width-lg" maxlength="100">
            <span class="gray text-sm">100字以内</span>
          </td>
        </tr>
        <tr class="company-matter">
          <th><span class="badge badge-red">必須</span>本店の所在地</th>
          <td>
            〒<input type="text" name="company_postal_code" class="wd-4" value="{{ old('company_postal_code') }}" placeholder="1234567" pattern="[0-9]*" inputmode="numeric" maxlength="7">
            <span class="gray text-sm">7桁</span><br>
            都道府県：
            <select name="company_prefecture">
              <option value="">選択してください</option>
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
              @foreach($prefectures as $prefecture)
                <option value="{{ $prefecture }}"
                  {{ old('company_prefecture') == $prefecture ? 'selected' : '' }}>
                  {{ $prefecture }}
                </option>
              @endforeach
            </select><br>
            市区町村：<input type="text" name="company_city" class="wd-15" value="{{ old('company_city') }}" placeholder="〇〇市〇〇区"><br>
            番地：<input type="text" name="company_block" class="wd-15" value="{{ old('company_block') }}" placeholder="１－２－３"><br>
            建物：<input type="text" name="company_building" class="wd-15" value="{{ old('company_building') }}" placeholder="〇〇ビル101号室"><br>
          </td>
        </tr>
        <tr class="company-matter">
          <th>
            <span class="badge badge-red">必須</span>法人の電話番号
          </th>
          <td>
            <input type="tel" name="company_phone_number" class="wd-8" value="{{ old('company_phone_number') }}" placeholder="03-1234-5678">
          </td>
        </tr>
        <tr class="company-matter">
          <td colspan="2">以下、法人の代表者についてご入力ください。</td>
        </tr>
        <tr>
          <th><span class="badge badge-red">必須</span>氏名</th>
          <td>
            姓：<input type="text" name="last_name" value="{{ old('last_name') }}" class="wd-5" maxlength="50" placeholder="山田" required>
            名：<input type="text" name="first_name" value="{{ old('first_name') }}" class="wd-5" maxlength="50" placeholder="太郎" required>
          </td>
        </tr>
        <tr>
          <th><span class="badge badge-red">必須</span>氏名(カナ)</th>
          <td>
            姓(カナ)：<input type="text" name="last_name_kana" value="{{ old('last_name_kana') }}" class="wd-5" maxlength="50"  placeholder="ヤマダ" required>
            名(カナ)：<input type="text" name="first_name_kana" value="{{ old('first_name_kana') }}" class="wd-5" maxlength="50"  placeholder="タロウ" required>
          </td>
        </tr>
        <tr>
          <th><span class="badge badge-red">必須</span>住所</th>
          <td>
            〒<input type="text" name="postal_code" class="wd-4" value="{{ old('postal_code') }}" placeholder="1234567" pattern="[0-9]*" inputmode="numeric" maxlength="7" required>
            <span class="gray text-sm">7桁</span><br>
            都道府県：
            <select name="address_prefecture">
              <option value="">選択してください</option>
              @foreach($prefectures as $prefecture)
                <option value="{{ $prefecture }}"
                  {{ old('address_prefecture') == $prefecture ? 'selected' : '' }}>
                  {{ $prefecture }}
                </option>
              @endforeach
            </select><br>
            市区町村：<input type="text" name="address_city" class="wd-15" value="{{ old('address_city') }}" placeholder="〇〇市〇〇区" required><br>
            番地：<input type="text" name="address_block" class="wd-15" value="{{ old('address_block') }}" placeholder="１－２－３" required><br>
            建物：<input type="text" name="address_building" class="wd-15" value="{{ old('address_building') }}" placeholder="〇〇ビル101号室"><br>
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>電話番号
          </th>
          <td>
            <input type="tel" name="phone_number" class="wd-8" value="{{ old('phone_number') }}" placeholder="03-1234-5678" required>
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
              <input type="radio" name="message_notification" value="1" {{ old('message_notification', 1) == 1 ? 'checked' : '' }}> メール通知を受け取る
            </label>
            <label>
              <input type="radio" name="message_notification" value="0" {{ old('message_notification', 1) == 0 ? 'checked' : '' }}> メール通知を受け取らない
            </label>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="center">
    <button class="btn btn-primary" type="submit">登録する</button>
  </div>
</form>
@endsection
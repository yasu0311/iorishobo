@extends('layouts.member')

@section('title', 'ショップ作成')

@section('content')

<h1>ショップ作成</h1>

<x-alert/>

<form method="POST" action="{{ route('member.sell.shop.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="table-vertical-responsive">
    <table>
    <tbody>
        <tr>
        <th><span class="badge badge-red">必須</span>開店状況</th>
        <td>
            <span class="badge badge-green">公開</span>
            <label><input type="radio" name="shop_status" value="1" {{ old('shop_status', '1') == '1' ? 'checked' : '' }}>開店中</label>
            <label><input type="radio" name="shop_status" value="2" {{ old('shop_status') == '2' ? 'checked' : '' }}>準備中</label>
            <label><input type="radio" name="shop_status" value="3" {{ old('shop_status') == '3' ? 'checked' : '' }}>閉店済</label>
        </td>
        </tr>
        <tr>
        <th><span class="badge badge-red">必須</span>ショップ名</th>
        <td>
          <span class="badge badge-green">公開</span>
          <input type="text" name="shop_name" value="{{ old('shop_name') }}" maxlength="20" autofocus>
          <span class="gray text-sm">20字以内</span>
        </td>
        </tr>
        <tr>
            <th>
                <span class="badge badge-gray">任意</span>紹介文
                <span class="help">
                    <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                    <span class="help__content" role="tooltip" hidden>ショップ情報に表示される説明文です。どんな商品を扱っているか、ショップの特徴などをご記入ください。</span>
                </span>
            </th>
            <td>
                <div>
                    <span class="badge badge-green">公開</span>
                </div>
                <textarea name="shop_introduction" rows="12" placeholder="中学の数学・英語の教材を販売しています。現役の塾講師が商品開発を行っています。" maxlength="1000">{{ old('shop_introduction') }}</textarea>
                <span class="gray text-sm">1000字以内</span>
            </td>            
        </tr>
        <tr>
            <th>
                <span class="badge badge-gray">任意</span>ショップ情報
                <span class="help">
                    <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                    <span class="help__content" role="tooltip" hidden>
                        店舗の住所や電話番号などご入力ください。公開したくない場合は入力不要です。
                    </span>
                </span>
            </th>
            <td>
                <span class="badge badge-green">公開</span>
                <textarea name="shop_information" rows="10" placeholder="住所: 東京都新宿区●●●
電話番号: 000-0000-0000
メール: info@example.com
営業時間: 平日 9:00 - 18:00" maxlength="1000">{{ old('shop_information') }}</textarea>            
                <span class="gray text-sm">1000字以内</span>
            </td>
        </tr>
        <tr>
            <th>
                <span class="badge badge-gray">任意</span>URL
                <span class="help">
                    <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                    <span class="help__content" role="tooltip" hidden>会社ホームページやSNSなど、ショップに関連する外部リンクがあればご入力ください。</span>
                </span>
            </th>
            <td>
                <span class="badge badge-green">公開</span>
                <input class="wd-20" name="url" type="url" value="{{ old('url') }}" placeholder="https://example.com" maxlength="255">
            </td>
        </tr>
        <tr>
            <th>
                <span class="badge badge-gray">任意</span>ショップアイコン
                <span class="help">
                    <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                    <span class="help__content" role="tooltip" hidden>
                        ショップページに表示される画像です。ロゴやショップをイメージできる画像をアップロードしてください。
                    </span>
                </span>
            </th>
            <td>
              
              <div id="shop-icon-preview"></div>
              <span class="badge badge-green">公開</span>
              <input type="file" name="shop_icon"  accept=".jpg, .jpeg, .png, .gif, .bmp, .svg, .webp" onChange="imgPreView(event,'shop-icon-preview','shop-icon-preview-img')">
              <div class="gray text-sm">対応形式：jpeg, jpg, png, gif, bmp, svg, webp／2MB以内</div>
            </td>
          </tr>
        <tr>
        <th>
            <span class="badge badge-gray">任意</span>明細書記載事項
            <span class="help">
                <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                <span class="help__content" role="tooltip" hidden>
                    購入者用に発行する明細書に記載されます。
                    購入者にだけ公開したい店舗情報やインボイス番号等をご入力ください。
                </span>
            </span>
        </th>
        <td>
            <span class="badge badge-gray">購入者にのみ公開</span>
            <textarea rows="10" name="receipt_description" placeholder="消費税適格請求書番号：T1234567890" maxlength="1000">{{ old('receipt_description') }}</textarea>
            <span class="gray text-sm">1000字以内</span>
        </td>
        </tr>
        <tr>
        <th>
            <span class="badge badge-red">必須</span>消費税区分
            <span class="help">
                <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                <span class="help__content" role="tooltip" hidden>
                    販売する商品の消費税の扱いを選択してください。
                    免税・非課税事業者は「免税・非課税」、課税事業者は「課税(10%)」を選択してください。
                </span>
            </span>
        </th>
        <td>
            <span class="badge badge-green">公開</span>
            @foreach($consumptionTaxClassifications as $id => $label)
            <label><input type="radio" name="consumption_tax_classification_id" value="{{ $id }}" {{ old('consumption_tax_classification_id', array_key_first($consumptionTaxClassifications)) == (string)$id ? 'checked' : '' }}>{{ e($label) }}</label>
            @endforeach
        </td>
        </tr>
        <tr>
        <th>
            <span class="badge badge-red">必須</span>管理者返信権限
        </th>
        <td>
            <span class="badge badge-gray">非公開</span>
            <label><input type="radio" name="admin_reply" value="0" {{ old('admin_reply', '0') == '0' ? 'checked' : '' }}>不可</label>
            <label><input type="radio" name="admin_reply" value="1" {{ old('admin_reply') == '1' ? 'checked' : '' }}>可</label>
            <div class="gray text-sm">
                「可」を選択すると、購入検討者のメッセージに対してショップが返信しない場合に、サイト管理者が商品内容を確認して購入検討者に返信をすることがあります。
                「不可」の場合は管理者による代行返信は行いません。
            </div>
        </td>
        </tr>
    </tbody>
    </table>
</div>
    <div class="center">
    <button type="button" class="btn btn-white" onclick="location.href='{{ route('member.index') }}'">ホームに戻る</button>
    <button type="submit" class="btn btn-primary">ショップ作成</button>
    </div>
</form>
@endsection
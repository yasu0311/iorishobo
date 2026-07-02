@extends('layouts.member')

@section('title', '商品ファイル新規登録')

@section('content')
<h1>商品ファイル新規登録</h1>

<x-alert/>

@php
  $productFileAccept = collect(config('product-file.allowed_extensions', []))->map(fn ($ext) => '.' . $ext)->implode(', ');
  $productFileAllowedDesc = config('product-file.allowed_extensions_description', '');
@endphp

<form id="product-file-form" action="{{ route('member.sell.product-files.store', $product) }}" method="POST" enctype="multipart/form-data" data-client-max-bytes="{{ (int) $clientMaxProductFileBytes }}" data-client-oversized-message="{{ e($clientProductFileOversizedMessage) }}">
  @csrf
  
  <div class="table-vertical-responsive">
    <table>
      <tbody>
        <tr>
          <th>商品番号</th>
          <td>
            {{ $product->product_number }}
          </td>
        </tr>
        <tr>
          <th>商品名</th>
          <td>
            {{ $product->product_name }}
          </td>
        </tr>
        <tr>
          <th><span class="badge badge-red">必須</span>商品ファイル名</th>
          <td>
            <input type="text" name="file_name" maxlength="100" value="{{ old('file_name') }}" required>
            <span class="text-sm gray">100文字以内</span>
            @error('file_name')
              <div class="red">{{ $message }}</div>
            @enderror
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>アップロード
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>1ファイルあたり{{ $uploadMaxMb ?? 100 }}MB以内。対応形式：{{ $productFileAllowedDesc }}。上記以外の形式はアップロードできません。</span>
            </span>
          </th>
          <td>
            <input type="file" name="product_file" accept="{{ $productFileAccept }}" required>
            <div id="product-file-client-error" class="red mt-1" role="alert" hidden></div>
            <div class="text-sm gray mt-1">1ファイルあたり{{ $uploadMaxMb ?? 100 }}MB以内。対応形式：{{ $productFileAllowedDesc }}</div>
            @error('product_file')
              <div class="red">{{ $message }}</div>
            @enderror
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-red">必須</span>見本
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>「商品」は購入者のみダウンロード可能なファイルです。「見本」は未購入者にも公開され、購入前の確認用として表示されます。</span>
            </span>
          </th>
          <td>
            <label><input type="radio" name="sample" value="0" {{ old('sample', '0') == '0' ? 'checked' : '' }}>商品</label>
            <label><input type="radio" name="sample" value="1" {{ old('sample', '0') == '1' ? 'checked' : '' }}>見本</label>
            @error('sample')
              <div class="red">{{ $message }}</div>
            @enderror
          </td>
        </tr>
        <tr>
          <th><span class="badge badge-red">必須</span>ファイル説明</th>
          <td>
            <textarea name="file_description" rows="20" maxlength="1000" placeholder="例：&#10;【内容】算数プリント全15枚（PDF）。わり算の意味・あまりのあるわり算から応用問題まで。&#10;&#10;【使い方】授業の復習や宿題に。1日1枚で約3週間で完了。長期休みの家庭学習にも。&#10;&#10;【形式】PDF／A4&#10;&#10;【解答】別ファイルで同梱（解答付きでご利用ください）。&#10;&#10;【対象】小学3年生（2学期以降）&#10;&#10;【注意】ZIPで圧縮しています。解凍してからご利用ください。" required>{{ old('file_description') }}</textarea>
            <span class="text-sm gray">1000文字以内</span>
            @error('file_description')
              <div class="red">{{ $message }}</div>
            @enderror
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-gray">非公開</span>著作権に関する事項
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>制作時に著作権者から許諾を得た場合、権利者名や許諾内容や許諾日などを入力してください。すべて自作の場合は記載不要です。</span>
            </span>
          </th>
          <td>
            <textarea name="copyright" rows="8" maxlength="1000" placeholder="例：○○氏（○○社）から使用許諾を取得。許諾日：2024年1月、許諾範囲：当サイトでの販売のみ。">{{ old('copyright') }}</textarea>
            <span class="text-sm gray">1000文字以内</span>
            @error('copyright')
              <div class="red">{{ $message }}</div>
            @enderror
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-gray">非公開</span>プログラムファイルに関する事項
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>マクロなどプログラムファイルを含む場合に、その内容を入力してください。</span>
            </span>
          </th>
          <td>
            <textarea name="macro" rows="8" maxlength="1000" placeholder="例：Excelのマクロ（.xlsm）を含みます。">{{ old('macro') }}</textarea>
            <span class="text-sm gray">1000文字以内</span>
            @error('macro')
              <div class="red">{{ $message }}</div>
            @enderror
          </td>
        </tr>
        <tr>
          <th>
            <span class="badge badge-gray">任意</span>表示順
            <span class="help">
              <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
              <span class="help__content" role="tooltip" hidden>ファイル一覧での並び順を指定します。数値が小さいほど上に表示されます。</span>
            </span>
          </th>
          <td>
            <input type="number" name="display_order" class="wd-3" value="{{ old('display_order') }}" min="0">
            @error('display_order')
              <div class="red">{{ $message }}</div>
            @enderror
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="center">
    <a href="{{ route('member.sell.products.edit', $product) }}" class="btn btn-white">商品編集にもどる</a>
    <button type="submit" class="btn btn-primary">保存する</button>
  </div>
</form>

@include('member.sell.product-files.partials.upload-size-guard-script')

@endsection

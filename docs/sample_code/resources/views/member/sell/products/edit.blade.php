@extends('layouts.member')

@section('title', '商品編集')

@section('content')
<h1>商品編集</h1>

<x-alert/>
<form action="{{ route('member.sell.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
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
            <th><span class="badge badge-red">必須</span>商品名</th>
            <td>
              <input type="text" class="wd-20" name="product_name" maxlength="20" value="{{ old('product_name', $product->product_name) }}" required autofocus>
              <span class="text-sm gray">20文字以内</span>
            </td>
          </tr>
          <tr>
            <th><span class="badge badge-red">必須</span>状態</th>
            <td>
              <label><input type="radio" name="product_status" value="0" {{ old('product_status', $product->product_status) == 0 ? 'checked' : '' }}>準備中</label>
              <label><input type="radio" name="product_status" value="1" {{ old('product_status', $product->product_status) == 1 ? 'checked' : '' }}><b>販売中</b></label>
              <label><input type="radio" name="product_status" value="2" {{ old('product_status', $product->product_status) == 2 ? 'checked' : '' }}>販売終了</label>
              @if($product->product_limited)
              <div class="red">サイト運営者の判断により、販売停止中です。理由・販売再開方法はメール等でご連絡差し上げております。</div>
              @endif
            </td>
          </tr>
          <tr>
            <th><span class="badge badge-red">必須</span>価格</th>
            <td>
              個人利用：
              税抜<input type="text" id="price_for_personal" name="price_for_personal"
                     value="{{ old('price_for_personal') !== null ? number_format((int)str_replace(',', '', old('price_for_personal'))) : number_format((int)($product->price_for_personal ?? 0)) }}"
                     class="wd-4">円
              （<span id="personal_tax_included"></span>）<br>
            
              学校利用：
              税抜<input type="text" id="price_for_school" name="price_for_school"
                     value="{{ old('price_for_school') !== null ? number_format((int)str_replace(',', '', old('price_for_school'))) : number_format((int)($product->price_for_school ?? 0)) }}"
                     class="wd-4">円
              （<span id="school_tax_included"></span>）<br>
            
              商用利用：
              税抜<input type="text" id="price_for_commercial" name="price_for_commercial"
                     value="{{ old('price_for_commercial') !== null ? number_format((int)str_replace(',', '', old('price_for_commercial'))) : number_format((int)($product->price_for_commercial ?? 0)) }}"
                     class="wd-4">円
              （<span id="commercial_tax_included"></span>）<br>

              <div class="text-sm gray mt-1">入力可能金額: 0円 または {{ number_format($minimumListingPrice) }}円〜{{ number_format($maximumListingPrice) }}円</div>
            
              <div class="text-sm gray mt-1">個人利用・学校利用・商用利用の区分の詳細は<a href="{{ route('static.copyright-purchaser') }}" target="_blank" rel="noopener">こちらのページ</a>をご確認ください。</div>
              
              <div class="text-sm gray">現在の消費税率の設定は<span id="tax-rate">「{{ $product->shop?->getConsumptionTaxRateText() }}」</span>です。
                消費税設定は<a href="{{ route('member.sell.shop.edit') }}">ショップ設定</a>から変更できます。</div>
            </td>
          </tr>
          <tr>
            <th><span class="badge badge-red">必須</span>教科(複数可)</th>
            <td>
              @foreach($subjects as $subject)
              <label><input type="checkbox" name="subjects[]" value="{{ $subject->id }}" {{ in_array($subject->id, old('subjects', $selectedSubjects)) ? 'checked' : '' }}> {{ $subject->subject }}</label>
              @endforeach
            </td>
          </tr>
          <tr>
            <th><span class="badge badge-red">必須</span>学年(複数可)</th>
            <td>
              @foreach($grades as $grade)
              <label><input type="checkbox" name="grades[]" value="{{ $grade->id }}" {{ in_array($grade->id, old('grades', $selectedGrades)) ? 'checked' : '' }}> {{ $grade->grade }}</label>
              @endforeach
            </td>
          </tr>
          <tr>
            <th><span class="badge badge-red">必須</span>主なファイル種類(複数可)</th>
            <td>
              @foreach($fileTypes as $fileType)
              <label><input type="checkbox" name="file_types[]" value="{{ $fileType->id }}" {{ in_array($fileType->id, old('file_types', $selectedFileTypes)) ? 'checked' : '' }}> {{ $fileType->file_type_name }}</label>
              @endforeach
            </td>
          </tr>
          <tr>
            <th>
              <span class="badge badge-gray">任意</span>商品概要
              <span class="help">
                <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                <span class="help__content" role="tooltip" hidden>検索結果一覧ページなどで表示される短い説明です。</span>
              </span>
            </th>
            <td>
              <textarea name="product_summary" rows="2" maxlength="40" placeholder="例：小学校3年生向けの割り算プリント集です。">{{ old('product_summary', $product->product_summary) }}</textarea>
              <span class="text-sm gray">40文字以内</span>
            </td>
          </tr>
          <tr>
            <th><span class="badge badge-red">必須</span>商品説明</th>
            <td>
              <textarea name="product_description" rows="20" maxlength="2000" placeholder="例：小学校3年生向けの割り算プリント集です。&#10;&#10;【内容】割り算の基礎（わり算の意味・あまりのあるわり算）から応用問題まで全15枚。&#10;&#10;【使い方】授業の復習や宿題、長期休みの家庭学習にご利用ください。1日1枚ずつ進めると約3週間で完了します。&#10;&#10;【対象】小学3年生（2学期以降）&#10;&#10;【形式】PDF&#10;&#10;【サイズ】A4&#10;&#10;【解答】あり" required>{{ old('product_description', $product->product_description) }}</textarea>
              <span class="text-sm gray">2000文字以内</span>
            </td>
          </tr>
          <tr>
              <th><span class="badge badge-gray">任意</span>更新情報</th>
              <td>
                <textarea name="update_information" rows="10" placeholder="例：2024/04/01 問題を10題追加しました。">{{ old('update_information', $product->update_information) }}</textarea>
              </td>
          </tr>
          <tr>
            <th><span class="badge badge-gray">任意</span>商品イメージ画像</th>
            <td>
              @if($product->product_image_url)
              <div><span>現在の画像：</span><img src="{{ $product->product_image_url }}" alt="商品画像" class="vertical-middle" style="max-width: 200px;"></div>
              @endif
              <div class="product-image-preview" id="product-image-preview"></div>
              <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/bmp,image/svg+xml,image/webp" onChange="imgPreView(event,'product-image-preview','product-image-preview-img')">
            </td>
          </tr>
          <tr>
            <th>
              <span class="badge badge-gray">任意</span>表示順
              <span class="help">
                <button type="button" class="help__trigger" aria-expanded="false" aria-label="説明を表示">?</button>
                <span class="help__content" role="tooltip" hidden>商品一覧での並び順を指定します。数値が小さいほど上に表示されます。</span>
              </span>
            </th>
            <td>
              <input type="number" name="display_order" class="wd-3" value="{{ old('display_order', $product->display_order) }}" min="0">
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="center">
      <a href="{{ route('member.sell.products.index') }}" class="btn btn-white">商品一覧にもどる</a>
      <button type="submit" class="btn btn-primary">保存する</button>
    </div>
</form>

<div class="file-list">
  <h3>商品ファイル一覧</h3>
  @if(isset($productFilesLimit) && is_numeric($productFilesLimit) && (int) $productFilesLimit >= 1)
  <p class="text-sm gray mb-2">
    登録済みファイル数 {{ (int) ($productFileCount ?? $product->productFiles->count()) }}件／上限 {{ (int) $productFilesLimit }}件<br>
    {{ (int) $productFilesLimit }}件を超える場合は、ZIPなどで1ファイルにまとめる方法をご検討ください。
  </p>
  @endif
  @forelse($product->productFiles as $productFile)
  <details>
    <summary>
      <span class="summary-inner">
        <span class="file-list-icon"></span><span>{{ $productFile->file_name }}</span>
        @if($productFile->sample)
        <span class="badge badge-green">見本</span>
        @endif
      </span>
    </summary>
    <div class="detail">
      <div class="center">
        <a href="{{ route('member.sell.product-files.edit', ['product' => $product, 'file' => $productFile]) }}" class="btn btn-small btn-primary">編集</a>
        <form action="{{ route('member.sell.product-files.destroy', ['product' => $product, 'file' => $productFile]) }}" method="POST" style="display: inline;" data-file-name="{{ e($productFile->file_name) }}" onsubmit="return confirmProductFileDelete(this);">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-small btn-red">削除</button>
        </form>
        <a href="{{ route('member.sell.products.download', ['productFile' => $productFile]) }}" class="btn btn-small btn-green" download>ダウンロード</a>
      </div>
      <div class="width-lg table-vertical-responsive">
        <table>
          <tbody>
            <tr>
              <th>ファイル名</th>
              <td>
                {{ $productFile->file_name }}
              </td>
            </tr>
            <tr>
              <th>ファイル説明</th>
              <td>
                {!! nl2br(e($productFile->file_description)) !!}
              </td>
            </tr>
            @if($productFile->copyright)
            <tr>
              <th><span class="badge badge-gray">非公開</span>著作権に関する事項</th>
              <td>
                {!! nl2br(e($productFile->copyright)) !!}
              </td>
            </tr>
            @endif
            @if($productFile->macro)
            <tr>
              <th><span class="badge badge-gray">非公開</span>プログラムファイルに関する事項</th>
              <td>
                {!! nl2br(e($productFile->macro)) !!}
              </td>
            </tr>
            @endif
            <tr>
              <th>ファイル更新日</th>
              <td>
                {{ $productFile->file_updated_at ? $productFile->file_updated_at->format('Y年n月j日') : '-' }}
              </td>
            </tr>
            <tr>
              <th>拡張子</th>
              <td>
                {{ $productFile->file_path ? pathinfo($productFile->file_path, PATHINFO_EXTENSION) : '-' }}
              </td>
            </tr>
            <tr>
              <th>表示順</th>
              <td>
                {{ $productFile->display_order ?? '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>           
    </div>
  </details>
  @empty
  <p class="text-center">商品ファイルが登録されていません</p>
  @endforelse
</div>

  <div class="center">
  @php
    $productFilesLimitInt = isset($productFilesLimit) && is_numeric($productFilesLimit) ? (int) $productFilesLimit : 0;
    $productFileCountInt = (int) ($productFileCount ?? 0);
    $atProductFileLimit = $productFilesLimitInt >= 1 && $productFileCountInt >= $productFilesLimitInt;
  @endphp
  @if($productFilesLimitInt >= 1)
    @if($atProductFileLimit)
    <div class="width-sm left text-sm mb-3">
      <p class="red mb-2">
        １つの商品に登録できるファイル数が上限（{{ $productFilesLimitInt }}件）に達しています。<br>
        追加するには既存ファイルを削除するか、ZIPなどで1ファイルにまとめる方法をご検討ください。
      </p>
    </div>
    @endif
  @endif
  @if($atProductFileLimit)
  <button type="button" class="btn btn-primary" disabled>商品ファイル新規登録</button>
  @else
  <button type="button" class="btn btn-primary" onclick="window.location='{{ route('member.sell.product-files.create', $product) }}'">商品ファイル新規登録</button>
  @endif
  </div>

@endsection

@section('script')
<script>
  function confirmProductFileDelete(form) {
    var name = form.getAttribute('data-file-name');
    if (!name) return confirm('このファイルを削除してもよろしいですか？');
    var escaped = name.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\r/g, '\\r').replace(/\n/g, '\\n');
    return confirm(escaped + 'を削除してもよろしいですか？');
  }
  document.addEventListener('DOMContentLoaded', function() {
    const taxRate = {{ $product->shop?->getConsumptionTaxRate() }};
    [
      ['price_for_personal', 'personal_tax_included'],
      ['price_for_school', 'school_tax_included'],
      ['price_for_commercial', 'commercial_tax_included'],
    ].forEach(function(pair) {
      const input = document.getElementById(pair[0]);
      if (!input) return;
      input.addEventListener('input', function() {
        calculateTaxIncluded(input, pair[1], taxRate);
      });
      calculateTaxIncluded(input, pair[1], taxRate);
    });
  });
</script>
@endsection


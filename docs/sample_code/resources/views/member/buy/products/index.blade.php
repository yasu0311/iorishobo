@extends(auth()->check() ? 'layouts.member' : 'layouts.guest')

@section('title', '教材検索')

@section('content')
<h1>教材検索</h1>
      <form method="GET" action="{{ route('member.buy.products.index') }}" class="filtering">
        <div class="flex">
          <div class="filtering-column">
            <label for="grade">学年：</label>
            <select name="grade" id="grade" autofocus>
              <option value=""></option>
              @foreach($options['grade'] as $id => $grade)
                <option value="{{ $id }}" {{ request('grade') == $id ? 'selected' : '' }}>{{ $grade }}</option>
              @endforeach
            </select>
          </div>
          <div class="filtering-column">
            <label for="subject">教科：</label>
            <select name="subject" id="subject">
              <option value=""></option>
              @foreach($options['subject'] as $id => $subject)
                <option value="{{ $id }}" {{ request('subject') == $id ? 'selected' : '' }}>{{ $subject }}</option>
              @endforeach
            </select>
          </div>
          <div class="filtering-column">
            利用法：
            @foreach($options['usage'] as $value => $label)
              <label><input type="radio" name="usage" value="{{ $value }}" {{ request('usage', '1') == $value ? 'checked' : '' }}>{{ $label }}</label>
            @endforeach
          </div>
          <div class="filtering-column">
            <label for="product_name">商品名：</label>
            <input name="product_name" id="product_name" type="text" value="{{ request('product_name') }}"></input>
          </div>
          <div class="filtering-column">
            <label for="shop">販売者：</label>
            <input name="shop" id="shop" type="text" value="{{ request('shop') }}"></input>
          </div>
          
          <div class="filtering-column">
            <label for="price">価格：</label>
            <input type="checkbox" id="free" name="free" value="1" {{ request('free') ? 'checked' : '' }} onchange="change('free','price_min');change('free','price_max');"><label for="free">無料のみ</label>
            <input name="price_min" id="price_min" type="text" class="wd-5 amount-input" value="{{ request('price_min') !== null && request('price_min') !== '' ? number_format((int)str_replace(',', '', request('price_min'))) : '' }}">円～
            <input name="price_max" id="price_max" type="text" class="wd-5 amount-input" value="{{ request('price_max') !== null && request('price_max') !== '' ? number_format((int)str_replace(',', '', request('price_max'))) : '' }}">円
          </div>

          <div class="filtering-column">
            <label for="per_page">表示件数：</label>
            <select name="per_page" id="per_page">
              @foreach($options['per_page'] as $value => $label)
                <option value="{{ $value }}" {{ request('per_page', '10') == $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>

          <div class="filtering-column">
            <label for="sort">表示順：</label>
            <select name="sort" id="sort">
              @foreach($options['sort'] as $value => $label)
                <option value="{{ $value }}" {{ request('sort', 'おすすめ順') == $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="center">
          <button type="submit" class="btn btn-primary">検索</button>
        </div>
      </form>

      @if($request->hasAny(['grade', 'subject', 'usage', 'product_name', 'shop', 'price_min', 'price_max', 'free', 'sort']))
        <div class="center gray text-sm">
          現在の条件：
          @if($request->filled('product_name'))
            【商品名】{{ $request->input('product_name') }}
          @endif
          @if($request->filled('shop'))
            【販売者】{{ $request->input('shop') }}
          @endif
          @if($request->filled('usage'))
            【利用法】{{ $options['usage'][$request->input('usage')] }}
          @endif
          @if($request->filled('grade'))
            【学年】{{ $options['grade'][$request->input('grade')] }}
          @endif
          @if($request->filled('subject'))
            【教科】{{ $options['subject'][$request->input('subject')] }}
          @endif
          @if($request->filled('price_min') || $request->filled('price_max'))
            【価格】{{ $request->input('price_min') }}円～{{ $request->input('price_max') }}円
          @endif
          @if($request->filled('free'))
            【無料のみ】
          @endif
          @if($request->filled('sort'))
            【表示順】{{ $options['sort'][$request->input('sort')] }}
          @endif
        </div>
      @endif

      @forelse($products as $product)
        <div class="width-lg card card-shadow p-4">
          <div class="text-lg">
            <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
          </div>
          <div>
            {{ $product->product_summary }}
          </div>
          <div>
            <div class="flex card text-lg m-1 p-1">
              <div class="mr-4">個人利用：<span class="bold">{{ $product->price_for_personal_text }}</span></div>
              <div class="mr-4">学校利用：<span class="bold">{{ $product->price_for_school_text }}</span></div>
              <div>商用利用：<span class="bold">{{ $product->price_for_commercial_text }}</span></div>
            </div>
            <div class="flex justify-between">
              <div>
                @if($product->rating_average)
                  <span class="review-stars">
                    <span class="gold-stars" style="--score:{{ $product->rating_average }}">★★★★★</span>
                    <span class="gray-stars" style="--score:{{ $product->rating_average }}">★★★★★</span>
                  </span>({{ $product->rating_average }})
                @endif
                @foreach($product->fileTypes as $fileType)
                  @if($fileType->file_type === 'Word')
                    <img src="{{ asset('images/word.png') }}" class="icon m-0" alt="Word">
                  @elseif($fileType->file_type === 'PDF')
                    <img src="{{ asset('images/pdf.png') }}" class="icon m-0" alt="PDF">
                  @endif
                @endforeach
              </div>
              <div>
                <img src="{{ $product->shop->shop_icon_url }}" alt="ショップ画像" class="icon">
                <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop->shop_name }}</a>
              </div>
            </div>
            <div class="right light-gray text-xs">
              商品登録日{{ $product->created_at->format('Y/m/d') }}
            </div>
          </div>
        </div>
        @empty
        <div class="center gray text-lg">
          検索条件に一致する商品が見つかりませんでした。
        </div>
        @endempty
  
        <x-pagination :paginator="$products" />



    </div>

    <script>
      function change(checkboxId, inputId) {
        const checkbox = document.getElementById(checkboxId);
        const input = document.getElementById(inputId);
        if (checkbox.checked) {
          input.disabled = true;
          input.value = '';
        } else {
          input.disabled = false;
        }
      }
    </script>
@endsection
@extends('layouts.member')

@section('title', '評価・レビュー投稿')

@section('content')
<h1>評価・レビュー投稿</h1>
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form method="post" action="{{ route('member.reviews.confirm', $product) }}">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <div class="width-md table-vertical-responsive">
        <table>
            <tbody>
            <tr>
                <th>商品</th>
                <td>
                    <a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a>
                </td>
            </tr>
            <tr>
                <th><span class="badge badge-red">必須</span>注文</th>
                <td>
                    <select name="order_id" autofocus>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}" {{ old('order_id', $input['order_id'] ?? '') == $order->id ? 'selected' : '' }}>
                                注文番号: {{ $order->order_number }} - 購入日: {{ $order->ordered_at ? $order->ordered_at->format('Y年n月j日') : '-' }}
                            </option>
                        @endforeach
                    </select>
                    @error('order_id')
                        <div class="text-sm text-danger mt-1">{{ $message }}</div>
                    @enderror
                </td>
            </tr>
            <tr>
                <th>名前</th>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ ($member->member_icon) ? asset('storage/'.$member->member_icon) : asset('images/front/default_member_icon.png') }}" class="icon" alt="アイコン">
                        <span>{{ $member->nickname ?? optional($member->user)->name ?? 'ゲスト' }}</span>
                    </div>
                    <div class="text-sm gray">名前・アイコンの変更は<a href="{{ route('member.profile.edit') }}">登録情報変更</a>からお願いします。</div>
                </td>
            </tr>
            <tr>
                <th><span class="badge badge-red">必須</span>評価</th>
                <td>
                    @php
                        $rating = old('rating', $input['rating'] ?? 5);
                    @endphp
                    <label>
                        <input type="radio" name="rating" value="5" {{ (int)$rating === 5 ? 'checked' : '' }}>
                        <span class="review-stars">
                            <span class="gold-stars" style="--score:5">★★★★★</span>
                            <span class="gray-stars" style="--score:5">★★★★★</span>
                        </span>
                        満足
                    </label><br>
                    <label>
                        <input type="radio" name="rating" value="4" {{ (int)$rating === 4 ? 'checked' : '' }}>
                        <span class="review-stars">
                            <span class="gold-stars" style="--score:4">★★★★★</span>
                            <span class="gray-stars" style="--score:4">★★★★★</span>
                        </span>
                        やや満足
                    </label><br>
                    <label>
                        <input type="radio" name="rating" value="3" {{ (int)$rating === 3 ? 'checked' : '' }}>
                        <span class="review-stars">
                            <span class="gold-stars" style="--score:3">★★★★★</span>
                            <span class="gray-stars" style="--score:3">★★★★★</span>
                        </span>
                        普通
                    </label><br>
                    <label>
                        <input type="radio" name="rating" value="2" {{ (int)$rating === 2 ? 'checked' : '' }}>
                        <span class="review-stars">
                            <span class="gold-stars" style="--score:2">★★★★★</span>
                            <span class="gray-stars" style="--score:2">★★★★★</span>
                        </span>
                        やや不満
                    </label><br>
                    <label>
                        <input type="radio" name="rating" value="1" {{ (int)$rating === 1 ? 'checked' : '' }}>
                        <span class="review-stars">
                            <span class="gold-stars" style="--score:1">★★★★★</span>
                            <span class="gray-stars" style="--score:1">★★★★★</span>
                        </span>
                        不満
                    </label>
                    @error('rating')
                        <div class="text-sm text-danger mt-1">{{ $message }}</div>
                    @enderror
                </td>
            </tr>
            <tr>
                <th>レビュー</th>
                <td>
                    <textarea name="review" rows="7" placeholder="良問が多く、とても役に立ちました。">{{ old('review', $input['review'] ?? '') }}</textarea>
                    @error('review')
                        <div class="text-sm text-danger mt-1">{{ $message }}</div>
                    @enderror
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="center">
        <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.reviews.index', $product) }}'">もどる</button>
        <button class="btn btn-primary" type="submit">評価・レビュー内容を確認</button>
    </div>
</form>
@endsection
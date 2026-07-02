@extends('layouts.member')

@section('title', '質問・メッセージ投稿')

@section('content')

<h1>質問・メッセージ投稿</h1>
<x-alert />
<form method="post" action="{{ route('member.messages.confirm', $product) }}">
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
                <th>販売者</th>
                <td>
                    <a href="{{ route('member.buy.shops.show', $product->shop) }}">{{ $product->shop->shop_name }}</a>
                </td>
            </tr>
            <tr>
                <th>名前</th>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ $member->member_icon_url }}" class="icon" alt="アイコン">
                        <span>{{ $member->nickname }}</span>
                    </div>
                    <div class="text-sm gray">名前・アイコンの変更は<a href="{{ route('profile.edit') }}">登録情報変更</a>からお願いします。</div>
                </td>
            </tr>
            <tr>
                <th><span class="badge badge-red">必須</span>公開設定</th>
                <td>
                    @php
                        $publicSender = old('public_sender', $input['public_sender'] ?? 1);
                    @endphp
                    <label class="mr-3">
                        <input type="radio" name="public_sender" value="1" {{ (int)$publicSender === 1 ? 'checked' : '' }}>
                        公開可
                    </label>
                    <label>
                        <input type="radio" name="public_sender" value="0" {{ (int)$publicSender === 0 ? 'checked' : '' }}>
                        公開不可
                    </label>
                </td>
            </tr>
            <tr>
                <th><span class="badge badge-red">必須</span>タイトル</th>
                <td>
                    <input type="text" name="title" value="{{ old('title', $input['title'] ?? '') }}" maxlength="20" placeholder="最大20文字" autofocus>
                </td>
            </tr>
            <tr>
                <th><span class="badge badge-red">必須</span>内容</th>
                <td>
                    <textarea name="message" rows="7">{{ old('message', $input['message'] ?? '') }}</textarea>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="center">
        <button class="btn btn-white" type="button" onclick="window.location.href='{{ route('member.messages.index', $product) }}'">もどる</button>
        <button class="btn btn-primary" type="submit">送信内容を確認</button>
    </div>
</form>

@endsection
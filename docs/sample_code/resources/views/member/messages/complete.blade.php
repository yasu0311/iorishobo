@extends('layouts.member')

@section('title', '質問・メッセージ送信完了')

@section('content')

<h1>質問・メッセージ送信完了</h1>
      <div class="center">
        質問・メッセージの送信が完了しました。
      </div>
      <div class="width-md table-vertical-responsive mt-4">
        <table>
          <tbody>
            <tr>
              <th>商品</th>
              <td><a href="{{ route('member.buy.products.show', $product) }}">{{ $product->product_name }}</a></td>
            </tr>
            <tr>
              <th>公開設定</th>
              <td>{{ (int)($input['public_sender'] ?? 1) === 1 ? '公開可' : '公開不可' }}</td>
            </tr>
            <tr>
              <th>タイトル</th>
              <td>{{ $input['title'] ?? '' }}</td>
            </tr>
            <tr>
              <th>内容</th>
              <td>{!! nl2br(e($input['message'] ?? '')) !!}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="center">
        <button class="btn btn-primary" onclick="window.location.href='{{ route('member.messages.index', $product) }}'">メッセージ一覧へ</button>
      </div>

@endsection
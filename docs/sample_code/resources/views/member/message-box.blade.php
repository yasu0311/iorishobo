@extends('layouts.member')

@section('title', 'メッセージボックス')

@section('content')
<h1>メッセージ一覧</h1>
<div class="table-horizontal-responsive td-1-center td-2-center">
  <table>
    <thead>
      <tr>
        <th>新着</th>
        <th>種類</th>
        <th>相手方</th>
        <th>商品名と最新メッセージ</th>
        <th>送信日時</th>
      </tr>
    </thead>
    <tbody>
      @forelse($threads as $thread)
        <tr>
          <td data-label="新着">
            @if($thread['is_unread'])
              <span class="badge badge-red">未読</span>
            @endif
          </td>
          <td data-label="種類">
            {{ $thread['type'] === 'message' ? 'メッセージ' : 'レビュー' }}
          </td>
          <td data-label="相手方">
            @if(!empty($thread['counterparty_icon']))
              <img src="{{ $thread['counterparty_icon'] }}" class="icon" alt="{{ $thread['counterparty_name'] }}">
            @endif
            @if($thread['counterparty_url'])
              <span>{{ $thread['counterparty_name'] }}</span>
            @else
              {{ $thread['counterparty_name'] }}
            @endif
          </td>
          <td data-label="商品名とメッセージ">
            <div>
              @if($thread['product'] ?? false)
                <a href="{{ $thread['view_url'] }}">{{ $thread['product']->product_name }}</a>
              @else
                商品情報なし
              @endif
            </div>
            <div>{{ \Illuminate\Support\Str::limit($thread['latest_text'], 60) }}</div>
          </td>
          <td data-label="送信日時">
            {{ optional($thread['latest_date'])->format('Y年n月j日 H:i') }}
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="center">メッセージはありません。</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
<x-pagination :paginator="$threads" />
@endsection
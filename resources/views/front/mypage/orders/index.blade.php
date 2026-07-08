@extends('layouts.front')

@section('title', '注文履歴 - '.config('shop.name'))

@section('content')
    <a href="{{ route('mypage.index') }}" class="back-link">← マイページへ戻る</a>

    <h1>注文履歴</h1>

    @if ($orders->isEmpty())
        <p class="text-muted">注文履歴はありません。</p>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>注文番号</th>
                        <th>注文日</th>
                        <th>合計</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->ordered_at->format('Y-m-d') }}</td>
                            <td>{{ number_format($order->total) }}円</td>
                            <td>
                                <a href="{{ route('mypage.orders.show', $order) }}">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$orders" />
    @endif
@endsection

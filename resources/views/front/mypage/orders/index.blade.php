@extends('layouts.front')

@section('title', '注文履歴 - '.config('shop.name'))

@section('content')
    <h1>注文履歴</h1>

    <p><a href="{{ route('mypage.index') }}">マイページへ戻る</a></p>

    @if ($orders->isEmpty())
        <p>注文履歴はありません。</p>
    @else
        <table border="1" cellpadding="8">
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
                            <a href="{{ route('mypage.orders.receipt', $order) }}">領収書</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $orders->links() }}
    @endif
@endsection

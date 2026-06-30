@extends('layouts.admin')

@section('title', 'ダッシュボード')

@section('content')
    <h1>ダッシュボード</h1>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-card__label">未発送注文</div>
            <div class="stat-card__value">{{ number_format($unshippedCount) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">入金確認待ち</div>
            <div class="stat-card__value">{{ number_format($pendingPaymentCount) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__label">本日の注文</div>
            <div class="stat-card__value">{{ number_format($todayOrderCount) }}</div>
        </div>
    </div>

    <p><a href="{{ route('admin.orders.index') }}">注文一覧へ</a></p>
@endsection

@extends('layouts.mail-text')

@section('content')

出金手続き受け付けました。
出金額: {{ number_format($withdrawal->amount) }}円
出金手数料: {{ number_format($withdrawal->withdrawal_fee) }}円
振込金額: {{ number_format($withdrawal->amount - $withdrawal->withdrawal_fee) }}円
@if($withdrawal->comment)
コメント: {{ $withdrawal->comment }}
@endif
@endsection
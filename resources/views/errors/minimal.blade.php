@extends('errors.layout')

@section('title', 'エラー')

@section('content')
    <h1>エラー（{{ $exception->getStatusCode() }}）</h1>
    <p>{{ $exception->getMessage() ?: 'リクエストを処理できませんでした。' }}</p>
@endsection

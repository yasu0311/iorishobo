@extends('layouts.front')

@section('title', 'カテゴリ一覧 - '.config('shop.name'))

@section('content')
    <h1>カテゴリ一覧</h1>

    @if ($categories->isEmpty())
        <p>カテゴリはありません。</p>
    @else
        <ul>
            @foreach ($categories as $category)
                <li>
                    <a href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a>
                    @if ($category->childrenOrdered->isNotEmpty())
                        <ul>
                            @foreach ($category->childrenOrdered as $child)
                                <li>
                                    <a href="{{ route('categories.show', $child->slug) }}">{{ $child->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
@endsection

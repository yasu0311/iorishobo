@extends('layouts.front')

@section('title', 'カテゴリ一覧 - '.config('shop.name'))

@section('content')
    <h1>カテゴリ一覧</h1>

    @if ($categories->isEmpty())
        <p class="text-muted">カテゴリはありません。</p>
    @else
        <ul class="category-tree">
            @foreach ($categories as $category)
                <li>
                    <a href="{{ route('categories.show', $category->slug) }}" class="category-chip" style="font-size: 1rem;">{{ $category->name }}</a>
                    @if ($category->childrenOrdered->isNotEmpty())
                        <ul class="category-tree__children">
                            @foreach ($category->childrenOrdered as $child)
                                <li>
                                    <a href="{{ route('categories.show', $child->slug) }}" class="category-chip">{{ $child->name }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
@endsection

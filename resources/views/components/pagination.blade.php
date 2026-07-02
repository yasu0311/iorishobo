@props(['paginator'])

@if ($paginator->total() > 0)
    <p class="pagination-summary">
        全 {{ $paginator->total() }} 件中、{{ $paginator->firstItem() }}〜{{ $paginator->lastItem() }} 件を表示
    </p>
@endif

@if ($paginator->hasPages())
    <nav class="pagination" aria-label="ページナビゲーション">
        @if ($paginator->onFirstPage())
            <span class="pagination__link pagination__link--disabled" aria-hidden="true">&laquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination__link" rel="prev">&laquo;</a>
        @endif

        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $range = 2;
        @endphp

        @if ($lastPage <= 5)
            @for ($page = 1; $page <= $lastPage; $page++)
                @if ($page == $currentPage)
                    <span class="pagination__link pagination__link--current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="pagination__link">{{ $page }}</a>
                @endif
            @endfor
        @else
            @if ($currentPage == 1)
                <span class="pagination__link pagination__link--current" aria-current="page">1</span>
            @else
                <a href="{{ $paginator->url(1) }}" class="pagination__link">1</a>
            @endif

            @if ($currentPage > $range + 2)
                <span class="pagination__link pagination__link--disabled">…</span>
            @endif

            @for ($page = max(2, $currentPage - $range); $page <= min($lastPage - 1, $currentPage + $range); $page++)
                @if ($page == $currentPage)
                    <span class="pagination__link pagination__link--current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="pagination__link">{{ $page }}</a>
                @endif
            @endfor

            @if ($currentPage < $lastPage - $range - 1)
                <span class="pagination__link pagination__link--disabled">…</span>
            @endif

            @if ($lastPage == $currentPage)
                <span class="pagination__link pagination__link--current" aria-current="page">{{ $lastPage }}</span>
            @else
                <a href="{{ $paginator->url($lastPage) }}" class="pagination__link">{{ $lastPage }}</a>
            @endif
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination__link" rel="next">&raquo;</a>
        @else
            <span class="pagination__link pagination__link--disabled" aria-hidden="true">&raquo;</span>
        @endif
    </nav>
@endif

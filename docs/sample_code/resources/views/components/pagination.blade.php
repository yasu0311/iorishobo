{{-- 件数表示は、合計件数が1件以上あれば表示する（ページネーションの有無に関わらず） --}}
@if ($paginator->total() > 0)
    <div class="center text-sm dark-gray">
        全 {{ $paginator->total() }} 件中、
        {{ $paginator->firstItem() }}〜{{ $paginator->lastItem() }} 件を表示
    </div>
@endif


@if ($paginator->hasPages())
    <div class="pagination">
        {{-- 前へ --}}
        @if ($paginator->onFirstPage())
            <span class="pagination__link pagination__link--disabled">&laquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination__link">&laquo;</a>
        @endif

        {{-- ページ番号 --}}
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $range = 2; // 現在のページの前後に表示するページ数
        @endphp

        @if ($lastPage <= 5)
            {{-- ページ数が少ない場合は全て表示 --}}
            @for ($page = 1; $page <= $lastPage; $page++)
                @if ($page == $currentPage)
                    <span class="pagination__link pagination__link--current">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="pagination__link">{{ $page }}</a>
                @endif
            @endfor
        @else
            {{-- 最初のページ --}}
            @if ($currentPage == 1)
                <span class="pagination__link pagination__link--current">1</span>
            @else
                <a href="{{ $paginator->url(1) }}" class="pagination__link">1</a>
            @endif
            
            {{-- 左側の省略 --}}
            @if ($currentPage > $range + 2)
                <span class="pagination__link pagination__link--disabled">...</span>
            @endif
            
            {{-- 現在のページの前後のページ --}}
            @for ($page = max(2, $currentPage - $range); $page <= min($lastPage - 1, $currentPage + $range); $page++)
                @if ($page == $currentPage)
                    <span class="pagination__link pagination__link--current">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="pagination__link">{{ $page }}</a>
                @endif
            @endfor
            
            {{-- 右側の省略 --}}
            @if ($currentPage < $lastPage - $range - 1)
                <span class="pagination__link pagination__link--disabled">...</span>
            @endif
            
            {{-- 最後のページ --}}
            @if ($lastPage == $currentPage)
                <span class="pagination__link pagination__link--current">{{ $lastPage }}</span>
            @else
                <a href="{{ $paginator->url($lastPage) }}" class="pagination__link">{{ $lastPage }}</a>
            @endif
        @endif

        {{-- 次へ --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"  class="pagination__link">&raquo;</a>
        @else
            <span class="pagination__link pagination__link--disabled">&raquo;</span>
        @endif
    </div>
@endif

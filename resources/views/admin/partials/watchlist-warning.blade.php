@if ($watchlistMatches->isNotEmpty())
    <div class="flash flash--error" role="alert">
        <strong>⚠ 要注意リストに該当する購入者です</strong>
        <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
            @foreach ($watchlistMatches as $entry)
                <li>{!! nl2br(e($entry->reason)) !!}</li>
            @endforeach
        </ul>
    </div>
@endif

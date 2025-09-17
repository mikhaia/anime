@php
    $mode = $mode ?? 'favorites';
    $searchQuery = $searchQuery ?? '';
    $statusMessages = [
        'top' => 'Загружаем подборку…',
        'new' => 'Загружаем подборку…',
        'search' => ''
    ];
    $statusText = $statusMessages[$mode] ?? 'Загружаем подборку…';
@endphp

<div class="anime-list"
     data-anime-list
     data-mode="{{ $mode }}"
     @if($mode === 'search')
         data-anime-search-results
         data-search-query="{{ $searchQuery }}"
     @endif>
    <div class="anime-list__status" data-anime-status role="status">
        <span class="anime-list__status-spinner" aria-hidden="true"
              @if($mode === 'search') style="display: none;" @endif></span>
        <span class="anime-list__status-text">{{ $statusText }}</span>
    </div>
    <div class="anime-grid" data-anime-grid hidden></div>
    <button class="anime-list__more" type="button" data-load-more hidden>
        <span class="material-symbols-outlined" aria-hidden="true">refresh</span>
        Показать ещё
    </button>
</div>

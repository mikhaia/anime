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
    <div data-anime-card-actions-template style="display: none;">
        <div class="anime-card__actions" aria-hidden="true">
            <div class="anime-card__actions-section">
                <a class="anime-card__action anime-card__action--watch" href="#">Смотреть</a>
            </div>
            <div class="anime-card__actions-section">
                <a class="anime-card__action anime-card__action--details" href="/details">Описание</a>
            </div>
            <div class="anime-card__actions-section" data-favorite-section>
                <button
                    class="anime-card__action anime-card__action--favorite anime-card__favorite"
                    type="button"
                    data-favorite-placeholder
                >
                    В избранное
                </button>
            </div>
        </div>
    </div>
    <button class="anime-list__more" type="button" data-load-more hidden>
        <span class="material-symbols-outlined" aria-hidden="true">refresh</span>
        Показать ещё
    </button>
</div>

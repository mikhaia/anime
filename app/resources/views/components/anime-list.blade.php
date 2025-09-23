@php
    $mode = $mode ?? 'favorites';
    $paginator = $paginator ?? null;
    $items = $items ?? collect();

    if ($paginator) {
        $items = collect($paginator->items());
    } elseif (!($items instanceof \Illuminate\Support\Collection)) {
        $items = collect($items);
    }

    $errorMessage = $errorMessage ?? null;
    $searchQuery = isset($searchQuery) ? (string) $searchQuery : '';
    $emptyMessage = $emptyMessage ?? null;

    if ($emptyMessage === null) {
        if ($mode === 'search') {
            $emptyMessage = $searchQuery !== ''
                ? 'По запросу «' . e($searchQuery) . '» ничего не найдено.'
                : 'Укажите поисковый запрос.';
        } elseif (in_array($mode, ['top', 'new'], true)) {
            $emptyMessage = 'Для этой подборки пока нет тайтлов. Загляните позже!';
        } else {
            $emptyMessage = 'Список пока пуст.';
        }
    }
@endphp

<div class="anime-list" data-anime-list data-mode="{{ $mode }}">
    @if($errorMessage)
        <div class="anime-list__status" role="status">
            <span class="anime-list__status-text">{{ $errorMessage }}</span>
        </div>
    @endif

    @if($items->isNotEmpty())
        <div class="anime-grid">
            @foreach($items as $anime)
                @include('components.anime-card', ['anime' => $anime])
            @endforeach
        </div>
    @elseif(!$errorMessage)
        <div class="anime-list__status" role="status">
            <span class="anime-list__status-text">{{ $emptyMessage }}</span>
        </div>
    @endif

    @if($paginator && ($paginator->hasMorePages() || !$paginator->onFirstPage()))
        <nav class="anime-list__pagination" aria-label="Навигация по страницам">
            <div class="anime-list__pagination-controls">
                @if($paginator->onFirstPage())
                    <span class="anime-list__more" aria-disabled="true" tabindex="-1">
                        <x-icon name="chevron-left" class="h-5 w-5" />
                        Назад
                    </span>
                @else
                    <a class="anime-list__more" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <x-icon name="chevron-left" class="h-5 w-5" />
                        Назад
                    </a>
                @endif

                <span class="anime-list__page-indicator">
                    Страница {{ $paginator->currentPage() }}
                    @if($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->lastPage() > 0)
                        из {{ $paginator->lastPage() }}
                    @endif
                </span>

                @if($paginator->hasMorePages())
                    <a class="anime-list__more" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        Вперёд
                        <x-icon name="chevron-right" class="h-5 w-5" />
                    </a>
                @else
                    <span class="anime-list__more" aria-disabled="true" tabindex="-1">
                        Вперёд
                        <x-icon name="chevron-right" class="h-5 w-5" />
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>

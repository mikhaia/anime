@php
    $animeModel = $anime instanceof \App\Models\Anime ? $anime : null;
    $data = $animeModel ? [
        'id' => (int) $animeModel->getKey(),
        'title' => $animeModel->title,
        'title_english' => $animeModel->title_english,
        'poster_url' => $animeModel->poster_url,
        'type' => $animeModel->type,
        'year' => $animeModel->year,
        'episodes_total' => $animeModel->episodes_total,
        'alias' => $animeModel->alias,
    ] : (array) $anime;

    $animeId = isset($data['id']) ? (int) $data['id'] : null;
    $title = $data['title'] ?? 'Без названия';
    $englishTitle = $data['title_english'] ?? null;
    $posterUrl = $data['poster_url'] ?? ($data['poster'] ?? null);
    $type = $data['type'] ?? null;
    $year = $data['year'] ?? null;
    $episodes = $data['episodes_total'] ?? ($data['episodes'] ?? null);
    $alias = $data['alias'] ?? null;

    $metaParts = array_filter([
        is_string($type) && trim($type) !== '' ? $type : null,
        $year ? (string) $year : null,
        $episodes ? $episodes . ' эп.' : null,
    ]);

    $identifier = $alias ?: ($animeId !== null ? (string) $animeId : null);
    $watchUrl = $identifier ? url('/watch/' . $identifier) : '#';
    $detailsUrl = url('/details');

    $payload = [
        'id' => $animeId,
        'title' => $title,
        'title_english' => $englishTitle,
        'poster' => $posterUrl,
        'type' => $type,
        'year' => $year,
        'episodes' => $episodes,
        'alias' => $alias,
    ];
@endphp

<article class="anime-card" data-anime-card @if($animeId !== null) data-anime-id="{{ $animeId }}" @endif>
    <a
        class="anime-card__link"
        href="{{ $watchUrl }}"
        data-anime-card-trigger
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="Открыть варианты действий для «{{ $title }}»"
    >
        @if($posterUrl)
            <img
                class="anime-card__image"
                src="{{ $posterUrl }}"
                alt="Постер аниме «{{ $title }}»"
                loading="lazy"
                decoding="async"
            >
        @else
            <div class="anime-card__placeholder">Нет постера</div>
        @endif
        <div class="anime-card__overlay">
            <h3 class="anime-card__title">{{ $title }}</h3>
            @if(!empty($metaParts))
                <p class="anime-card__meta">{{ implode(' • ', $metaParts) }}</p>
            @endif
        </div>
    </a>
    <div class="anime-card__actions" data-anime-card-actions aria-hidden="true">
        <div class="anime-card__actions-section">
            <a class="anime-card__action anime-card__action--watch" href="{{ $watchUrl }}">Смотреть</a>
        </div>
        <div class="anime-card__actions-section">
            <a class="anime-card__action anime-card__action--details" href="{{ $detailsUrl }}">Описание</a>
        </div>
        <div class="anime-card__actions-section">
            <button
                class="anime-card__action anime-card__action--favorite anime-card__favorite @if(!empty($isFavorite)) anime-card__favorite--active @endif"
                type="button"
                data-favorite-button
                @if($animeId !== null) data-anime-id="{{ $animeId }}" @endif
                data-anime-payload='@json($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)'
                aria-pressed="{{ !empty($isFavorite) ? 'true' : 'false' }}"
                aria-label="{{ !empty($isFavorite) ? 'Удалить из избранного' : 'Добавить в избранное' }}"
            >
                <span class="material-symbols-outlined anime-card__favorite-icon" data-favorite-icon>favorite</span>
                <span class="anime-card__favorite-text" data-favorite-text>
                    {{ !empty($isFavorite) ? 'В избранном' : 'В избранное' }}
                </span>
            </button>
        </div>
    </div>
</article>

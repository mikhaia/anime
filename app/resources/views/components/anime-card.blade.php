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
        aria-label="Смотреть «{{ $title }}»"
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
</article>

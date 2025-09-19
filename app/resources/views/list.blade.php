@extends('layouts.app')

@section('title', 'NeAnime · Каталог')

@php
    $mode = $mode ?? 'favorites';
    $searchQuery = isset($searchQuery) ? trim((string) $searchQuery) : '';
    $titles = [
        'favorites' => 'Избранные тайтлы',
        'top' => 'Лучшее за сезон',
        'new' => 'Свежие новинки',
        'search' => 'Результаты поиска'
    ];
    $subtitles = [
        'favorites' => 'Подборка тайтлов, к которым вы возвращаетесь снова и снова.',
        'top' => 'Самые просматриваемые и высоко оценённые сериалы.',
        'new' => 'Последние релизы, за которыми стоит следить.',
        'search' => $searchQuery !== ''
            ? "По запросу «{$searchQuery}»."
            : 'Найдите любимые тайтлы по названию, жанру или году выхода.'
    ];
    $title = $titles[$mode] ?? $titles['favorites'];
    $subtitle = $subtitles[$mode] ?? $subtitles['favorites'];
@endphp

@section('content')
    <header class="page-header">
        <h1 class="page-title">{{ $title }}</h1>
        <p class="page-subtitle">{{ $subtitle }}</p>
    </header>
    <section class="page-content @if(in_array($mode, ['top', 'new', 'search'])) page-content--wide @endif">
        @if($mode === 'search')
            @include('components.anime-list', ['mode' => 'search', 'searchQuery' => $searchQuery])
        @elseif(in_array($mode, ['top', 'new']))
            @include('components.anime-list', ['mode' => $mode])
        @elseif($mode === 'favorites')
            @if(!$currentUser)
                <p>Авторизуйтесь, чтобы добавлять аниме в избранное и быстро находить любимые тайтлы.</p>
            @else
                @php
                    /** @var \Illuminate\Support\Collection<int, \App\Models\Favorite> $favorites */
                    $favorites = $favorites ?? collect();
                    $hasFavorites = $favorites->isNotEmpty();
                @endphp
                <div class="anime-grid" data-favorites-list @if(!$hasFavorites) hidden @endif>
                    @foreach($favorites as $favorite)
                        @php $anime = $favorite->anime; @endphp
                        @continue(!$anime)
                        @php
                            $metaParts = array_filter([
                                $anime->type,
                                $anime->year ? (string) $anime->year : null,
                                $anime->episodes_total ? $anime->episodes_total . ' эп.' : null,
                            ]);
                            $payload = [
                                'id' => (int) $anime->getKey(),
                                'title' => $anime->title,
                                'poster' => $anime->poster_url,
                                'type' => $anime->type,
                                'year' => $anime->year,
                                'episodes' => $anime->episodes_total,
                                'alias' => $anime->alias,
                            ];
                        @endphp
                        <article class="anime-card" data-anime-card data-anime-id="{{ $anime->getKey() }}">
                            @php
                                $watchUrl = url('/watch/' . ($anime->alias ?: $anime->getKey()));
                                $detailsUrl = url('/details');
                            @endphp
                            <a
                                class="anime-card__link"
                                href="{{ $watchUrl }}"
                                data-anime-card-trigger
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-label="Открыть варианты действий для «{{ $anime->title }}»"
                            >
                                @if($anime->poster_url)
                                    <img class="anime-card__image" src="{{ $anime->poster_url }}"
                                         alt="Постер аниме «{{ $anime->title }}»" loading="lazy" decoding="async">
                                @else
                                    <div class="anime-card__placeholder">Нет постера</div>
                                @endif
                                <div class="anime-card__overlay">
                                    <h3 class="anime-card__title">{{ $anime->title }}</h3>
                                    @if(!empty($metaParts))
                                        <p class="anime-card__meta">{{ implode(' • ', $metaParts) }}</p>
                                    @endif
                                </div>
                            </a>
                            <div class="anime-card__actions" data-anime-card-actions aria-hidden="true">
                                <a class="anime-card__action anime-card__action--watch" href="{{ $watchUrl }}">Смотреть</a>
                                <button
                                    class="anime-card__action anime-card__action--favorite anime-card__favorite anime-card__favorite--active"
                                    type="button"
                                    data-favorite-button
                                    data-anime-id="{{ $anime->getKey() }}"
                                    data-anime-payload='@json($payload, JSON_UNESCAPED_UNICODE)'
                                >
                                    <span class="material-symbols-outlined anime-card__favorite-icon" data-favorite-icon>favorite</span>
                                    <span class="anime-card__favorite-text" data-favorite-text>Удалить из избранного</span>
                                </button>
                                <a class="anime-card__action anime-card__action--details" href="{{ $detailsUrl }}">Описание</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <p class="anime-list__status-text" data-favorites-empty @if($hasFavorites) hidden @endif>
                    Добавьте понравившиеся тайтлы в избранное, чтобы они всегда были под рукой.
                </p>
            @endif
        @else
            <p>Мы готовим подборку для этого раздела. Загляните позже, чтобы увидеть новые тайтлы.</p>
        @endif
    </section>
@endsection

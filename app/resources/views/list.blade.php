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
            @include('components.anime-list', [
                'mode' => 'search',
                'paginator' => $searchPaginator ?? null,
                'searchQuery' => $searchQuery,
            ])
        @elseif(in_array($mode, ['top', 'new']))
            @include('components.anime-list', [
                'mode' => $mode,
                'paginator' => $catalogPaginator ?? null,
                'errorMessage' => $catalogMessage ?? null,
            ])
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
                        @include('components.anime-card', ['anime' => $anime, 'isFavorite' => true])
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


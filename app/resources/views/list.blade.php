@extends('layouts.app')

@section('title', 'NeAnime · Каталог')

@php
    $mode = $mode ?? 'favorites';
    $titles = [
        'favorites' => 'Избранные тайтлы',
        'top' => 'Лучшее за сезон',
        'new' => 'Свежие новинки'
    ];
    $subtitles = [
        'favorites' => 'Подборка тайтлов, к которым вы возвращаетесь снова и снова.',
        'top' => 'Самые просматриваемые и высоко оценённые сериалы.',
        'new' => 'Последние релизы, за которыми стоит следить.'
    ];
    $title = $titles[$mode] ?? $titles['favorites'];
    $subtitle = $subtitles[$mode] ?? $subtitles['favorites'];
@endphp

@section('content')
    <header class="page-header">
        <h1 class="page-title">{{ $title }}</h1>
        <p class="page-subtitle">{{ $subtitle }}</p>
    </header>
    <section class="page-content @if(in_array($mode, ['top', 'new'])) page-content--wide @endif">
        @if(in_array($mode, ['top', 'new']))
            <div class="anime-list" data-anime-list data-mode="{{ $mode }}">
                <div class="anime-list__status" data-anime-status role="status">
                    <span class="anime-list__status-spinner" aria-hidden="true"></span>
                    <span class="anime-list__status-text">Загружаем подборку…</span>
                </div>
                <div class="anime-grid" data-anime-grid hidden></div>
                <button class="anime-list__more" type="button" data-load-more hidden>
                    <span class="material-symbols-outlined" aria-hidden="true">refresh</span>
                    Показать ещё
                </button>
            </div>
        @else
            <p>Здесь будет отображён список аниме с фильтрами, сортировками и удобной навигацией. Для каждого режима можно вывести отдельные подборки и карточки тайтлов.</p>
        @endif
    </section>
@endsection

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
    <section class="page-content">
        <p>Здесь будет отображён список аниме с фильтрами, сортировками и удобной навигацией. Для каждого режима можно вывести отдельные подборки и карточки тайтлов.</p>
    </section>
@endsection

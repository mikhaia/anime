@extends('layouts.app')

@section('title', 'NeAnime · Поиск')

@section('content')
    <header class="page-header">
        <h1 class="page-title">Поиск аниме</h1>
        <p class="page-subtitle">Найдите любимые тайтлы по названию, жанру или году выхода.</p>
    </header>
    <section class="page-content page-content--wide">
        <form class="search-form" data-anime-search-form>
            <label class="search-form__label" for="anime-search">Название аниме</label>
            <div class="search-form__controls">
                <div class="search-form__input-wrapper">
                    <span class="material-symbols-outlined" aria-hidden="true">search</span>
                    <input class="search-form__input" type="search" id="anime-search" name="search"
                           placeholder="Например, Наруто, One Piece или Восхождение героя щита"
                           autocomplete="off" data-anime-search-input required>
                </div>
                <button class="search-form__button" type="submit">
                    Найти
                </button>
            </div>
        </form>
        <div class="search-results">
            <h2 class="search-results__title">Результаты поиска</h2>
            @include('components.anime-list', ['mode' => 'search'])
        </div>
    </section>
@endsection

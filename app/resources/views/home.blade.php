@extends('layouts.app')

@section('title', 'NeAnime · Поиск')

@section('content')
    <header class="page-header">
        <h1 class="page-title">Поиск аниме</h1>
        <p class="page-subtitle">Найдите любимые тайтлы по названию, жанру или году выхода.</p>
    </header>
    <section class="page-content page-content--wide overflow-visible">
        <form
            class="mx-auto w-full max-w-3xl space-y-3"
            action="{{ url('/list') }}"
            method="GET"
            data-anime-search-form
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1" data-anime-search>
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400">
                        <x-icon name="search" class="h-6 w-6" />
                    </div>
                    <input
                        class="block w-full rounded-lg border border-slate-700 bg-slate-900 py-3 ps-12 pe-4 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        type="search"
                        id="anime-search"
                        name="search"
                        placeholder="Введите название аниме. Например, Наруто, One Piece или Восхождение героя щита..."
                        autocomplete="off"
                        value="{{ $searchQuery ?? '' }}"
                        required
                        data-anime-search-input
                    >
                    <div
                        class="absolute inset-x-0 top-full z-20 mt-2 hidden max-h-96 overflow-auto rounded-xl border border-slate-700 bg-slate-900/95 shadow-2xl"
                        data-anime-search-suggestions
                        role="listbox"
                        hidden
                    ></div>
                </div>
                <button
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-900/60 sm:w-auto"
                    type="submit"
                >
                    Найти
                </button>
            </div>
            <input type="hidden" name="mode" value="search">
        </form>
    </section>
    
    <section class="page-content--wide m-auto mt-5">
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-5 rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-xl">
                <div>
                    <h2 class="text-lg font-semibold text-slate-100">Недавно смотрели</h2>
                    <p class="text-sm text-slate-400">Тайтлы, которые вы открывали последними.</p>
                </div>
                <div class="space-y-4">
                    @if(!$currentUser)
                        <p class="text-sm text-slate-400">Войдите, чтобы видеть историю просмотров.</p>
                    @else
                        @forelse($recentWatchProgress as $progress)
                            @php
                                $anime = $progress->anime;
                                $identifier = $anime?->alias ?: ($anime?->getKey());
                            @endphp
                            @if($anime && $identifier)
                                <a
                                    class="flex gap-4 rounded-2xl border border-slate-800 bg-slate-900/70 p-4 transition hover:border-blue-500/60 hover:bg-slate-900"
                                    href="{{ url('/watch/' . $identifier) }}"
                                >
                                    <div class="flex h-48 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-800">
                                        @if($anime->poster_url)
                                            <img
                                                class="h-full w-full object-cover"
                                                src="{{ $anime->poster_url }}"
                                                alt="Постер аниме «{{ $anime->title }}»"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        @else
                                            <span class="px-2 text-center text-xs text-slate-400">Нет постера</span>
                                        @endif
                                    </div>
                                    <div class="flex min-w-0 flex-1 flex-col justify-center gap-1">
                                        <h3 class="truncate text-sm font-semibold text-slate-100">{{ $anime->title }}</h3>
                                        <p class="text-xs text-slate-400">Последний просмотренный эпизод: {{ $progress->episode_number }}</p>
                                        @if($progress->updated_at)
                                            <p class="text-xs text-slate-500">Обновлено {{ $progress->updated_at->format('d.m.Y H:i') }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endif
                        @empty
                            <p class="text-sm text-slate-400">Здесь появятся тайтлы, которые вы начнёте смотреть.</p>
                        @endforelse
                    @endif
                </div>
            </div>
            <div class="space-y-5 rounded-3xl border border-slate-800 bg-slate-900/70 p-6 shadow-xl">
                <div>
                    <h2 class="text-lg font-semibold text-slate-100">Недавно в избранном</h2>
                    <p class="text-sm text-slate-400">Тайтлы, которые вы добавили в избранное последними.</p>
                </div>
                <div class="space-y-4">
                    @if(!$currentUser)
                        <p class="text-sm text-slate-400">Войдите, чтобы управлять избранным.</p>
                    @else
                        @forelse($recentFavorites as $favorite)
                            @php
                                $anime = $favorite->anime;
                                $identifier = $anime?->alias ?: ($anime?->getKey());
                            @endphp
                            @if($anime && $identifier)
                                <a
                                    class="flex gap-4 rounded-2xl border border-slate-800 bg-slate-900/70 p-4 transition hover:border-blue-500/60 hover:bg-slate-900"
                                    href="{{ url('/watch/' . $identifier) }}"
                                >
                                    <div class="flex h-48 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-800">
                                        @if($anime->poster_url)
                                            <img
                                                class="h-full w-full object-cover"
                                                src="{{ $anime->poster_url }}"
                                                alt="Постер аниме «{{ $anime->title }}»"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        @else
                                            <span class="px-2 text-center text-xs text-slate-400">Нет постера</span>
                                        @endif
                                    </div>
                                    <div class="flex min-w-0 flex-1 flex-col justify-center gap-1">
                                        <h3 class="truncate text-sm font-semibold text-slate-100">{{ $anime->title }}</h3>
                                        @if($anime->year || $anime->type)
                                            <p class="text-xs text-slate-400">
                                                {{ implode(' • ', array_filter([$anime->type, $anime->year])) }}
                                            </p>
                                        @endif
                                        @if($favorite->created_at)
                                            <p class="text-xs text-slate-500">Добавлено {{ $favorite->created_at->format('d.m.Y H:i') }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endif
                        @empty
                            <p class="text-sm text-slate-400">Добавляйте тайтлы в избранное, и они появятся здесь.</p>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection

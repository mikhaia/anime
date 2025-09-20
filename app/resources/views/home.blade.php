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
                        <span class="material-symbols-outlined text-2xl" aria-hidden="true">search</span>
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
@endsection

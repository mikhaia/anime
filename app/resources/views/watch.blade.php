@extends('layouts.app')

@php
    /** @var \App\Models\Anime $anime */
    $activeEpisode = $activeEpisode ?? ($episodes[0] ?? null);
    $englishTitle = is_string($anime->title_english) ? trim($anime->title_english) : '';
    $favoritePayload = [
        'id' => (int) $anime->getKey(),
        'title' => $anime->title,
        'title_english' => $englishTitle !== '' ? $englishTitle : null,
        'poster' => $anime->poster_url,
        'type' => $anime->type,
        'year' => $anime->year,
        'episodes' => $anime->episodes_total,
        'alias' => $anime->alias,
    ];
@endphp

@section('title', 'NeAnime · Просмотр — ' . ($anime->title ?? 'Аниме'))

@section('content')
    <header class="page-header">
        <h1 class="page-title">{{ $anime->title }}</h1>
        @if($englishTitle !== '' && $englishTitle !== $anime->title)
            <p class="page-subtitle">{{ $englishTitle }}</p>
        @endif
    </header>
    <section class="page-content page-content--wide">
        @if($activeEpisode)
            <div
                class="watch-layout"
                data-watch-anime
                data-anime-id="{{ $anime->getKey() }}"
                data-active-episode-number="{{ $activeEpisode['number'] ?? '' }}"
            >
                <div class="watch-player">
                    <div class="flex justify-between">
                        <h2 class="watch-player__title" data-watch-title>
                            {{ sprintf('%02d. %s', $activeEpisode['number'], $activeEpisode['title']) }}
                        </h2>
                        <div class="watch-player__controls">
                            <div class="watch-player__quality">
                                <div class="watch-player__quality-select-wrapper">
                                    <select
                                        id="watch-quality-select"
                                        class="watch-player__quality-select"
                                        data-watch-quality
                                        aria-label="Выберите качество воспроизведения"
                                        @if(count($activeEpisode['streams'] ?? []) <= 1) disabled @endif
                                    >
                                        @foreach($activeEpisode['streams'] ?? [] as $quality => $url)
                                            <option
                                                value="{{ $quality }}"
                                                @if(($activeEpisode['default_quality'] ?? null) === $quality) selected @endif
                                            >
                                                {{ $quality }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="watch-player__quality-select-icon" aria-hidden="true">
                                        <svg class="watch-player__quality-select-icon-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m6 8 4 4 4-4" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-100 transition-colors duration-150 hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-500/40 disabled:cursor-not-allowed disabled:opacity-60"
                                data-favorite-button
                                data-anime-id="{{ $anime->getKey() }}"
                                data-anime-payload='@json($favoritePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)'
                                aria-pressed="false"
                                aria-label="Добавить в избранное"
                            >
                                <span
                                    class="material-symbols-outlined anime-card__favorite-icon text-base"
                                    data-favorite-icon
                                    aria-hidden="true"
                                >favorite</span>
                                <span class="text-sm font-semibold" data-favorite-text>
                                    В избранное
                                </span>
                            </button>
                        </div>
                    </div>
                    <div class="watch-player__video-wrapper">
                        <video
                            class="watch-player__video"
                            controls
                            playsinline
                            preload="none"
                            data-watch-player
                            @if($anime->poster_url)
                                poster="{{ $anime->poster_url }}"
                            @endif
                        >
                            Ваш браузер не поддерживает воспроизведение HLS. Попробуйте использовать современный браузер.
                        </video>
                    </div>
                    <div class="watch-player__meta">
                                @if(!empty($seasons) && count($seasons) > 1)
            <nav class="mb-8" aria-label="Сезоны">
                <div class="rounded-3xl border border-slate-700/60 bg-slate-900/70 p-6 shadow-lg shadow-slate-950/30 backdrop-blur">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-white">Сезоны</h3>
                    </div>
                    <div class="mt-4 flex flex-col gap-3">
                        @foreach($seasons as $season)
                            <a
                                href="{{ url('/watch/' . rawurlencode($season['identifier'])) }}"
                                @class([
                                    'group inline-flex min-w-[12rem] flex-1 flex-col gap-1 rounded-2xl border px-4 py-3 text-left shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/70',
                                    'border-blue-500/80 bg-blue-600/25 text-blue-100 shadow-lg shadow-blue-900/40 ring-2 ring-inset ring-blue-500/70' => !empty($season['is_active']),
                                    'border-slate-700/60 bg-slate-800/80 text-slate-200 hover:border-slate-600 hover:bg-slate-800/90 focus-visible:ring-offset-0' => empty($season['is_active']),
                                ])
                                @if(!empty($season['is_active'])) aria-current="page" @endif
                            >
                                <span class="text-base font-semibold tracking-tight">{{ $season['title'] }}</span>
                                @if(!empty($season['relation']) && empty($season['is_active']))
                                    <span class="text-sm font-medium text-slate-400 transition group-hover:text-slate-300">{{ $season['relation'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </nav>
        @endif
                    </div>
                </div>
                <aside class="watch-playlist" aria-label="Плейлист серий">
                    <h3 class="watch-playlist__title">Плейлист</h3>
                    <ul class="watch-playlist__list">
                        @foreach($episodes as $episode)
                            <li>
                                @php
                                    $streamsJson = json_encode($episode['streams'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                @endphp
                                <button
                                    type="button"
                                    class="watch-playlist__item @if($episode['number'] === $activeEpisode['number']) watch-playlist__item--active @endif"
                                    data-episode-item
                                    data-episode-number="{{ $episode['number'] }}"
                                    data-episode-title="{{ e(sprintf('%02d. %s', $episode['number'], $episode['title'])) }}"
                                    data-episode-duration="{{ e($episode['duration']) }}"
                                    data-episode-description="{{ e($episode['description']) }}"
                                    data-episode-stream="{{ e($episode['stream_url']) }}"
                                    @if($streamsJson) data-episode-streams="{{ e($streamsJson) }}" @endif
                                    data-episode-default-quality="{{ e($episode['default_quality'] ?? '') }}"
                                    @if($episode['number'] === $activeEpisode['number']) data-active="true" @endif
                                >
                                    <span class="watch-playlist__number">{{ sprintf('%02d', $episode['number']) }}</span>
                                    <span class="watch-playlist__content">
                                        <span class="watch-playlist__item-title">
                                            {{ sprintf('%02d. %s', $episode['number'], $episode['title']) }}
                                        </span>
                                        <span class="watch-playlist__meta">{{ $episode['duration'] }}</span>
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </aside>
            </div>
        @else
            <p class="watch-empty">Плейлист пока пуст. Загляните позже, чтобы посмотреть новые эпизоды.</p>
        @endif
    </section>
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js" defer></script>
    <script src="/js/watch.js" defer></script>
    <script src="/js/control.js" defer></script>
@endpush
@endsection

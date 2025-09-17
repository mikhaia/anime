@extends('layouts.app')

@php
    /** @var \App\Models\Anime $anime */
    $activeEpisode = $activeEpisode ?? ($episodes[0] ?? null);
@endphp

@section('title', 'NeAnime · Просмотр — ' . ($anime->title ?? 'Аниме'))

@section('content')
    <header class="page-header">
        <h1 class="page-title">Просмотр: {{ $anime->title }}</h1>
        <p class="page-subtitle">Выберите серию в плейлисте справа и наслаждайтесь просмотром с поддержкой HLS.</p>
    </header>
    <section class="page-content page-content--wide">
        @if($activeEpisode)
            <div class="watch-layout">
                <div class="watch-player">
                    <div class="watch-player__video-wrapper">
                        <video
                            class="watch-player__video"
                            controls
                            playsinline
                            preload="metadata"
                            data-watch-player
                            @if($anime->poster_url)
                                poster="{{ $anime->poster_url }}"
                            @endif
                        >
                            Ваш браузер не поддерживает воспроизведение HLS. Попробуйте использовать современный браузер.
                        </video>
                    </div>
                    <div class="watch-player__meta">
                        <h2 class="watch-player__title" data-watch-title>{{ $activeEpisode['title'] }}</h2>
                        <p class="watch-player__subtitle">
                            {{ $anime->title }}
                            <span class="watch-player__dot" aria-hidden="true">•</span>
                            <span data-watch-duration>{{ $activeEpisode['duration'] }}</span>
                        </p>
                        <p class="watch-player__description" data-watch-description>{{ $activeEpisode['description'] }}</p>
                    </div>
                </div>
                <aside class="watch-playlist" aria-label="Плейлист серий">
                    <h3 class="watch-playlist__title">Плейлист</h3>
                    <ul class="watch-playlist__list">
                        @foreach($episodes as $episode)
                            <li>
                                <button
                                    type="button"
                                    class="watch-playlist__item @if($episode['number'] === $activeEpisode['number']) watch-playlist__item--active @endif"
                                    data-episode-item
                                    data-episode-number="{{ $episode['number'] }}"
                                    data-episode-title="{{ e($episode['title']) }}"
                                    data-episode-duration="{{ e($episode['duration']) }}"
                                    data-episode-description="{{ e($episode['description']) }}"
                                    data-episode-stream="{{ e($episode['stream_url']) }}"
                                    @if($episode['number'] === $activeEpisode['number']) data-active="true" @endif
                                >
                                    <span class="watch-playlist__number">{{ sprintf('%02d', $episode['number']) }}</span>
                                    <span class="watch-playlist__content">
                                        <span class="watch-playlist__item-title">{{ $episode['title'] }}</span>
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
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const player = document.querySelector('[data-watch-player]');
            const playlistItems = Array.from(document.querySelectorAll('[data-episode-item]'));

            if (!player || playlistItems.length === 0) {
                return;
            }

            const titleElement = document.querySelector('[data-watch-title]');
            const descriptionElement = document.querySelector('[data-watch-description]');
            const durationElement = document.querySelector('[data-watch-duration]');
            let hlsInstance = null;

            function detachHls() {
                if (hlsInstance) {
                    hlsInstance.destroy();
                    hlsInstance = null;
                }
            }

            function loadStream(url) {
                if (!url) {
                    return;
                }

                detachHls();

                if (player.canPlayType('application/vnd.apple.mpegurl')) {
                    player.src = url;
                    player.load();
                } else if (window.Hls) {
                    hlsInstance = new Hls();
                    hlsInstance.loadSource(url);
                    hlsInstance.attachMedia(player);
                } else {
                    player.src = url;
                    player.load();
                }
            }

            function setActive(item) {
                playlistItems.forEach((entry) => {
                    entry.classList.toggle('watch-playlist__item--active', entry === item);
                });

                const { episodeTitle, episodeDescription, episodeDuration, episodeStream } = item.dataset;

                if (titleElement) {
                    titleElement.textContent = episodeTitle || '';
                }

                if (descriptionElement) {
                    descriptionElement.textContent = episodeDescription || '';
                }

                if (durationElement) {
                    durationElement.textContent = episodeDuration || '';
                }

                loadStream(episodeStream);
            }

            playlistItems.forEach((item) => {
                item.addEventListener('click', () => {
                    setActive(item);
                    player.play().catch(() => {});
                });
            });

            const initiallyActive = playlistItems.find((item) => item.dataset.active === 'true') || playlistItems[0];
            if (initiallyActive) {
                setActive(initiallyActive);
            }
        });
    </script>
@endsection

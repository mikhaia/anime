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
            <div
                class="watch-layout"
                data-watch-anime
                data-anime-id="{{ $anime->getKey() }}"
                data-active-episode-number="{{ $activeEpisode['number'] ?? '' }}"
            >
                <div class="watch-player">
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
                        <h2 class="watch-player__title" data-watch-title>
                            {{ sprintf('%02d. %s', $activeEpisode['number'], $activeEpisode['title']) }}
                        </h2>
                        <p class="watch-player__subtitle">
                            {{ $anime->title }}
                            <span class="watch-player__dot" aria-hidden="true">•</span>
                            <span data-watch-duration>{{ $activeEpisode['duration'] }}</span>
                        </p>
                        <p class="watch-player__description" data-watch-description>{{ $activeEpisode['description'] }}</p>
                        <div class="watch-player__controls">
                            <label class="watch-player__quality">
                                <span class="watch-player__quality-label">Качество</span>
                                <select
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
                            </label>
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.7/dist/hls.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const player = document.querySelector('[data-watch-player]');
            const playlistItems = Array.from(document.querySelectorAll('[data-episode-item]'));
            const watchContainer = document.querySelector('[data-watch-anime]');
            const animeId = watchContainer?.dataset?.animeId;
            const initialEpisodeNumber = Number.parseInt(watchContainer?.dataset?.activeEpisodeNumber ?? '', 10);
            let isAuthenticated = document.body?.dataset?.authenticated === 'true';
            let lastSavedEpisode = Number.isFinite(initialEpisodeNumber) ? initialEpisodeNumber : null;
            let saveInFlight = null;
            const qualitySelect = document.querySelector('[data-watch-quality]');
            let preferredQuality = qualitySelect?.value ?? null;

            if (!player || playlistItems.length === 0) {
                return;
            }

            const titleElement = document.querySelector('[data-watch-title]');
            const descriptionElement = document.querySelector('[data-watch-description]');
            const durationElement = document.querySelector('[data-watch-duration]');
            let hlsInstance = null;

            function normalizeStreams(streams) {
                if (!streams || typeof streams !== 'object') {
                    return {};
                }

                const entries = Object.entries(streams)
                    .filter((entry) => {
                        const [quality, url] = entry;
                        return typeof quality === 'string' && quality.trim() !== '' && typeof url === 'string' && url.trim() !== '';
                    })
                    .map(([quality, url]) => [quality.trim(), url.trim()]);

                if (entries.length === 0) {
                    return {};
                }

                entries.sort((left, right) => {
                    const parseQuality = (value) => {
                        const match = String(value).match(/(\d+)/);
                        return match ? Number.parseInt(match[1], 10) : 0;
                    };

                    return parseQuality(right[0]) - parseQuality(left[0]);
                });

                const normalized = {};
                entries.forEach(([quality, url]) => {
                    normalized[quality] = url;
                });

                return normalized;
            }

            function updateQualityOptions(streams, defaultQuality) {
                if (!qualitySelect) {
                    return null;
                }

                const normalizedStreams = normalizeStreams(streams);
                const qualities = Object.keys(normalizedStreams);

                qualitySelect.innerHTML = '';

                qualities.forEach((quality) => {
                    const option = document.createElement('option');
                    option.value = quality;
                    option.textContent = quality;
                    qualitySelect.append(option);
                });

                if (qualities.length <= 1) {
                    qualitySelect.disabled = true;
                } else {
                    qualitySelect.disabled = false;
                }

                const desiredQuality = (() => {
                    if (preferredQuality && normalizedStreams[preferredQuality]) {
                        return preferredQuality;
                    }

                    if (defaultQuality && normalizedStreams[defaultQuality]) {
                        return defaultQuality;
                    }

                    return qualities[0] ?? null;
                })();

                if (desiredQuality) {
                    qualitySelect.value = desiredQuality;
                }

                return desiredQuality ? normalizedStreams[desiredQuality] : null;
            }

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

                const {
                    episodeTitle,
                    episodeDescription,
                    episodeDuration,
                    episodeStream,
                    episodeStreams,
                    episodeDefaultQuality,
                } = item.dataset;
                const episodeNumber = Number.parseInt(item.dataset.episodeNumber ?? '', 10);
                let selectedStream = episodeStream || '';

                if (episodeStreams) {
                    try {
                        const parsed = JSON.parse(episodeStreams);
                        const stream = updateQualityOptions(parsed, episodeDefaultQuality || null);
                        if (stream) {
                            selectedStream = stream;
                        }
                    } catch (error) {
                        console.warn('Failed to parse episode streams', error);
                        updateQualityOptions({}, null);
                    }
                } else {
                    updateQualityOptions({}, null);
                }

                if (titleElement) {
                    titleElement.textContent = episodeTitle || '';
                }

                if (descriptionElement) {
                    descriptionElement.textContent = episodeDescription || '';
                }

                if (durationElement) {
                    durationElement.textContent = episodeDuration || '';
                }

                loadStream(selectedStream);
                rememberEpisode(episodeNumber);
            }

            function rememberEpisode(episodeNumber) {
                if (!animeId || !Number.isFinite(episodeNumber)) {
                    return;
                }

                if (!isAuthenticated) {
                    return;
                }

                if (episodeNumber === lastSavedEpisode && !saveInFlight) {
                    return;
                }

                if (saveInFlight) {
                    saveInFlight.abort();
                    saveInFlight = null;
                }

                const controller = new AbortController();
                saveInFlight = controller;

                fetch('/watch-progress', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        anime_id: Number.parseInt(animeId, 10),
                        episode_number: episodeNumber,
                    }),
                    signal: controller.signal,
                })
                    .then((response) => {
                        if (response.status === 401) {
                            isAuthenticated = false;
                            return null;
                        }

                        if (!response.ok) {
                            throw new Error(`Request failed with status ${response.status}`);
                        }

                        return response.json();
                    })
                    .then((payload) => {
                        if (!payload) {
                            return;
                        }

                        if (Number.isFinite(payload?.progress?.episode_number)) {
                            lastSavedEpisode = payload.progress.episode_number;
                        } else {
                            lastSavedEpisode = episodeNumber;
                        }
                    })
                    .catch((error) => {
                        if (error.name !== 'AbortError') {
                            console.warn('Failed to save watch progress', error);
                        }
                    })
                    .finally(() => {
                        if (saveInFlight === controller) {
                            saveInFlight = null;
                        }
                    });
            }

            playlistItems.forEach((item) => {
                item.addEventListener('click', () => {
                    setActive(item);
                });
            });

            if (qualitySelect) {
                qualitySelect.addEventListener('change', () => {
                    const currentItem = playlistItems.find((item) => item.classList.contains('watch-playlist__item--active'));
                    preferredQuality = qualitySelect.value || null;

                    if (!currentItem) {
                        return;
                    }

                    const { episodeStreams } = currentItem.dataset;
                    if (!episodeStreams) {
                        return;
                    }

                    try {
                        const parsed = JSON.parse(episodeStreams);
                        const normalized = normalizeStreams(parsed);
                        const selectedQuality = qualitySelect.value;
                        const streamUrl = normalized[selectedQuality];

                        if (streamUrl) {
                            loadStream(streamUrl);
                        }
                    } catch (error) {
                        console.warn('Failed to switch quality', error);
                    }
                });
            }
        });
    </script>
@endsection

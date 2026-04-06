@extends('layouts.lite')

@section('content')
    <link rel="stylesheet" href="/lite/video.css">

    <div class="screen">
        <div class="title">
            <div><button onclick="window.history.back()">&laquo; Назад</button></div>
            <h1>
                {{ $anime->title }}
                <a href="#" class="fav-toggle @if ($favorited) favorited @endif"
                    data-anime-id="{{ $anime->id }}" title="Добавить в избранное">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M12 4.248c-3.148-5.402-12-3.825-12 2.944 0 4.661 5.571 9.427 12 15.808 6.429-6.381 12-11.147 12-15.808 0-6.769-8.852-8.346-12-2.944z" />
                    </svg>
                </a>
                <small>{{ $anime->title_english }}</small>
            </h1>
        </div>
        <div class="video">
            <a class="video-cover"
                style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.8)), url(/{{ $anime->poster }});">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="200" height="200" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M8.6 5.2A1 1 0 0 0 7 6v12a1 1 0 0 0 1.6.8l8-6a1 1 0 0 0 0-1.6l-8-6Z"
                        clip-rule="evenodd" />
                </svg>
            </a>
            <video id="player" controls>
                Ваш браузер не поддерживает воспроизведение HLS.
            </video>
        </div>
    </div>

    <div class="video-controls">
        <div>
            <select id="select-episode">
                <option value="">Выбрать серию</option>
                @foreach ($anime->episodes as $episode)
                    <option value="{{ $episode->number }}">
                        {{ $episode->number }}.
                        {{ $episode->title ?: 'Серия ' . $episode->number }}
                    </option>
                @endforeach
            </select>
            @if ($anime->episodes->count() > 1)
                <div class="episode-navigation">
                    <button id="btn-prev-episode">Предыдущая серия</button>
                    <button id="btn-next-episode">Следующая серия</button>
                </div>
            @endif
        </div>
        <div>
            <select id="select-season">
                <option>Выбрать сезон</option>
                @foreach ($anime->relates as $r)
                    <option value="{{ $r->relate_id }}" @if ($r->relate_id == $anime->id) selected @endif>
                        {{ $r->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select id="select-quality">
                @foreach ($qualities as $q)
                    <option value="{{ $q }}">{{ $q }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="video-controls">
        <div class="genre-list">
            <h3>Жанры:</h3>
            @foreach ($anime->genres as $g)
                <a href="/genre/{{ $g->id }}" class="tag">{{ $g->name }}</a>
            @endforeach
        </div>
    </div>

    @php
        $episodes = [];
        foreach ($anime->streams as $s) {
            $episodes[(int) $s->episode_id][(int) $s->quality] = $s->url;
        }
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        const video = document.getElementById('player');
        const videoContainer = document.querySelector('.video');
        let hls = null;
        let quality;
        let episode;
        let shouldAutoplayEpisode = false;
        const episodes = JSON.parse('{!! json_encode($episodes, JSON_UNESCAPED_SLASHES) !!}');
        const animeId = {{ $anime->id }};

        function selectVideo(url, play = false) {
            // 🧹 очистка предыдущего
            if (hls) {
                hls.detachMedia();
                hls.destroy();
                hls = null;
            }

            // ❗ сброс video (важно)
            video.pause();
            video.removeAttribute('src');
            video.load();

            if (window.Hls && Hls.isSupported()) {
                hls = new Hls();

                hls.loadSource(url);
                hls.attachMedia(video);

                if (play) {
                    hls.on(Hls.Events.MANIFEST_PARSED, () => {
                        video.play().catch(() => {});
                    });
                }

            } else if (video.canPlayType('application/vnd.apple.mpegURL')) {
                video.src = url;

                if (play) {
                    video.play().catch(() => {});
                }

            } else {
                console.error('HLS не поддерживается этим браузером');
            }
        }

        function saveWatchProgress() {
            if (episode && video.currentTime > 0) {
                $.post('/watch-progress', {
                    anime_id: animeId,
                    episode_number: episode,
                    time: Math.floor(video.currentTime)
                }, function(data) {
                    console.log('Progress saved:', data);
                }).fail(function(error) {
                    console.error('Error saving progress:', error);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            quality = parseInt($('#select-quality').val());

            function updateNavigationButtons() {
                const selectEpisode = $('#select-episode');
                const options = selectEpisode.find('option');
                let currentIndex = -1;

                options.each(function(index) {
                    if ($(this).val() === selectEpisode.val()) {
                        currentIndex = index;
                    }
                });

                $('#btn-prev-episode').prop('disabled', currentIndex <= 1);

                $('#btn-next-episode').prop('disabled', currentIndex >= options.length - 1);
            }

            @if ($progress)
                const savedEpisode = {{ $progress->episode_number }};
                const savedTime = {{ $progress->time }};

                $('#select-episode').val(savedEpisode);
                episode = savedEpisode;

                selectVideo(episodes[episode][quality], false);

                $('.video-cover').hide();

                video.addEventListener('loadedmetadata', function setProgress() {
                    video.currentTime = savedTime;
                    video.removeEventListener('loadedmetadata', setProgress);
                });
            @endif

            updateNavigationButtons();

            function nextEpisode() {
                const selectEpisode = $('#select-episode');
                const options = selectEpisode.find('option');
                let currentIndex = -1;

                options.each(function(index) {
                    if ($(this).val() === selectEpisode.val()) {
                        currentIndex = index;
                    }
                });

                if (currentIndex !== -1 && currentIndex < options.length - 1) {
                    changeEpisodeSelection(currentIndex + 1);
                }
            }

            function changeEpisodeSelection(index, autoplay = true) {
                shouldAutoplayEpisode = autoplay;
                if (autoplay) {
                    $('.video-cover').hide();
                }
                $('#select-episode').prop('selectedIndex', index).change();
            }

            function prevEpisode() {
                const selectEpisode = $('#select-episode');
                const options = selectEpisode.find('option');
                let currentIndex = -1;

                options.each(function(index) {
                    if ($(this).val() === selectEpisode.val()) {
                        currentIndex = index;
                    }
                });

                if (currentIndex > 1) {
                    changeEpisodeSelection(currentIndex - 1);
                }
            }

            function togglePlayback() {
                if (!video.src) {
                    if ($('#select-episode').val()) {
                        const selectedEpisode = parseInt($('#select-episode').val());
                        episode = selectedEpisode;
                        $('.video-cover').hide();
                        selectVideo(episodes[selectedEpisode][quality], true);
                    } else {
                        const firstEpisode = Object.keys(episodes)[0];
                        const firstOption = $('#select-episode option[value="' + firstEpisode + '"]').prop(
                            'index');
                        changeEpisodeSelection(firstOption, true);
                    }
                    return;
                }

                if (video.paused) {
                    $('.video-cover').hide();
                    video.play().catch(() => {});
                    return;
                }

                video.pause();
            }

            function toggleFullscreen() {
                if (document.fullscreenElement) {
                    if (document.exitFullscreen) {
                        document.exitFullscreen().catch(() => {});
                    }
                    return;
                }

                if (videoContainer && videoContainer.requestFullscreen) {
                    videoContainer.requestFullscreen().catch(() => {});
                    return;
                }

                if (video.webkitEnterFullscreen) {
                    video.webkitEnterFullscreen();
                }
            }

            function changeVolume(delta) {
                const nextVolume = Math.max(0, Math.min(1, video.volume + delta));
                video.volume = Math.round(nextVolume * 10) / 10;

                if (video.muted && nextVolume > 0) {
                    video.muted = false;
                }
            }

            function isInteractiveElement(target) {
                return $(target).closest('input, textarea, select, button, a, [contenteditable="true"]').length > 0;
            }

            $('#select-quality').change(function() {
                quality = parseInt($('#select-quality').val());
                selectVideo(episodes[episode][quality]);
            });

            $('#select-episode').change(function() {
                episode = parseInt($(this).val());
                const autoplay = shouldAutoplayEpisode;
                shouldAutoplayEpisode = false;
                selectVideo(episodes[episode][quality], autoplay);
                updateNavigationButtons();
            });

            $('#select-season').change(function() {
                location.href = '/anime/' + $(this).val();
            });

            $('#btn-next-episode').click(function(e) {
                e.preventDefault();
                nextEpisode();
            });

            $('#btn-prev-episode').click(function(e) {
                e.preventDefault();
                prevEpisode();
            });

            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey || e.altKey || isInteractiveElement(e.target)) {
                    return;
                }

                if (e.target === video && [' ', 'Spacebar'].includes(e.key)) {
                    return;
                }

                if (e.repeat && [' ', 'Enter', 'ArrowLeft', 'ArrowRight', 'PageUp', 'PageDown'].includes(e.key)) {
                    return;
                }

                switch (e.key) {
                    case ' ':
                    case 'Spacebar':
                        e.preventDefault();
                        togglePlayback();
                        break;
                    case 'Enter':
                        e.preventDefault();
                        toggleFullscreen();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        changeVolume(0.1);
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        changeVolume(-0.1);
                        break;
                    case 'ArrowLeft':
                    case 'PageUp':
                        e.preventDefault();
                        prevEpisode();
                        break;
                    case 'ArrowRight':
                    case 'PageDown':
                        e.preventDefault();
                        nextEpisode();
                        break;
                }
            });

            @if (!$progress)
                updateNavigationButtons();
            @endif

            setInterval(saveWatchProgress, 60 * 1000); // Сохранять прогресс каждую минуту

            $('.video-cover').click(function() {
                togglePlayback();
            });

            $('.fav-toggle').click(function(e) {
                e.preventDefault();
                const animeId = $(this).data('anime-id');
                const toggle = $(this);
                $.post('/favorite', {
                    anime_id: animeId,
                    favorite: !toggle.hasClass('favorited')
                }, function(data) {
                    if (data.success) {
                        toggle.toggleClass('favorited', data.favorited);
                    } else {
                        alert('Ошибка при обновлении избранного');
                    }
                });
            });
        });
    </script>
@endsection

@extends('layouts.lite')

@section('content')
    <link rel="stylesheet" href="/lite/video.css">

    <div class="screen">
        <div class="title">
            <div><button onclick="window.history.back()">&laquo; Назад</button></div>
            <h1>{{ $anime->title }}</h1>
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

    {{-- {{ dd($anime->streams) }} --}}
    <div class="video-controls">
        <select id="select-episode">
            <option value="">Выбрать серию</option>
            @foreach ($anime->episodes as $episode)
                <option value="{{ $episode->number }}">
                    {{ $episode->number }}.
                    {{ $episode->title ?: 'Серия ' . $episode->number }}
                </option>
            @endforeach
        </select>
        <select id="select-season">
            <option>Выбрать сезон</option>
            @foreach ($anime->relates as $r)
                <option value="{{ $r->relate_id }}">{{ $r->title }}</option>
            @endforeach
        </select>
        <select id="select-quality">
            @foreach ($qualities as $q)
                <option value="{{ $q }}">{{ $q }}</option>
            @endforeach
        </select>
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
        let quality;
        let episode;
        const episodes = JSON.parse('{!! json_encode($episodes, JSON_UNESCAPED_SLASHES) !!}');

        function selectVideo(url, play = false) {
            if (video.canPlayType('application/vnd.apple.mpegURL')) {
                video.src = url;
                if (play) {
                    video.play();
                }
            } else if (window.Hls && Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(url);
                hls.attachMedia(video);
                if (play) {
                    hls.on(Hls.Events.MANIFEST_PARSED, () => video.play());
                }
            } else {
                console.error('HLS не поддерживается этим браузером');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            quality = parseInt($('#select-quality').val());

            $('#select-quality').change(function() {
                quality = parseInt($('#select-quality').val());
            });

            $('#select-episode').change(function() {
                selectVideo(episodes[$(this).val()][quality]);
            });

            $('#select-season').change(function() {
                location.href = '/anime/' + $(this).val();
            });

            $('.video-cover').click(function() {
                $(this).hide();
                if (video.src) {
                    video.play();
                } else {
                    if ($('#select-episode').val()) {
                        video.play();
                    } else {
                        let first = episodes.find(e => e && e[quality]);
                        selectVideo(first[quality], true);
                    }
                }
            });
        });
    </script>
@endsection

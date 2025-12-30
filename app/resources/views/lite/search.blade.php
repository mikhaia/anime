@extends('lite.list')

@section('content')
    <div class="search-page">
        <div class="search-bar">
            <form method="get" action="/search" class="search-form">
                <input type="text" id="search-input" name="query" placeholder="Название аниме" value="{{ $searchQuery }}"
                    inputmode="text" lang="ru" required>
                <ul class="search-suggestions" id="search-suggestions">
                </ul>
                <button type="submit">Искать</button>
            </form>
        </div>

        <div class="genre-list">
            <h3>Жанры:</h3>
            @foreach ($genres as $g)
                <a href="/genre/{{ $g->id }}" class="tag">{{ $g->name }}</a>
            @endforeach
        </div>

        <div class="suggestions">
            <div>
                <h3>Продолжить просмотр:</h3>
                <ul>
                    @forelse ($watchProgress as $progress)
                        @if ($progress->anime)
                            <li>
                                <div>
                                    <a href="/anime/{{ $progress->anime_id }}">
                                        <img src="/{{ $progress->anime->poster }}" alt="{{ $progress->anime->title }}">
                                    </a>
                                    <span>
                                        <a href="/anime/{{ $progress->anime_id }}">{{ $progress->anime->title }}</a>
                                        <br>
                                        <i>Серия {{ $progress->episode_number }}</i>
                                        <i>Продолжить просмотр с {{ gmdate('H:i:s', $progress->time) }}</i>
                                    </span>
                                </div>
                            </li>
                        @endif
                    @empty
                        <li><em>Нет просмотренных аниме</em></li>
                    @endforelse
                </ul>
            </div>
            <div>
                <h3>Избранное:</h3>
                <ul>
                    @forelse ($favorites as $favorite)
                        <li>
                            <div>
                                <a href="/anime/{{ $favorite->anime_id }}">
                                    <img src="/{{ $favorite->anime->poster }}" alt="{{ $favorite->anime->title }}">
                                </a>
                                <span>
                                    <a href="/anime/{{ $favorite->anime_id }}">{{ $favorite->anime->title }}</a>
                                    <br>
                                    <i>Добавлено {{ $favorite->created_at->format('d.m.Y') }}</i>
                                </span>
                            </div>
                        </li>
                    @empty
                        <li><em>Нет добавленных в избранное</em></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @parent
@endsection

@section('scripts')
    <script src="/lite/js/search.js"></script>
@endsection

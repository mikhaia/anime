@extends('lite.list')

@section('content')
    <div class="search-page">
        <div class="search-bar">
            <form method="get" action="/search" class="search-form">
                <input type="text" name="query" placeholder="Название аниме" value="{{ $searchQuery }}" inputmode="text"
                    lang="ru" required>
                <button type="submit">Искать</button>
            </form>
        </div>

        <div class="genre-list">
            <h3>Жанры:</h3>
            @foreach ($genres as $g)
                <a href="/genre/{{ $g->id }}" class="tag">{{ $g->name }}</a>
            @endforeach
        </div>
    </div>

    @parent
@endsection

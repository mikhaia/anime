@extends('lite.list')

@section('content')
<div class="search-page">
    <div class="search-bar">
        <form method="get" action="/search" class="search-form">
            <input type="text" name="query" placeholder="Название аниме" value="{ $searchQuery }}" required>
            <button type="submit">Искать</button>
        </form>
    </div>
</div>

@parent
@endsection
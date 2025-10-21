@extends('layouts.lite')

@section('content')
    <div class="wrapper">
        @if ($searchQuery)
            <h2 class="search-title">Результаты поиска по "{{ $searchQuery }}"</h2>
        @endif

        <div class="list">
            @foreach ($items as $item)
                <a class="item" href="/watch/{{ $item->alias }}">
                    <img src="/{{ $item->poster }}" alt="{{ $item->title }}">
                    <b class="title">{{ $item->title }}</b>
                </a>
            @endforeach
        </div>
    </div>
    {{ $items->links('vendor.pagination.lite') }}
@endsection

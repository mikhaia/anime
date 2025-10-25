@extends('layouts.lite')

@section('content')
@if ($searchQuery)
<h2 class="search-title">Результаты поиска по "{{ $searchQuery }}"</h2>
@endif

<div class="wrapper">
    <div class="list">
        @foreach ($items as $item)
        <a class="item" href="/anime/{{ $item->id }}">
            <img src="/{{ $item->poster }}" alt="{{ $item->title }}">
            <b class="title">{{ $item->title }}</b>
        </a>
        @endforeach
    </div>
</div>

@if (count($items) >= 24)
@include('vendor.pagination.lite')
@endif
@endsection
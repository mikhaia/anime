@extends('layouts.lite')
{{-- {{ dd($items) }} --}}
@section('content')
    <div class="wrapper">
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

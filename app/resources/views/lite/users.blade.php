@extends('layouts.lite')

@section('content')
    <div class="user-screen">
        <div class="user-list">
            @foreach ($users as $u)
                @php
                    $url = '/switch/' . $u->id;
                    if ($u->id === auth()->id()) {
                        $url = '/users/edit';
                    }
                @endphp
                <div>
                    <a href="{{ $url }}">
                        <b>{{ $u->name }}</b>
                    </a>
                    <a href="{{ $url }}">
                        <span class="avatar">
                            <span class="avatar-edit">
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="200" height="200"
                                    fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z" />
                                </svg>
                            </span>
                            @if ($u->avatar_path)
                                <img src="{{ $u->avatar_path }}" alt="{{ $u->name }}" width="200" height="200">
                            @else
                                <svg width="200" height="200" viewBox="0 0 20 20" fill="currentColor"
                                    class="text-gray-400" role="img" aria-label="Default user icon"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M10 9a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.2 0-6 2.1-6 3v1h12v-1c0-.9-1.8-3-6-3Z" />
                                </svg>
                            @endif
                        </span>
                    </a>
                    <i>{{ $u->email }}</i>
                </div>
            @endforeach
            <div>
                <b>Войти</b>
                <a href="#" class="open-auth-modal">
                    <span class="avatar">
                        <svg width="200" height="200" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" class="text-gray-400" role="img" aria-label="Add new user"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke-opacity="0.6" />
                            <path d="M12 6v12M6 12h12" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </a>
            </div>
        </div>
    </div>
@endsection

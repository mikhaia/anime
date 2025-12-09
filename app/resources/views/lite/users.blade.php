@extends('layouts.lite')

@section('content')
    <div class="user-screen">
        <div class="user-list">
            @foreach ($users as $u)
                <div>
                    <a href="/switch/{{ $u->id }}">
                        <b>{{ $u->name }}</b>
                    </a>
                    <a href="/switch/{{ $u->id }}">
                        <span class="avatar">
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

@extends('layouts.app')

@section('title', 'NeAnime · Смена пользователя')

@section('content')
    <header class="page-header">
        <h1 class="page-title">Смена пользователя</h1>
        <p class="page-subtitle">Выберите аккаунт или добавьте нового пользователя для этого устройства.</p>
    </header>
    <section class="page-content page-content--wide">
        @php
            /** @var \Illuminate\Support\Collection<int, \App\Models\DeviceLogin> $deviceLogins */
            $deviceLogins = $deviceLogins ?? collect();
            $hasLogins = $deviceLogins->filter(fn ($login) => $login->user !== null)->isNotEmpty();
            $redirectTarget = isset($redirectTarget) ? trim((string) $redirectTarget) : '';
            $redirectValue = $redirectTarget !== '' ? $redirectTarget : '/';
        @endphp
        <div class="space-y-10">
            @if(!$hasLogins)
                <div class="flex flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-slate-700 bg-slate-900/60 px-8 py-16 text-center">
                    <span class="material-symbols-outlined text-5xl text-blue-400">group</span>
                    <h2 class="text-2xl font-semibold text-slate-100">На этом устройстве пока не было входов</h2>
                    <p class="max-w-xl text-sm text-slate-300">Как только вы авторизуетесь, здесь появится список пользователей, которые заходили на этот сайт с текущего устройства.</p>
                    <button
                        type="button"
                        class="mt-2 inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500"
                        data-modal-target="login-modal"
                        data-modal-toggle="login-modal"
                    >
                        <span class="material-symbols-outlined text-base">login</span>
                        Войти в аккаунт
                    </button>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    @foreach($deviceLogins as $login)
                        @php $user = $login->user; @endphp
                        @continue(!$user)
                        <form method="POST" action="{{ url('/switch-user/login') }}" class="group">
                            <input type="hidden" name="login_id" value="{{ $login->getKey() }}">
                            <input type="hidden" name="redirect" value="{{ $redirectValue }}">
                            <button
                                type="submit"
                                class="flex w-full flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/80 p-5 text-left transition hover:border-blue-500/50 hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                            >
                                <div class="mb-4 inline-flex items-center justify-center overflow-hidden rounded-2xl border border-slate-700 bg-slate-800">
                                    @if($user->avatar_path)
                                        <img
                                            src="{{ url($user->avatar_path) }}"
                                            alt="Аватар пользователя {{ $user->name }}"
                                            class="size-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <span class="material-symbols-outlined text-3xl text-slate-300">person</span>
                                    @endif
                                </div>
                                <h3 class="text-lg font-semibold text-slate-100">{{ $user->name }}</h3>
                                <p class="mt-1 text-sm text-slate-400">{{ $user->email }}</p>
                                @if($login->last_used_at)
                                    <p class="mt-4 text-xs uppercase tracking-wide text-slate-500">Последний вход: {{ $login->last_used_at->format('d.m.Y H:i') }}</p>
                                @endif
                            </button>
                        </form>
                    @endforeach
                    <button
                        type="button"
                        class="flex min-h-[180px] flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-slate-700 bg-slate-900/50 p-5 text-center font-medium text-slate-200 transition hover:border-blue-500/50 hover:text-blue-300"
                        data-modal-target="login-modal"
                        data-modal-toggle="login-modal"
                    >
                        <span class="material-symbols-outlined text-4xl">add</span>
                        Добавить нового пользователя
                    </button>
                </div>
            @endif
        </div>
    </section>
@endsection

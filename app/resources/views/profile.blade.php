@extends('layouts.app')

@section('title', 'NeAnime · Профиль')

@section('content')
    <header class="page-header">
        <h1 class="page-title">Профиль</h1>
        <p class="page-subtitle">Управляйте личными данными, обновляйте пароль и загружайте новый аватар.</p>
    </header>
    <section class="page-content page-profile">
        <div class="grid gap-10 lg:grid-cols-[280px,1fr]">
            <div class="space-y-6">
                <div class="flex flex-col items-center gap-4 rounded-2xl border border-slate-700 bg-slate-900/70 p-6 text-center shadow-xl">
                    <div class="relative">
                        <span class="profile-avatar" aria-hidden="true">
                            @if($user->avatar_path)
                                <img src="{{ url($user->avatar_path) }}" alt="Текущий аватар" class="profile-avatar__image">
                            @else
                                <span class="material-symbols-outlined">person</span>
                            @endif
                        </span>
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-lg font-semibold">{{ $user->name }}</h2>
                        <p class="text-sm text-slate-300">{{ $user->email }}</p>
                    </div>
                    <p class="text-xs text-slate-400">Аватар автоматически обрежется и будет отображаться размером 100×100 пикселей.</p>
                </div>
            </div>
            <div>
                <form class="space-y-6" method="POST" action="{{ url('/profile') }}" enctype="multipart/form-data">
                    @if(!empty($success))
                        <div class="rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200">
                            {{ $success }}
                        </div>
                    @endif
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-200" for="profile-name">Имя</label>
                        <input
                            class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            type="text"
                            id="profile-name"
                            name="name"
                            autocomplete="name"
                            value="{{ $old['name'] ?? $user->name }}"
                            required
                        >
                        @if(!empty($errors['name']))
                            <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['name'] }}</p>
                        @endif
                    </div>
                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-200" for="profile-password">Новый пароль</label>
                            <input
                                class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                type="password"
                                id="profile-password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="Оставьте пустым, чтобы не менять"
                            >
                            @if(!empty($errors['password']))
                                <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['password'] }}</p>
                            @endif
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-200" for="profile-password-confirm">Повторите пароль</label>
                            <input
                                class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                type="password"
                                id="profile-password-confirm"
                                name="password_confirmation"
                                autocomplete="new-password"
                            >
                            @if(!empty($errors['password_confirmation']))
                                <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['password_confirmation'] }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-200" for="profile-avatar">Аватар</label>
                        <input
                            class="block w-full cursor-pointer rounded-lg border border-dashed border-slate-600 bg-slate-900/60 p-3 text-sm text-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            type="file"
                            id="profile-avatar"
                            name="avatar"
                            accept="image/png,image/jpeg,image/webp"
                        >
                        <p class="text-xs text-slate-400">Поддерживаются изображения JPG, PNG и WebP до 5 МБ. Мы автоматически обрежем и уменьшим их до 100×100 пикселей.</p>
                        @if(!empty($errors['avatar']))
                            <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['avatar'] }}</p>
                        @endif
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <button
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-900/60"
                            type="submit"
                        >
                            <span class="material-symbols-outlined text-base">save</span>
                            <span>Сохранить изменения</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

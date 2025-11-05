@extends('layouts.app')

@section('title', 'NeAnime · Регистрация')

@section('content')
    <header class="page-header">
        <h1 class="page-title">Создание аккаунта</h1>
        <p class="page-subtitle">Введите данные, чтобы зарегистрироваться и начать пользоваться сервисом.</p>
    </header>
    <section class="page-content page-auth">
        <div class="overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl">
            <form class="space-y-6 p-8" method="POST" action="{{ url('/register') }}">
                @csrf
                <input type="hidden" name="redirect" value="/">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-200" for="register-name">Имя</label>
                    <input
                        class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        type="text"
                        id="register-name"
                        name="name"
                        autocomplete="name"
                        value="{{ $old['name'] ?? '' }}"
                        required
                    >
                    @if(!empty($errors['name']))
                        <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['name'] }}</p>
                    @endif
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-200" for="register-email">Email</label>
                    <input
                        class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        type="email"
                        id="register-email"
                        name="email"
                        autocomplete="email"
                        value="{{ $old['email'] ?? '' }}"
                        required
                    >
                    @if(!empty($errors['email']))
                        <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['email'] }}</p>
                    @endif
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-200" for="register-password">Пароль</label>
                    <input
                        class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        type="password"
                        id="register-password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                    @if(!empty($errors['password']))
                        <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['password'] }}</p>
                    @endif
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-slate-200" for="register-password-confirm">Повторите пароль</label>
                    <input
                        class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        type="password"
                        id="register-password-confirm"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    >
                    @if(!empty($errors['password_confirmation']))
                        <p class="text-sm font-medium text-red-400" role="alert">{{ $errors['password_confirmation'] }}</p>
                    @endif
                </div>
                <button
                    class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-900/60"
                    type="submit"
                >
                    Зарегистрироваться
                </button>
            </form>
        </div>
    </section>
@endsection

@extends('layouts.app')

@section('title', 'NeAnime · Регистрация')

@section('content')
    <header class="page-header">
        <h1 class="page-title">Создание аккаунта</h1>
        <p class="page-subtitle">Введите данные, чтобы зарегистрироваться и начать пользоваться сервисом.</p>
    </header>
    <section class="page-content page-auth">
        <form class="auth-form" method="POST" action="{{ url('/register') }}">
            <input type="hidden" name="redirect" value="/">
            <div class="form-field">
                <label class="form-label" for="register-name">Имя</label>
                <div class="form-input-wrapper">
                    <span class="form-input-icon material-symbols-outlined">person</span>
                    <input
                        class="form-input"
                        type="text"
                        id="register-name"
                        name="name"
                        autocomplete="name"
                        value="{{ $old['name'] ?? '' }}"
                        required
                    >
                </div>
                @if(!empty($errors['name']))
                    <p class="form-message form-message--error">{{ $errors['name'] }}</p>
                @endif
            </div>
            <div class="form-field">
                <label class="form-label" for="register-email">Email</label>
                <div class="form-input-wrapper">
                    <span class="form-input-icon material-symbols-outlined">mail</span>
                    <input
                        class="form-input"
                        type="email"
                        id="register-email"
                        name="email"
                        autocomplete="email"
                        value="{{ $old['email'] ?? '' }}"
                        required
                    >
                </div>
                @if(!empty($errors['email']))
                    <p class="form-message form-message--error">{{ $errors['email'] }}</p>
                @endif
            </div>
            <div class="form-field">
                <label class="form-label" for="register-password">Пароль</label>
                <div class="form-input-wrapper">
                    <span class="form-input-icon material-symbols-outlined">lock</span>
                    <input
                        class="form-input"
                        type="password"
                        id="register-password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                </div>
                @if(!empty($errors['password']))
                    <p class="form-message form-message--error">{{ $errors['password'] }}</p>
                @endif
            </div>
            <div class="form-field">
                <label class="form-label" for="register-password-confirm">Повторите пароль</label>
                <div class="form-input-wrapper">
                    <span class="form-input-icon material-symbols-outlined">key</span>
                    <input
                        class="form-input"
                        type="password"
                        id="register-password-confirm"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    >
                </div>
                @if(!empty($errors['password_confirmation']))
                    <p class="form-message form-message--error">{{ $errors['password_confirmation'] }}</p>
                @endif
            </div>
            <button class="auth-submit" type="submit">Зарегистрироваться</button>
        </form>
    </section>
@endsection

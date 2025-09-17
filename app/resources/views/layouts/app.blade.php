<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NeAnime')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body @if($openLoginModal ?? false) class="modal-open" @endif>
    <nav class="navbar">
        <div class="nav-links">
            <a href="{{ url('/') }}" class="nav-link">
                <span class="material-symbols-outlined">search</span>
                <span>Поиск</span>
            </a>
            <a href="{{ url('/list?mode=favorites') }}" class="nav-link">
                <span class="material-symbols-outlined">favorite</span>
                <span>Избранное</span>
            </a>
            <a href="{{ url('/list?mode=top') }}" class="nav-link">
                <span class="material-symbols-outlined">star</span>
                <span>Лучшее</span>
            </a>
            <a href="{{ url('/list?mode=new') }}" class="nav-link">
                <span class="material-symbols-outlined">new_releases</span>
                <span>Новинки</span>
            </a>
        </div>
        <div class="nav-actions">
            <button
                class="nav-button"
                type="button"
                data-auth-button
                data-auth-state="{{ $currentUser ? 'authenticated' : 'guest' }}"
            >
                <span class="material-symbols-outlined">logout</span>
                <span>{{ $currentUser ? 'Выйти' : 'Войти' }}</span>
            </button>
            <button class="nav-button" type="button">
                <span class="material-symbols-outlined">fullscreen</span>
                <span>Полный экран / Обычный</span>
            </button>
        </div>
    </nav>
    <div
        class="modal-overlay"
        data-login-modal
        data-open-on-load="{{ $openLoginModal ? 'true' : 'false' }}"
        @if(!($openLoginModal ?? false)) hidden @endif
    >
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="login-title">
            <button class="modal-close" type="button" data-modal-close>
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="modal-header">
                <span class="modal-badge">С возвращением!</span>
                <h2 class="modal-title" id="login-title">Вход в аккаунт</h2>
                <p class="modal-description">Введите свои данные, чтобы продолжить и открыть доступ к персональным рекомендациям.</p>
            </div>
            <form class="auth-form" data-login-form method="POST" action="{{ url('/login') }}">
                <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
                <div class="form-field">
                    <label class="form-label" for="login-email">Email</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon material-symbols-outlined">alternate_email</span>
                        <input
                            class="form-input"
                            type="email"
                            id="login-email"
                            name="email"
                            autocomplete="email"
                            value="{{ $loginEmail ?? '' }}"
                            required
                        >
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="login-password">Пароль</label>
                    <div class="form-input-wrapper">
                        <span class="form-input-icon material-symbols-outlined">lock</span>
                        <input class="form-input" type="password" id="login-password" name="password" autocomplete="current-password" required>
                    </div>
                </div>
                <p
                    class="form-message form-message--error"
                    data-login-error
                    @if(empty($loginError)) hidden @endif
                >{{ $loginError }}</p>
                <div class="form-actions">
                    <button class="auth-submit" type="submit">Войти</button>
                    <a class="auth-secondary" href="{{ url('/register') }}">Регистрация</a>
                </div>
            </form>
        </div>
    </div>
    <main>
        @yield('content')
    </main>
    <form method="POST" action="{{ url('/logout') }}" data-logout-form hidden>
        <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
    </form>
    <script src="/js/app.js" defer></script>
</body>
</html>

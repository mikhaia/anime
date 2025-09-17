<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NeAnime')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.5.1/flowbite.min.css" integrity="sha512-5HZS0V9PhnCbS5z+5p3pyLgBxxh/vmzgk0/8nCTa2DYyI7nXkCg4lk+Wc66NnZ6MYj5PslHxltNNFUKzPk2Cww==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/css/app.css">
</head>
<body
    @class(['antialiased', 'modal-open' => $openLoginModal ?? false])
    data-authenticated="{{ $currentUser ? 'true' : 'false' }}"
    data-favorites='@json($favoriteIds ?? [])'
>
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
                @unless($currentUser)
                    data-modal-target="login-modal"
                    data-modal-toggle="login-modal"
                @endunless
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
        id="login-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto overflow-x-hidden p-4"
        data-login-modal
        data-open-on-load="{{ $openLoginModal ? 'true' : 'false' }}"
        aria-hidden="{{ ($openLoginModal ?? false) ? 'false' : 'true' }}"
        role="dialog"
        aria-labelledby="login-title"
        aria-modal="true"
        @if(!($openLoginModal ?? false)) hidden @endif
    >
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" data-modal-close></div>
        <div class="relative w-full max-w-md">
            <div class="relative overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 text-slate-100 shadow-2xl">
                <button
                    type="button"
                    class="absolute end-3 top-3 inline-flex size-10 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-modal-close
                    aria-label="Закрыть модальное окно"
                >
                    <span class="material-symbols-outlined">close</span>
                </button>
                <div class="px-6 py-7">
                    <div class="mb-6 space-y-2 text-center">
                        <span class="inline-flex items-center justify-center rounded-full bg-blue-500/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-300">
                            С возвращением!
                        </span>
                        <h2 class="text-2xl font-semibold" id="login-title">Вход в аккаунт</h2>
                        <p class="text-sm text-slate-300">Введите свои данные, чтобы продолжить и открыть доступ к персональным рекомендациям.</p>
                    </div>
                    <form class="space-y-5" data-login-form method="POST" action="{{ url('/login') }}">
                        <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
                        <div class="space-y-2 text-left">
                            <label class="block text-sm font-medium text-slate-200" for="login-email">Email</label>
                            <input
                                class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                type="email"
                                id="login-email"
                                name="email"
                                autocomplete="email"
                                value="{{ $loginEmail ?? '' }}"
                                required
                            >
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-sm font-medium text-slate-200" for="login-password">Пароль</label>
                            <input
                                class="block w-full rounded-lg border border-slate-700 bg-slate-800 p-2.5 text-sm text-white placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                type="password"
                                id="login-password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >
                        </div>
                        <p
                            class="text-sm font-medium text-red-400"
                            data-login-error
                            role="alert"
                            @if(empty($loginError)) hidden @endif
                        >{{ $loginError }}</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button
                                class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-900/60"
                                type="submit"
                            >
                                Войти
                            </button>
                            <a
                                class="w-full text-center text-sm font-semibold text-blue-400 transition hover:text-blue-300 sm:w-auto"
                                href="{{ url('/register') }}"
                            >
                                Регистрация
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <main>
        @yield('content')
    </main>
    <form method="POST" action="{{ url('/logout') }}" data-logout-form hidden>
        <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
    </form>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.5.1/flowbite.min.js" integrity="sha512-e1zNcu3zcOZ9F8AIMVAnMl1hTRvabzpKUfJGlnLIw3FGUeR9CvU9N1JsVgBttRaxDTKhuk0Ik1qfThaWwsugrA==" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <script src="/js/app.js" defer></script>
</body>
</html>

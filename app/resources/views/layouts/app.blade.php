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
            @if($currentUser)
                <div class="relative" data-dropdown>
                    <button
                        id="nav-user-menu-button"
                        type="button"
                        data-dropdown-toggle="nav-user-menu"
                        class="nav-button nav-profile flex items-center gap-3 pe-3"
                    >
                        <span class="nav-profile__avatar" aria-hidden="true">
                            @if($currentUser->avatar_path)
                                <img
                                    src="{{ url($currentUser->avatar_path) }}"
                                    alt="Аватар пользователя"
                                    class="nav-profile__avatar-image"
                                >
                            @else
                                <span class="material-symbols-outlined">person</span>
                            @endif
                        </span>
                        <span class="nav-profile__name">{{ $currentUser->name }}</span>
                        <span class="material-symbols-outlined text-base text-slate-400">expand_more</span>
                    </button>
                    <div
                        id="nav-user-menu"
                        class="absolute z-50 hidden w-64 divide-y divide-slate-800 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/95 text-sm shadow-2xl"
                    >
                        <div class="px-5 py-4">
                            <p class="text-sm font-semibold text-slate-100">{{ $currentUser->name }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $currentUser->email }}</p>
                        </div>
                        <ul class="space-y-1 px-2 py-3" aria-labelledby="nav-user-menu-button">
                            <li>
                                <a
                                    class="flex items-center gap-2 rounded-xl px-3 py-2 text-slate-100 transition hover:bg-slate-800"
                                    href="{{ url('/profile') }}"
                                >
                                    <span class="material-symbols-outlined text-base text-slate-300">edit</span>
                                    <span>Редактировать</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    class="flex items-center gap-2 rounded-xl px-3 py-2 text-slate-100 transition hover:bg-slate-800"
                                    href="{{ url('/switch-user') }}"
                                >
                                    <span class="material-symbols-outlined text-base text-slate-300">group</span>
                                    <span>Сменить пользователя</span>
                                </a>
                            </li>
                        </ul>
                        <div class="border-t border-slate-800 bg-slate-900 px-2 py-3">
                            <button
                                type="button"
                                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left font-semibold text-red-300 transition hover:bg-red-500/10 hover:text-red-200"
                                data-logout-link
                            >
                                <span class="material-symbols-outlined text-base">logout</span>
                                <span>Выход</span>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <button
                    class="nav-button"
                    type="button"
                    data-auth-button
                    data-auth-state="guest"
                    data-modal-target="login-modal"
                    data-modal-toggle="login-modal"
                >
                    <span class="material-symbols-outlined">login</span>
                    <span>Войти</span>
                </button>
            @endif
            <button class="nav-button" type="button" data-fullscreen-button>
                <span class="material-symbols-outlined" data-fullscreen-icon>fullscreen</span>
                <span data-fullscreen-text>Полный экран</span>
            </button>
        </div>
    </nav>
    @include('components.login-modal')
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

<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NeAnime')</title>
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
                <x-icon name="search" class="h-5 w-5" />
                <span>Поиск</span>
            </a>
            <a href="{{ url('/list?mode=favorites') }}" class="nav-link">
                <x-icon name="favorite" class="h-5 w-5" />
                <span>Избранное</span>
            </a>
            <a href="{{ url('/list?mode=top') }}" class="nav-link">
                <x-icon name="star" class="h-5 w-5" />
                <span>Лучшее</span>
            </a>
            <a href="{{ url('/list?mode=new') }}" class="nav-link">
                <x-icon name="sparkles" class="h-5 w-5" />
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
                                <x-icon name="user" class="h-5 w-5 text-slate-200" />
                            @endif
                        </span>
                        <span class="nav-profile__name">{{ $currentUser->name }}</span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
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
                                    <x-icon name="pencil-square" class="h-5 w-5 text-slate-300" />
                                    <span>Редактировать</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    class="flex items-center gap-2 rounded-xl px-3 py-2 text-slate-100 transition hover:bg-slate-800"
                                    href="{{ url('/switch-user') }}"
                                >
                                    <x-icon name="users" class="h-5 w-5 text-slate-300" />
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
                                <x-icon name="arrow-right-on-rectangle" class="h-5 w-5" />
                                <span>Выход</span>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <a
                    class="nav-button"
                    href="{{ url('/switch-user') . '?redirect=' . urlencode(request()->fullUrl()) }}"
                >
                    <x-icon name="arrow-left-on-rectangle" class="h-5 w-5" />
                    <span>Войти</span>
                </a>
            @endif
            <button class="nav-button" type="button" data-fullscreen-button>
                <span class="inline-flex items-center" data-fullscreen-icon>
                    <x-icon name="arrows-pointing-out" class="h-5 w-5" data-fullscreen-icon-state="enter" />
                    <x-icon name="arrows-pointing-in" class="hidden h-5 w-5" data-fullscreen-icon-state="exit" />
                </span>
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
    @stack('scripts')
</body>
</html>

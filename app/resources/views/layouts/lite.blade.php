<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NeAnime :: Lite</title>
    <link rel="stylesheet" href="/lite/style.css">
    <link rel="stylesheet" href="/lite/deck.css" media="(max-width: 1280px)">
    <link rel="stylesheet" href="/lite/mobile.css" media="(max-width: 655px)">
    <link rel="stylesheet" href="/lite/tv.css">
    <link rel="stylesheet" href="/lite/search.css">
    <link rel="stylesheet" href="/lite/chromecast.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>

<body>
    @include('lite.loader')
    @include('lite.user')
    <div class="header">
        <button id="menu-toggle" class="menu-toggle" title="Открыть меню">
            <svg width="45" height="45" class="icon icon-menu-toggle" aria-hidden="true">
                <use href="/lite/icons/menu_toggle.svg#menu_toggle"></use>
            </svg>
        </button>
        <nav class="nav">
            <ul id="main-nav">
                <li>{{-- class="has-submenu" --}}
                    <a href="/users">
                        <svg width="24" height="24" class="icon icon-user" aria-hidden="true">
                            <use href="/lite/icons/user.svg#user"></use>
                        </svg>
                        <span class="nav-label">
                            @auth
                                {{ auth()->user()->name }}
                            @else
                                Войти
                            @endauth
                        </span>
                    </a>
                    {{-- <ul class="submenu" id="user-submenu" role="menu">
                        <li role="none"><a role="menuitem" href="#edit">Редактировать</a></li>
                        <li role="none"><a role="menuitem" href="#switch">Сменить пользователя</a></li>
                        <li role="none"><a role="menuitem" href="#logout">Выход</a></li>
                    </ul> --}}
                </li>
                <li class="nav-search {{ request()->is('search*') ? 'active' : '' }}">
                    <a href="/search">
                        <svg width="24" height="24" class="icon icon-search" aria-hidden="true">
                            <use href="/lite/icons/search.svg#search"></use>
                        </svg>
                        <span class="nav-label">Поиск</span>
                    </a>
                </li>
                <li class="nav-favorites {{ request()->is('fav*') ? 'active' : '' }}">
                    <a href="/fav">
                        <svg width="24" height="24" class="icon icon-fav" aria-hidden="true">
                            <use href="/lite/icons/fav.svg#fav"></use>
                        </svg>
                        <span class="nav-label">Избранное</span>
                    </a>
                </li>
                <li class="nav-new {{ request()->is('new*') ? 'active' : '' }}">
                    <a href="/new">
                        <svg width="24" height="24" class="icon icon-new" aria-hidden="true">
                            <use href="/lite/icons/new.svg#new"></use>
                        </svg>
                        <span class="nav-label">Новинки</span>
                    </a>
                </li>
                <li class="nav-top {{ request()->is('top*') ? 'active' : '' }}">
                    <a href="/top">
                        <svg width="24" height="24" class="icon icon-top" aria-hidden="true">
                            <use href="/lite/icons/top.svg#top"></use>
                        </svg>
                        <span class="nav-label">Лучшее</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    @yield('content')
    <script src="/lite/jquery.js"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        // const version = Date.now();
        // document.write(`<script src="/lite/script.js?${version}"><\/script>`);
    </script>
    <script src="/lite/script.js"></script>
    @yield('scripts')

</body>

</html>

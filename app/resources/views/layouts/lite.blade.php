<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeAnime :: Lite</title>
    <link rel="stylesheet" href="/lite/style.css">
    <link rel="stylesheet" href="/lite/mobile.css" media="(max-width: 655px)">
    <link rel="stylesheet" href="/lite/tv.css">
    <link rel="stylesheet" href="/lite/chromecast.css">

    <!-- Temporary disable cache -->
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <script>

    </script>
</head>

<body>
    <div class="header">
        <button id="menu-toggle" class="menu-toggle" title="Открыть меню">
            <svg width="45" height="45" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </button>
        <nav class="nav">
            <ul id="main-nav">
                <li class="has-submenu">
                    <a href="#">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path fill-rule="evenodd"
                                d="M12 20a7.966 7.966 0 0 1-5.002-1.756l.002.001v-.683c0-1.794 1.492-3.25 3.333-3.25h3.334c1.84 0 3.333 1.456 3.333 3.25v.683A7.966 7.966 0 0 1 12 20ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10c0 5.5-4.44 9.963-9.932 10h-.138C6.438 21.962 2 17.5 2 12Zm10-5c-1.84 0-3.333 1.455-3.333 3.25S10.159 13.5 12 13.5c1.84 0 3.333-1.455 3.333-3.25S13.841 7 12 7Z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="nav-label">Пользователь</span>
                    </a>
                    <ul class="submenu" id="user-submenu" role="menu">
                        <li role="none"><a role="menuitem" href="#edit">Редактировать</a></li>
                        <li role="none"><a role="menuitem" href="#switch">Сменить пользователя</a></li>
                        <li role="none"><a role="menuitem" href="#logout">Выход</a></li>
                    </ul>
                </li>
                <li class="nav-search">
                    <a href="">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                        <span class="nav-label">Поиск</span>
                    </a>
                </li>
                <li class="nav-favorites">
                    <a href="">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path
                                d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.343 3.172 10.828a4 4 0 010-5.656z" />
                        </svg>
                        <span class="nav-label">Избранное</span>
                    </a>
                </li>
                <li class="nav-new">
                    <a href="/new">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path
                                d="M5 2a1 1 0 01.894.553L7.382 4H9a1 1 0 010 2H7.382l-.488 1.447A1 1 0 015 10a1 1 0 01-.894-.553L3.618 7H2a1 1 0 110-2h1.618l.488-1.447A1 1 0 015 2zM15 6a1 1 0 01.894.553L17.382 8H19a1 1 0 110 2h-1.618l-.488 1.447A1 1 0 0115 14a1 1 0 01-.894-.553L13.618 11H12a1 1 0 110-2h1.618l.488-1.447A1 1 0 0115 6zM10 12a1 1 0 01.894.553L11.382 14H13a1 1 0 110 2h-1.618l-.488 1.447A1 1 0 019 19a1 1 0 01-.894-.553L7.618 16H6a1 1 0 110-2h1.618l.488-1.447A1 1 0 0110 12z" />
                        </svg>
                        <span class="nav-label">Новинки</span>
                    </a>
                </li>
                <li class="nav-top">
                    <a href="/top">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.946a1 1 0 00.95.69h4.157c.969 0 1.371 1.24.588 1.81l-3.369 2.449a1 1 0 00-.364 1.118l1.286 3.946c.3.921-.755 1.688-1.54 1.118L10 15.347l-3.369 2.449c-.784.57-1.838-.197-1.539-1.118l1.286-3.946a1 1 0 00-.364-1.118L2.605 9.373c-.783-.57-.38-1.81.588-1.81h4.157a1 1 0 00.95-.69L9.05 2.927z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="nav-label">Лучшее</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    @yield('content')
    <script src="https://code.jquery.com/jquery-3.7.1.slim.min.js"
        integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>
    <script>
        const version = Date.now();
        document.write(`<script src="/lite/script.js?${version}"><\/script>`);
    </script>
</body>

</html>
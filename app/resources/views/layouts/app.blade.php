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
<body>
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
            <button class="nav-button" type="button">
                <span class="material-symbols-outlined">login</span>
                <span>Войти / Выйти</span>
            </button>
            <button class="nav-button" type="button">
                <span class="material-symbols-outlined">fullscreen</span>
                <span>Полный экран / Обычный</span>
            </button>
        </div>
    </nav>
    <main>
        @yield('content')
    </main>
</body>
</html>

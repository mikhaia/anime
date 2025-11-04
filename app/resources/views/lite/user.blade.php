<link rel="stylesheet" href="/lite/user.css">
<div class="overlay">
    <div class="modal">
        <a href="#" class="close">&times;</a>
        <h3>Войти</h3>
        <div class="error-message"></div>
        <form method="POST" action="/login" class="form">
            @csrf
            <div class="field">
                <label for="name">Имя пользователя:</label>
                <input type="text" name="name" required>
            </div>
            <div class="field">
                <label for="password">Пароль:</label>
                <input type="password" name="password" required>
            </div>
            <div class="bottom">
                <button type="submit">Войти</button>
                <button type="button">Создать пользователя</button>
                <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
            </div>
        </form>
    </div>
</div>
<script src="/lite/user.js"></script>

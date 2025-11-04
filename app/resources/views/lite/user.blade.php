<link rel="stylesheet" href="/lite/user.css">
<div class="overlay">
    <div class="modal">
        <a href="#" class="close">&times;</a>

        <div class="user-login">
            <h3>Войти</h3>
            <div class="error-message" data-error="login"></div>
            <form method="POST" action="/login" class="form">
                @csrf
                <div class="field">
                    <label for="login-name">Имя пользователя или email:</label>
                    <input type="text" id="login-name" name="name" required>
                </div>
                <div class="field">
                    <label for="login-password">Пароль:</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <div class="bottom">
                    <button type="submit">Войти</button>
                    <button type="button" class="create-user">Создать пользователя</button>
                </div>
            </form>
        </div>

        <div class="user-register">
            <h3>Создать пользователя</h3>
            <div class="error-message" data-error="register"></div>
            <form method="POST" action="/create" class="form">
                @csrf
                <div class="field">
                    <label for="register-name">Имя пользователя:</label>
                    <input type="text" id="register-name" name="name" required>
                </div>
                <div class="field">
                    <label for="register-email">Email:</label>
                    <input type="email" id="register-email" name="email" required>
                </div>
                <div class="field">
                    <label for="register-password">Пароль:</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                <div class="bottom">
                    <button type="submit">Создать</button>
                    {{-- <button type="button" class="back-to-login">У меня есть аккаунт</button> --}}
                </div>
            </form>
        </div>
    </div>
</div>
<script src="/lite/user.js"></script>

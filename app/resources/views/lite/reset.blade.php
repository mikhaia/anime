<link rel="stylesheet" href="/lite/user.css">
<div class="overlay show">
    <div class="modal">
        <a href="/" class="close">&times;</a>

        <div class="password-reset">
            <h3>Установить новый пароль</h3>
            <div class="error-message" data-error="reset"></div>
            <form method="POST" action="{{ url('/lite/reset') }}" class="form reset-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="field">
                    <label for="reset-password">Новый пароль:</label>
                    <input type="password" id="reset-password" name="password" required>
                </div>
                <div class="field">
                    <label for="reset-password-confirm">Подтвердить пароль:</label>
                    <input type="password" id="reset-password-confirm" name="password_confirm" required>
                </div>
                <div class="bottom">
                    <button type="submit">Установить пароль</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="/lite/user.js"></script>

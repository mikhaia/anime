<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .content {
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Восстановление пароля</h1>
        </div>

        <div class="content">
            <p>Привет, {{ $user->name }}!</p>

            <p>Вы запросили восстановление пароля для вашего аккаунта.</p>

            <p>Нажмите на кнопку ниже, чтобы установить новый пароль:</p>

            <a href="{{ $resetUrl }}" class="button">Восстановить пароль</a>

            <p>Ссылка действительна в течение 1 часа.</p>

            <p>Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо.</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Anime. Все права защищены.</p>
        </div>
    </div>
</body>

</html>

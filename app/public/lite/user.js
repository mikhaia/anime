document.addEventListener('DOMContentLoaded', function () {

    $('.modal .form').on('submit', function (event) {
        event.preventDefault();

        const $form = $(this);
        const $errorMessage = $form.find('.error-message');

        // Валидация для формы сброса пароля
        if ($form.hasClass('reset-form')) {
            const password = $form.find('input[name="password"]').val();
            const passwordConfirm = $form.find('input[name="password_confirm"]').val();

            if (password !== passwordConfirm) {
                $errorMessage.text('Пароли не совпадают.').show();
                return;
            }

            if (password.length < 6) {
                $errorMessage.text('Пароль должен быть не менее 6 символов.').show();
                return;
            }
        }

        $.post($form.attr('action'), $form.serialize(), function (response) {
            console.log('response', response);
            if (response.success) {
                location.reload();
            } else {
                console.log('response.message', response.message);
                console.log($errorMessage);
                console.log($form);
                $errorMessage.text(response.message).show();
            }
        }).fail(function () {
            $errorMessage.text('Ошибка при отправке формы.').show();
        });
    });


    $('.modal .close').on('click', function (event) {
        event.preventDefault();
        if ($(this).attr('href') === '/') {
            window.location.href = '/';
        } else {
            $('.overlay').removeClass('show');
        }
    });

    $('.open-auth-modal').on('click', function (event) {
        event.preventDefault();
        $('.overlay').addClass('show');
    });

    $('.create-user').on('click', function () {
        $('.user-login').hide();
        $('.user-register').show();
        $('.user-recover').hide();
    });

    $('.back-to-login').on('click', function () {
        $('.user-login').show();
        $('.user-register').hide();
        $('.user-recover').hide();
    });

    $('.recover-password').on('click', function (event) {
        event.preventDefault();
        $('.user-login').hide();
        $('.user-register').hide();
        $('.user-recover').show();
    });
});

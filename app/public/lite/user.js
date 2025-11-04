document.addEventListener('DOMContentLoaded', function () {

    $('.modal .form').on('submit', function (event) {
        event.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function (response) {
            if (response.success) {
                location.reload();
            } else {
                $('.error-message').text(response.message);
            }
        });
    });


    $('.modal .close').on('click', function (event) {
        event.preventDefault();
        $('.overlay').removeClass('show');
    });

    $('.open-auth-modal').on('click', function (event) {
        event.preventDefault();
        $('.overlay').addClass('show');
    });

    $('.create-user').on('click', function () {
        $('.user-register').show();
    });

    $('.back-to-login').on('click', function () {
        $loginBlock.show();
    });
});

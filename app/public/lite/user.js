document.addEventListener('DOMContentLoaded', function() {
    var $form = $('.modal .form');
    $form.on('submit', function (event) {
        event.preventDefault();

        $.post($form.attr('action'), $form.serialize()).always(function (response) {
            if (response.success) {
                console.log('Login successful');
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
});
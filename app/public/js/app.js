(function () {
    const authButton = document.querySelector('[data-auth-button]');
    const loginModal = document.querySelector('[data-login-modal]');
    const loginForm = document.querySelector('[data-login-form]');
    const logoutForm = document.querySelector('[data-logout-form]');
    const modalCloseControls = document.querySelectorAll('[data-modal-close]');

    function openLoginModal() {
        if (!loginModal) {
            return;
        }

        loginModal.hidden = false;
        loginModal.classList.add('is-open');
        document.body.classList.add('modal-open');

        const emailInput = loginForm ? loginForm.querySelector('[name="email"]') : null;
        if (emailInput instanceof HTMLElement) {
            emailInput.focus();
        }
    }

    function closeLoginModal() {
        if (!loginModal) {
            return;
        }

        loginModal.classList.remove('is-open');
        loginModal.hidden = true;
        document.body.classList.remove('modal-open');
    }

    function toggleAuth() {
        if (!authButton) {
            return;
        }

        const state = authButton.dataset.authState;
        if (state === 'authenticated') {
            if (logoutForm) {
                logoutForm.submit();
            }
        } else {
            openLoginModal();
        }
    }

    if (authButton) {
        authButton.addEventListener('click', toggleAuth);
    }

    modalCloseControls.forEach((element) => {
        element.addEventListener('click', () => {
            closeLoginModal();
        });
    });

    if (loginModal) {
        loginModal.addEventListener('click', (event) => {
            if (event.target === loginModal) {
                closeLoginModal();
            }
        });

        if (loginModal.dataset.openOnLoad === 'true') {
            openLoginModal();
        }
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && loginModal && loginModal.classList.contains('is-open')) {
            closeLoginModal();
        }
    });
})();

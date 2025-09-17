(function () {
    const authButton = document.querySelector('[data-auth-button]');
    const loginModal = document.querySelector('[data-login-modal]');
    const loginForm = document.querySelector('[data-login-form]');
    const logoutForm = document.querySelector('[data-logout-form]');
    const modalCloseControls = loginModal ? loginModal.querySelectorAll('[data-modal-close]') : [];

    function openLoginModal() {
        if (!loginModal) {
            return;
        }

        loginModal.hidden = false;
        loginModal.classList.remove('hidden');
        loginModal.setAttribute('aria-hidden', 'false');
        loginModal.classList.add('flex');
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

        loginModal.classList.add('hidden');
        loginModal.setAttribute('aria-hidden', 'true');
        loginModal.hidden = true;
        loginModal.classList.remove('flex');
        document.body.classList.remove('modal-open');
    }

    function isLoginModalOpen() {
        return Boolean(loginModal) && !loginModal.classList.contains('hidden');
    }

    function toggleAuth(event) {
        if (!authButton) {
            return;
        }

        const state = authButton.dataset.authState;
        if (state === 'authenticated') {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (logoutForm) {
                logoutForm.submit();
            }
        } else {
            event.preventDefault();
            event.stopImmediatePropagation();
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
        if (loginModal.dataset.openOnLoad === 'true') {
            openLoginModal();
        }
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isLoginModalOpen()) {
            closeLoginModal();
        }
    });
})();

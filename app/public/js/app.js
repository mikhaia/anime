(function () {
    const globalNamespace = window.NeAnime || (window.NeAnime = {});

    const authButton = document.querySelector('[data-auth-button]');
    const loginModal = document.querySelector('[data-login-modal]');
    const loginForm = document.querySelector('[data-login-form]');
    const logoutForm = document.querySelector('[data-logout-form]');
    const logoutLinks = document.querySelectorAll('[data-logout-link]');
    const modalCloseControls = loginModal ? loginModal.querySelectorAll('[data-modal-close]') : [];
    const loginModalToggleButtons = document.querySelectorAll('[data-modal-toggle="login-modal"]');
    const fullscreenButton = document.querySelector('[data-fullscreen-button]');
    const fullscreenIcon = fullscreenButton ? fullscreenButton.querySelector('[data-fullscreen-icon]') : null;
    const fullscreenText = fullscreenButton ? fullscreenButton.querySelector('[data-fullscreen-text]') : null;
    const dropdownToggleButtons = document.querySelectorAll('[data-dropdown-toggle]');

    dropdownToggleButtons.forEach((button) => {
        const targetId = button.getAttribute('data-dropdown-toggle');

        if (!targetId) {
            return;
        }

        const dropdownMenu = document.getElementById(targetId);

        if (!dropdownMenu) {
            return;
        }

        button.addEventListener('click', (event) => {
            event.preventDefault();

            const shouldOpen = dropdownMenu.style.display !== 'block';

            if (shouldOpen) {
                dropdownMenu.style.display = 'block';
                dropdownMenu.classList.remove('hidden');
                dropdownMenu.setAttribute('aria-hidden', 'false');
                button.setAttribute('aria-expanded', 'true');
            } else {
                dropdownMenu.style.display = 'none';
                dropdownMenu.classList.add('hidden');
                dropdownMenu.setAttribute('aria-hidden', 'true');
                button.setAttribute('aria-expanded', 'false');
            }
        });
    });

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

    loginModalToggleButtons.forEach((element) => {
        element.addEventListener('click', (event) => {
            if (element === authButton && authButton && authButton.dataset.authState === 'authenticated') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openLoginModal();
        });
    });

    logoutLinks.forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!logoutForm) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            logoutForm.submit();
        });
    });

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

    const searchForm = document.querySelector('[data-anime-search-form]');
    const searchInput = searchForm ? searchForm.querySelector('[data-anime-search-input]') : null;
    const suggestionsPanel = searchForm ? searchForm.querySelector('[data-anime-search-suggestions]') : null;

    if (searchForm && searchInput && suggestionsPanel) {
        let debounceTimer = null;
        let activeController = null;
        let currentSuggestions = [];

        function hideSuggestions() {
            currentSuggestions = [];
            suggestionsPanel.innerHTML = '';
            suggestionsPanel.hidden = true;
            suggestionsPanel.classList.add('hidden');
            suggestionsPanel.setAttribute('aria-hidden', 'true');
        }

        function createSuggestionLink(suggestion) {
            const title = typeof suggestion?.title === 'string' ? suggestion.title.trim() : '';
            const englishTitle = typeof suggestion?.title_english === 'string' ? suggestion.title_english.trim() : '';
            const id = suggestion?.id;

            if (!title || !id) {
                return null;
            }

            const alias = typeof suggestion?.alias === 'string' ? suggestion.alias.trim() : '';
            const identifier = alias !== '' ? alias : String(id);
            const link = document.createElement('a');
            link.className =
                'block border-b border-slate-800 px-4 py-3 text-sm text-slate-100 transition last:border-b-0 hover:bg-slate-800/80 focus:bg-slate-800/80 focus:outline-none';
            link.href = `/watch/${encodeURIComponent(identifier)}`;
            link.setAttribute('role', 'option');
            link.dataset.animeId = String(id);

            const titleElement = document.createElement('span');
            titleElement.className = 'block';
            titleElement.textContent = title;
            link.appendChild(titleElement);

            if (englishTitle !== '') {
                const englishElement = document.createElement('small');
                englishElement.className = 'mt-1 block text-xs text-slate-400';
                englishElement.textContent = englishTitle;
                link.appendChild(englishElement);
            }

            return link;
        }

        function showSuggestions(items) {
            suggestionsPanel.innerHTML = '';
            currentSuggestions = Array.isArray(items) ? items.filter(Boolean) : [];

            if (currentSuggestions.length === 0) {
                hideSuggestions();
                return;
            }

            currentSuggestions.forEach((item) => {
                const link = createSuggestionLink(item);
                if (link) {
                    suggestionsPanel.appendChild(link);
                }
            });

            if (!suggestionsPanel.hasChildNodes()) {
                hideSuggestions();
                return;
            }

            suggestionsPanel.hidden = false;
            suggestionsPanel.classList.remove('hidden');
            suggestionsPanel.setAttribute('aria-hidden', 'false');
        }

        function abortRequest() {
            if (activeController) {
                activeController.abort();
                activeController = null;
            }
        }

        function requestSuggestions(query) {
            abortRequest();

            if (typeof AbortController !== 'undefined') {
                activeController = new AbortController();
            }

            const controller = activeController;
            const options = controller ? { signal: controller.signal } : {};

            fetch(`/api/anime/suggestions?query=${encodeURIComponent(query)}`, options)
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to load suggestions');
                    }

                    return response.json();
                })
                .then((payload) => {
                    if (searchInput.value.trim() !== query) {
                        return;
                    }

                    if (payload && Array.isArray(payload.data)) {
                        showSuggestions(payload.data);
                    } else {
                        hideSuggestions();
                    }
                })
                .catch((error) => {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    console.warn('Не удалось загрузить подсказки поиска', error);
                    hideSuggestions();
                })
                .finally(() => {
                    if (controller === activeController) {
                        activeController = null;
                    }
                });
        }

        function handleInput() {
            const value = searchInput.value.trim();

            if (value.length < 2) {
                clearTimeout(debounceTimer);
                abortRequest();
                hideSuggestions();
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                requestSuggestions(value);
            }, 200);
        }

        hideSuggestions();

        searchInput.addEventListener('input', handleInput);

        searchInput.addEventListener('focus', () => {
            if (currentSuggestions.length > 0) {
                showSuggestions(currentSuggestions);
            }
        });

        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideSuggestions();
            }
        });

        searchForm.addEventListener('submit', () => {
            hideSuggestions();
        });

        document.addEventListener('click', (event) => {
            if (!searchForm.contains(event.target)) {
                hideSuggestions();
            }
        });
    }

    function getFullscreenElement() {
        return (
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement ||
            null
        );
    }

    function ensurePromise(result) {
        if (result && typeof result.then === 'function') {
            return result;
        }

        return Promise.resolve();
    }

    function requestFullscreen(element) {
        if (!element) {
            return Promise.reject(new Error('Element is not available for fullscreen'));
        }

        const request =
            element.requestFullscreen ||
            element.webkitRequestFullscreen ||
            element.mozRequestFullScreen ||
            element.msRequestFullscreen ||
            null;

        if (request) {
            return ensurePromise(request.call(element));
        }

        return Promise.reject(new Error('Fullscreen API is not supported'));
    }

    function exitFullscreen() {
        const exit =
            document.exitFullscreen ||
            document.webkitExitFullscreen ||
            document.mozCancelFullScreen ||
            document.msExitFullscreen ||
            null;

        if (exit) {
            return ensurePromise(exit.call(document));
        }

        return Promise.reject(new Error('Fullscreen API is not supported'));
    }

    function updateFullscreenButtonState() {
        const isActive = Boolean(getFullscreenElement());

        if (fullscreenButton) {
            fullscreenButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            fullscreenButton.setAttribute('aria-label', isActive ? 'Выйти из полноэкранного режима' : 'Перейти в полноэкранный режим');
        }

        if (fullscreenIcon) {
            fullscreenIcon.textContent = isActive ? 'fullscreen_exit' : 'fullscreen';
        }

        if (fullscreenText) {
            fullscreenText.textContent = isActive ? 'Обычный режим' : 'Полный экран';
        }
    }

    function toggleFullscreen() {
        if (getFullscreenElement()) {
            exitFullscreen().catch(() => {
                // Ignore errors when exiting fullscreen
            });
        } else {
            requestFullscreen(document.documentElement).catch(() => {
                // Ignore errors when entering fullscreen
            });
        }
    }

    if (fullscreenButton) {
        fullscreenButton.addEventListener('click', (event) => {
            event.preventDefault();
            toggleFullscreen();
        });
    }

    document.addEventListener('fullscreenchange', updateFullscreenButtonState);
    document.addEventListener('webkitfullscreenchange', updateFullscreenButtonState);
    document.addEventListener('mozfullscreenchange', updateFullscreenButtonState);
    document.addEventListener('MSFullscreenChange', updateFullscreenButtonState);

    updateFullscreenButtonState();

    const bodyElement = document.body;
    const initialFavoriteIds = (() => {
        if (!bodyElement || !bodyElement.dataset || !bodyElement.dataset.favorites) {
            return [];
        }

        try {
            const parsed = JSON.parse(bodyElement.dataset.favorites);
            if (Array.isArray(parsed)) {
                return parsed.map((value) => String(value));
            }
        } catch (error) {
            console.warn('Failed to parse favorites list', error);
        }

        return [];
    })();

    let isAuthenticated = bodyElement?.dataset?.authenticated === 'true';
    const favoriteState = {
        ids: new Set(initialFavoriteIds),
    };

    function syncFavoriteDataset() {
        if (!bodyElement) {
            return;
        }

        bodyElement.dataset.favorites = JSON.stringify(Array.from(favoriteState.ids));
    }

    function isFavorite(animeId) {
        if (animeId === undefined || animeId === null) {
            return false;
        }

        return favoriteState.ids.has(String(animeId));
    }

    function markFavorite(animeId) {
        if (animeId === undefined || animeId === null) {
            return;
        }

        favoriteState.ids.add(String(animeId));
        syncFavoriteDataset();
    }

    function unmarkFavorite(animeId) {
        if (animeId === undefined || animeId === null) {
            return;
        }

        favoriteState.ids.delete(String(animeId));
        syncFavoriteDataset();
    }

    function updateFavoriteButtonAppearance(button, active) {
        if (!button) {
            return;
        }

        button.classList.toggle('anime-card__favorite--active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.setAttribute('aria-label', active ? 'Удалить из избранного' : 'Добавить в избранное');

        const icon = button.querySelector('[data-favorite-icon]');
        const text = button.querySelector('[data-favorite-text]');

        if (icon) {
            icon.classList.toggle('anime-card__favorite-icon--active', active);
        }

        if (text) {
            text.textContent = active ? 'В избранном' : 'В избранное';
        }
    }

    function refreshFavoriteButtons(animeId, active) {
        const id = String(animeId);
        document.querySelectorAll(`[data-favorite-button][data-anime-id="${id}"]`).forEach((button) => {
            updateFavoriteButtonAppearance(button, active);
        });
    }

    function createFavoritePayload(release, title, posterUrl) {
        if (!release || !release.id) {
            return null;
        }

        return {
            id: release.id,
            title,
            title_english: typeof release?.title_english === 'string' && release.title_english.trim().length > 0
                ? release.title_english.trim()
                : null,
            poster: posterUrl || null,
            type: release?.type ?? null,
            year: release?.year ?? null,
            episodes: release?.episodes_total ?? null,
            alias: release?.alias ?? null,
        };
    }

    function createFavoriteButton(payload) {
        if (!payload || !payload.id) {
            return null;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'anime-card__action anime-card__action--favorite anime-card__favorite';
        button.dataset.favoriteButton = 'true';
        button.dataset.animeId = String(payload.id);
        button.dataset.animePayload = JSON.stringify(payload);
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', 'Добавить в избранное');

        const icon = document.createElement('span');
        icon.className = 'material-symbols-outlined anime-card__favorite-icon';
        icon.dataset.favoriteIcon = '';
        icon.textContent = 'favorite';

        const text = document.createElement('span');
        text.className = 'anime-card__favorite-text';
        text.dataset.favoriteText = '';
        text.textContent = 'В избранное';

        button.append(icon, text);
        updateFavoriteButtonAppearance(button, isFavorite(payload.id));

        return button;
    }

    function showFavoritesList() {
        const favoritesList = document.querySelector('[data-favorites-list]');
        if (favoritesList) {
            favoritesList.hidden = false;
        }

        const emptyState = document.querySelector('[data-favorites-empty]');
        if (emptyState) {
            emptyState.hidden = true;
        }
    }

    function removeFavoriteCardsFromList(animeId) {
        const cards = document.querySelectorAll(`[data-anime-card][data-anime-id="${animeId}"]`);
        cards.forEach((card) => {
            const listElement = card.closest('[data-favorites-list]');
            if (!listElement) {
                return;
            }

            card.remove();

            if (!listElement.querySelector('[data-anime-card]')) {
                listElement.hidden = true;
                const emptyState = document.querySelector('[data-favorites-empty]');
                if (emptyState) {
                    emptyState.hidden = false;
                }
            }
        });
    }

    async function toggleFavorite(button) {
        if (!button) {
            return;
        }

        const animeId = button.dataset.animeId;
        if (!animeId) {
            return;
        }

        if (!isAuthenticated) {
            if (authButton) {
                authButton.click();
            } else {
                openLoginModal();
            }
            return;
        }

        if (button.dataset.loading === 'true') {
            return;
        }

        button.dataset.loading = 'true';
        button.disabled = true;

        let payload = null;
        if (button.dataset.animePayload) {
            try {
                payload = JSON.parse(button.dataset.animePayload);
            } catch (error) {
                console.warn('Failed to parse anime payload', error);
            }
        }

        if (!payload) {
            payload = {};
        }
        payload.id = Number(payload.id ?? animeId);

        try {
            if (isFavorite(animeId)) {
                const response = await fetch(`/favorites/${animeId}`, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (response.status === 401) {
                    isAuthenticated = false;
                    if (authButton) {
                        authButton.click();
                    } else {
                        openLoginModal();
                    }
                    return;
                }

                if (!response.ok && response.status !== 404) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                unmarkFavorite(animeId);
                refreshFavoriteButtons(animeId, false);
                removeFavoriteCardsFromList(animeId);
            } else {
                const response = await fetch('/favorites', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                if (response.status === 401) {
                    isAuthenticated = false;
                    if (authButton) {
                        authButton.click();
                    } else {
                        openLoginModal();
                    }
                    return;
                }

                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }

                markFavorite(animeId);
                refreshFavoriteButtons(animeId, true);
                showFavoritesList();
            }
        } catch (error) {
            console.error('Failed to toggle favorite', error);
        } finally {
            delete button.dataset.loading;
            button.disabled = false;
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-favorite-button]');
        if (!button) {
            return;
        }

        event.preventDefault();
        toggleFavorite(button);
    });

    document.querySelectorAll('[data-favorite-button]').forEach((button) => {
        const animeId = button.dataset.animeId;
        updateFavoriteButtonAppearance(button, isFavorite(animeId));
    });

    globalNamespace.favorites = {
        isFavorite,
        markFavorite,
        unmarkFavorite,
        createPayload: createFavoritePayload,
        createButton: createFavoriteButton,
        updateButtonAppearance: updateFavoriteButtonAppearance,
        refreshButtons: refreshFavoriteButtons,
    };
})();

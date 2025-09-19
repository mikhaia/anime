(function () {
    const APP_BASE_URL = window.location.origin;
    const ANILIBRIA_BASE_URL = 'https://anilibria.top';
    const ANILIBRIA_SEARCH_URL = `${ANILIBRIA_BASE_URL}/api/v1/app/search/releases`;

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
            text.textContent = active ? 'Удалить из избранного' : 'Добавить в избранное';
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

    function createWatchUrl(payload) {
        if (!payload) {
            return '';
        }

        const identifier = payload.alias || payload.id;
        if (!identifier) {
            return '';
        }

        return `/watch/${encodeURIComponent(String(identifier))}`;
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

        const icon = document.createElement('span');
        icon.className = 'material-symbols-outlined anime-card__favorite-icon';
        icon.dataset.favoriteIcon = '';
        icon.textContent = 'favorite';

        const text = document.createElement('span');
        text.className = 'anime-card__favorite-text';
        text.dataset.favoriteText = '';

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

    function setCardActionsVisibility(card, visible) {
        if (!card) {
            return;
        }

        card.classList.toggle('anime-card--actions-visible', visible);

        const trigger = card.querySelector('[data-anime-card-trigger]');
        if (trigger) {
            trigger.setAttribute('aria-expanded', visible ? 'true' : 'false');
        }

        const actions = card.querySelector('[data-anime-card-actions]');
        if (actions) {
            actions.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }
    }

    function hideAllCardActions(exceptCard = null) {
        document.querySelectorAll('[data-anime-card].anime-card--actions-visible').forEach((card) => {
            if (card === exceptCard) {
                return;
            }

            setCardActionsVisibility(card, false);
        });
    }

    function toggleCardActions(card) {
        if (!card) {
            return;
        }

        const isVisible = card.classList.contains('anime-card--actions-visible');
        if (isVisible) {
            setCardActionsVisibility(card, false);
        } else {
            hideAllCardActions(card);
            setCardActionsVisibility(card, true);
        }
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-anime-card-trigger]');
        if (trigger) {
            const card = trigger.closest('[data-anime-card]');
            if (card) {
                toggleCardActions(card);
                event.preventDefault();
            }
            return;
        }

        if (!event.target.closest('[data-anime-card-actions]')) {
            hideAllCardActions();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideAllCardActions();
            return;
        }

        const trigger = event.target.closest ? event.target.closest('[data-anime-card-trigger]') : null;
        if (!trigger) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            const card = trigger.closest('[data-anime-card]');
            if (card) {
                event.preventDefault();
                toggleCardActions(card);
            }
        }
    });

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

    const CATALOG_SORTING = {
        top: true,
        new: true,
    };

    function createAnimeCard(release) {
        const card = document.createElement('article');
        card.className = 'anime-card';
        card.dataset.animeCard = 'true';

        const title = release?.title || 'Без названия';
        const posterUrl = release?.poster_url || '';

        const favoritePayload = createFavoritePayload(release, title, posterUrl);

        const link = document.createElement('a');
        link.className = 'anime-card__link';
        const watchUrl = createWatchUrl(favoritePayload);
        link.href = watchUrl || '#';
        link.dataset.animeCardTrigger = 'true';
        link.setAttribute('aria-haspopup', 'true');
        link.setAttribute('aria-expanded', 'false');
        link.setAttribute('aria-label', `Открыть варианты действий для «${title}»`);

        if (posterUrl) {
            const poster = document.createElement('img');
            poster.className = 'anime-card__image';
            poster.loading = 'lazy';
            poster.decoding = 'async';
            poster.alt = `Постер аниме «${title}»`;
            poster.src = posterUrl;
            link.appendChild(poster);
        } else {
            const placeholder = document.createElement('div');
            placeholder.className = 'anime-card__placeholder';
            placeholder.textContent = 'Нет постера';
            link.appendChild(placeholder);
        }

        const overlay = document.createElement('div');
        overlay.className = 'anime-card__overlay';

        const heading = document.createElement('h3');
        heading.className = 'anime-card__title';
        heading.textContent = title;
        overlay.appendChild(heading);

        const metaParts = [];
        if (release?.type) {
            metaParts.push(release.type);
        }
        if (release?.year) {
            metaParts.push(String(release.year));
        }
        if (release?.episodes_total) {
            metaParts.push(`${release.episodes_total} эп.`);
        }

        if (metaParts.length > 0) {
            const meta = document.createElement('p');
            meta.className = 'anime-card__meta';
            meta.textContent = metaParts.join(' • ');
            overlay.appendChild(meta);
        }

        link.appendChild(overlay);

        card.appendChild(link);

        const favoriteButton = createFavoriteButton(favoritePayload);
        if (favoritePayload?.id) {
            card.dataset.animeId = String(favoritePayload.id);
        }

        const actions = document.createElement('div');
        actions.className = 'anime-card__actions';
        actions.dataset.animeCardActions = 'true';
        actions.setAttribute('aria-hidden', 'true');

        const watchAction = document.createElement('a');
        watchAction.className = 'anime-card__action anime-card__action--watch';
        watchAction.href = watchUrl || '#';
        watchAction.textContent = 'Смотреть';
        actions.appendChild(watchAction);

        if (favoriteButton) {
            actions.appendChild(favoriteButton);
        }

        const detailsAction = document.createElement('a');
        detailsAction.className = 'anime-card__action anime-card__action--details';
        detailsAction.href = '/details';
        detailsAction.textContent = 'Описание';
        actions.appendChild(detailsAction);

        card.appendChild(actions);

        return card;
    }

    function createStatusUpdater(statusElement) {
        return function updateStatus(message, options = {}) {
            if (!statusElement) {
                return;
            }

            const { showSpinner = true, hidden = false } = options;
            const spinner = statusElement.querySelector('.anime-list__status-spinner');
            const text = statusElement.querySelector('.anime-list__status-text');

            if (hidden) {
                statusElement.hidden = true;
                return;
            }

            statusElement.hidden = false;
            if (text) {
                text.textContent = message;
            }

            if (spinner) {
                spinner.style.display = showSpinner ? '' : 'none';
            }
        };
    }

    function initCatalogList(animeList) {
        const mode = animeList.dataset.mode || '';
        if (!Object.prototype.hasOwnProperty.call(CATALOG_SORTING, mode)) {
            return;
        }

        const grid = animeList.querySelector('[data-anime-grid]');
        const statusElement = animeList.querySelector('[data-anime-status]');
        const moreButton = animeList.querySelector('[data-load-more]');
        const updateStatus = createStatusUpdater(statusElement);

        let currentPage = 0;
        let loading = false;
        let hasNextPage = true;

        function buildApiUrl(page) {
            const url = new URL(`/api/catalog/${encodeURIComponent(mode)}`, APP_BASE_URL);
            url.searchParams.set('page', String(page));
            return url.toString();
        }

        async function fetchPage(page) {
            const response = await fetch(buildApiUrl(page));
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();
            const releases = Array.isArray(payload?.data) ? payload.data : [];
            const hasNext = Boolean(payload?.meta?.has_next_page);

            return { releases, hasNext };
        }

        async function loadNextPage() {
            if (loading || !hasNextPage) {
                return;
            }

            loading = true;
            const nextPage = currentPage + 1;
            updateStatus(nextPage === 1 ? 'Загружаем подборку…' : 'Загружаем ещё тайтлы…', { showSpinner: true });
            if (moreButton) {
                moreButton.disabled = true;
            }

            try {
                const { releases, hasNext } = await fetchPage(nextPage);

                if (nextPage === 1 && releases.length === 0) {
                    updateStatus('Мы не нашли тайтлов для этой подборки.', { showSpinner: false });
                    hasNextPage = false;
                    return;
                }

                if (grid) {
                    grid.hidden = false;
                    releases.forEach((release) => {
                        grid.appendChild(createAnimeCard(release));
                    });
                }

                currentPage = nextPage;
                hasNextPage = hasNext;

                if (hasNextPage) {
                    updateStatus('', { hidden: true });
                    if (moreButton) {
                        moreButton.hidden = false;
                        moreButton.disabled = false;
                    }
                } else {
                    updateStatus('', { hidden: true });
                    if (moreButton) {
                        moreButton.hidden = true;
                    }
                }
            } catch (error) {
                console.error('Failed to load anime list', error);
                updateStatus('Не удалось загрузить данные. Попробуйте обновить страницу.', { showSpinner: false });
                hasNextPage = false;
                if (moreButton) {
                    moreButton.hidden = true;
                }
            } finally {
                loading = false;
                if (moreButton) {
                    moreButton.disabled = false;
                }
            }
        }

        if (moreButton) {
            moreButton.addEventListener('click', () => {
                loadNextPage();
            });
        }

        loadNextPage();
    }

    function initSearch(form, animeList, initialQuery = '') {
        const input = form ? form.querySelector('[data-anime-search-input]') : null;
        if (form && !input) {
            return;
        }

        const grid = animeList.querySelector('[data-anime-grid]');
        const statusElement = animeList.querySelector('[data-anime-status]');
        const moreButton = animeList.querySelector('[data-load-more]');
        const updateStatus = createStatusUpdater(statusElement);

        let currentQuery = '';
        let currentPage = 0;
        let hasNextPage = false;
        let loading = false;
        let activeRequestId = 0;

        function resetResults() {
            activeRequestId += 1;

            if (grid) {
                grid.innerHTML = '';
                grid.hidden = true;
            }

            currentPage = 0;
            hasNextPage = false;
            loading = false;

            if (moreButton) {
                moreButton.hidden = true;
                moreButton.disabled = false;
            }
        }

        function ensureAbsoluteImageUrl(url) {
            if (typeof url !== 'string') {
                return '';
            }

            const trimmed = url.trim();
            if (trimmed === '') {
                return '';
            }

            if (/^https?:\/\//i.test(trimmed)) {
                return trimmed;
            }

            if (trimmed.startsWith('//')) {
                return `https:${trimmed}`;
            }

            if (trimmed.startsWith('/')) {
                return `${ANILIBRIA_BASE_URL}${trimmed}`;
            }

            return `${ANILIBRIA_BASE_URL}/${trimmed}`;
        }

        function normalizeSearchRelease(release) {
            if (!release || typeof release !== 'object') {
                return null;
            }

            const id = release.id ?? null;
            if (id === null) {
                return null;
            }

            const titleCandidates = [
                typeof release.title === 'string' ? release.title : null,
                release?.name?.main ?? null,
                release?.name?.english ?? null,
                release?.name?.alternative ?? null,
            ];

            const title = titleCandidates.find((value) => typeof value === 'string' && value.trim().length > 0) ||
                'Без названия';

            const englishCandidates = [
                release?.title_english ?? null,
                release?.name?.english ?? null,
                release?.name?.alternative ?? null,
            ];

            const englishTitleCandidate = englishCandidates.find((value) => typeof value === 'string' && value.trim().length > 0)
                || null;
            const englishTitle = englishTitleCandidate ? englishTitleCandidate.trim() : null;

            const posterCandidates = [
                release.poster_url ?? null,
                release?.poster?.optimized?.preview ?? null,
                release?.poster?.optimized?.src ?? null,
                release?.poster?.optimized?.thumbnail ?? null,
                release?.poster?.preview ?? null,
                release?.poster?.src ?? null,
                release?.poster?.thumbnail ?? null,
            ];

            const poster = posterCandidates.find((value) => typeof value === 'string' && value.trim().length > 0) || '';
            const posterUrl = ensureAbsoluteImageUrl(poster);
            const fallbackPoster =
                typeof release.poster_url === 'string' && release.poster_url.trim().length > 0
                    ? release.poster_url.trim()
                    : '';
            const normalizedPoster = posterUrl || ensureAbsoluteImageUrl(fallbackPoster);
            const finalPoster = normalizedPoster && normalizedPoster.trim().length > 0 ? normalizedPoster : null;

            let type = release.type ?? null;
            if (type && typeof type === 'object') {
                type = type.description || type.value || null;
            }
            if (typeof type === 'string') {
                type = type.trim();
            }
            if (type === '') {
                type = null;
            }

            const yearValue = release.year ?? null;
            const parsedYear = typeof yearValue === 'number'
                ? yearValue
                : Number.parseInt(String(yearValue ?? '').trim(), 10);
            const normalizedYear = Number.isNaN(parsedYear) ? null : parsedYear;

            const episodesValue = release.episodes_total ?? null;
            const parsedEpisodes = typeof episodesValue === 'number'
                ? episodesValue
                : Number.parseInt(String(episodesValue ?? '').trim(), 10);
            const normalizedEpisodes = Number.isNaN(parsedEpisodes) ? null : parsedEpisodes;

            const alias = typeof release.alias === 'string' && release.alias.trim().length > 0
                ? release.alias.trim()
                : null;

            return {
                id,
                title,
                title_english: englishTitle,
                poster_url: finalPoster,
                type,
                year: normalizedYear,
                episodes_total: normalizedEpisodes,
                alias,
            };
        }

        function buildSearchUrl(query, page) {
            const url = new URL(ANILIBRIA_SEARCH_URL);
            url.searchParams.set('query', query);
            if (page > 1) {
                url.searchParams.set('page', String(page));
            }
            return url.toString();
        }

        async function fetchSearchPage(query, page) {
            const response = await fetch(buildSearchUrl(query, page));
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();
            let rawReleases = [];
            let hasNext = false;

            if (Array.isArray(payload?.data)) {
                rawReleases = payload.data;
                hasNext = Boolean(payload?.meta?.has_next_page);
            } else if (Array.isArray(payload)) {
                rawReleases = payload;
                hasNext = false;
            }

            const releases = rawReleases
                .map((item) => normalizeSearchRelease(item))
                .filter(Boolean);

            return { releases, hasNext };
        }

        async function loadNextPage() {
            if (loading || !hasNextPage) {
                return;
            }

            const requestId = ++activeRequestId;
            const query = currentQuery;
            loading = true;
            const nextPage = currentPage + 1;
            updateStatus(nextPage === 1 ? `Ищем «${query}»…` : 'Загружаем ещё результаты…', { showSpinner: true });
            if (moreButton) {
                moreButton.disabled = true;
            }

            try {
                const { releases, hasNext } = await fetchSearchPage(query, nextPage);

                if (requestId !== activeRequestId || query !== currentQuery) {
                    return;
                }

                if (nextPage === 1 && releases.length === 0) {
                    updateStatus(`Мы не нашли тайтлов по запросу «${query}».`, { showSpinner: false });
                    hasNextPage = false;
                    return;
                }

                if (grid) {
                    grid.hidden = false;
                    releases.forEach((release) => {
                        grid.appendChild(createAnimeCard(release));
                    });
                }

                currentPage = nextPage;
                hasNextPage = hasNext;

                if (hasNextPage) {
                    updateStatus('', { hidden: true });
                    if (moreButton) {
                        moreButton.hidden = false;
                        moreButton.disabled = false;
                    }
                } else {
                    updateStatus('', { hidden: true });
                    if (moreButton) {
                        moreButton.hidden = true;
                    }
                }
            } catch (error) {
                console.error('Failed to search anime', error);
                if (requestId !== activeRequestId || query !== currentQuery) {
                    return;
                }

                updateStatus('Не удалось выполнить поиск. Попробуйте ещё раз позже.', { showSpinner: false });
                hasNextPage = false;
                if (moreButton) {
                    moreButton.hidden = true;
                }
            } finally {
                if (requestId === activeRequestId) {
                    loading = false;
                    if (moreButton) {
                        moreButton.disabled = false;
                    }
                }
            }
        }

        if (form && input) {
            form.addEventListener('submit', (event) => {
                event.preventDefault();

                const query = String(input.value || '').trim();
                if (query.length === 0) {
                    currentQuery = '';
                    resetResults();
                    updateStatus('Укажите поисковый запрос.', { showSpinner: false });
                    return;
                }

                currentQuery = query;
                resetResults();
                hasNextPage = true;
                loadNextPage();
            });
        }

        if (moreButton) {
            moreButton.addEventListener('click', (event) => {
                event.preventDefault();
                loadNextPage();
            });
        }

        const preparedInitialQuery = String(initialQuery || '').trim();
        if (preparedInitialQuery.length > 0) {
            if (input) {
                input.value = preparedInitialQuery;
            }
            currentQuery = preparedInitialQuery;
            resetResults();
            hasNextPage = true;
            loadNextPage();
        }
    }

    document.querySelectorAll('[data-anime-list]').forEach((listElement) => {
        initCatalogList(listElement);
    });

    const searchResults = document.querySelector('[data-anime-search-results]');
    if (searchResults) {
        const searchForm = document.querySelector('[data-anime-search-form]');
        const initialQuery = searchResults.getAttribute('data-search-query') || '';
        initSearch(searchForm, searchResults, initialQuery);
    }
})();

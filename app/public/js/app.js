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

    const animeList = document.querySelector('[data-anime-list]');
    if (animeList) {
        const mode = animeList.dataset.mode || '';
        const grid = animeList.querySelector('[data-anime-grid]');
        const statusElement = animeList.querySelector('[data-anime-status]');
        const moreButton = animeList.querySelector('[data-load-more]');

        const SORTING = {
            top: 'RATING_DESC',
            new: 'FRESH_AT_DESC',
        };

        const API_BASE_URL = 'https://anilibria.top';

        function buildApiUrl(page) {
            const sorting = SORTING[mode];
            if (!sorting) {
                return '';
            }

            const url = new URL('/api/v1/anime/catalog/releases', API_BASE_URL);
            url.searchParams.set('f[sorting]', sorting);
            url.searchParams.set('page', String(page));
            return url.toString();
        }

        function updateStatus(message, options = {}) {
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
        }

        function createCard(release) {
            const card = document.createElement('article');
            card.className = 'anime-card';

            const title = release?.name?.main || release?.name?.alternative || 'Без названия';
            const posterPath = release?.poster?.optimized?.preview
                || release?.poster?.preview
                || release?.poster?.src
                || '';

            if (posterPath) {
                const poster = document.createElement('img');
                poster.className = 'anime-card__image';
                poster.loading = 'lazy';
                poster.decoding = 'async';
                poster.alt = `Постер аниме «${title}»`;
                poster.src = new URL(posterPath, API_BASE_URL).toString();
                card.appendChild(poster);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'anime-card__placeholder';
                placeholder.textContent = 'Нет постера';
                card.appendChild(placeholder);
            }

            const overlay = document.createElement('div');
            overlay.className = 'anime-card__overlay';

            const heading = document.createElement('h3');
            heading.className = 'anime-card__title';
            heading.textContent = title;
            overlay.appendChild(heading);

            const metaParts = [];
            if (release?.type?.description) {
                metaParts.push(release.type.description);
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

            card.appendChild(overlay);
            return card;
        }

        async function fetchPage(page) {
            const url = buildApiUrl(page);
            if (!url) {
                return { releases: [], hasNext: false };
            }

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();
            const releases = Array.isArray(payload?.data) ? payload.data : [];
            const hasNext = Boolean(payload?.meta?.pagination?.links?.next);

            return { releases, hasNext };
        }

        if (!SORTING[mode]) {
            updateStatus('', { hidden: true });
            return;
        }

        let currentPage = 0;
        let loading = false;
        let hasNextPage = true;

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
                        grid.appendChild(createCard(release));
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
})();

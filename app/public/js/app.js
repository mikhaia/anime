(function () {
    const API_BASE_URL = 'https://anilibria.top';

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

    const CATALOG_SORTING = {
        top: 'RATING_DESC',
        new: 'FRESH_AT_DESC'
    };

    function createAnimeCard(release) {
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

        const sorting = CATALOG_SORTING[mode];
        if (!sorting) {
            updateStatus('', { hidden: true });
            return;
        }

        let currentPage = 0;
        let loading = false;
        let hasNextPage = true;

        function buildApiUrl(page) {
            const url = new URL('/api/v1/anime/catalog/releases', API_BASE_URL);
            url.searchParams.set('f[sorting]', sorting);
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
            const hasNext = Boolean(payload?.meta?.pagination?.links?.next);

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

    function initSearch(form, animeList) {
        const input = form.querySelector('[data-anime-search-input]');
        if (!input) {
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

        function buildSearchUrl(query, page) {
            const url = new URL('/api/v1/anime/catalog/releases', API_BASE_URL);
            url.searchParams.set('search', query);
            url.searchParams.set('page', String(page));
            return url.toString();
        }

        async function fetchSearchPage(query, page) {
            const response = await fetch(buildSearchUrl(query, page));
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();
            const releases = Array.isArray(payload?.data) ? payload.data : [];
            const hasNext = Boolean(payload?.meta?.pagination?.links?.next);

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

        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const query = String(input.value || '').trim();
            if (query.length === 0) {
                currentQuery = '';
                resetResults();
                updateStatus('Введите название аниме для поиска.', { showSpinner: false });
                return;
            }

            currentQuery = query;
            resetResults();
            hasNextPage = true;
            loadNextPage();
        });

        if (moreButton) {
            moreButton.addEventListener('click', (event) => {
                event.preventDefault();
                loadNextPage();
            });
        }
    }

    document.querySelectorAll('[data-anime-list]').forEach((listElement) => {
        initCatalogList(listElement);
    });

    const searchForm = document.querySelector('[data-anime-search-form]');
    const searchResults = document.querySelector('[data-anime-search-results]');

    if (searchForm && searchResults) {
        initSearch(searchForm, searchResults);
    }
})();

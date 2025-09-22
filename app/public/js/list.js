(function () {
    const APP_BASE_URL = window.location.origin;
    const ANILIBRIA_BASE_URL = 'https://anilibria.top';
    const APP_SEARCH_URL = new URL('/api/anime/search', APP_BASE_URL).toString();
    const ANILIBRIA_SEARCH_URL = `${ANILIBRIA_BASE_URL}/api/v1/app/search/releases`;

    const favoritesModule = window.NeAnime?.favorites || {};
    const cardsModule = window.NeAnime?.cards || {};
    const createFavoritePayload =
        typeof favoritesModule.createPayload === 'function' ? favoritesModule.createPayload : null;
    const createFavoriteButton =
        typeof favoritesModule.createButton === 'function' ? favoritesModule.createButton : null;

    const CATALOG_SORTING = {
        top: true,
        new: true,
    };

    function resolveWatchIdentifier(release, payload) {
        if (payload) {
            return payload.alias || payload.id || null;
        }

        if (typeof release?.alias === 'string' && release.alias.trim().length > 0) {
            return release.alias.trim();
        }

        if (release?.id !== undefined && release?.id !== null) {
            return release.id;
        }

        return null;
    }

    function createWatchUrl(release, payload) {
        const identifier = resolveWatchIdentifier(release, payload);
        if (!identifier) {
            return '#';
        }

        return `/watch/${encodeURIComponent(String(identifier))}`;
    }

    function createAnimeCard(release) {
        const card = document.createElement('article');
        card.className = 'anime-card';
        card.dataset.animeCard = 'true';

        const title = release?.title || 'Без названия';
        const posterUrl = release?.poster_url || '';

        const favoritePayload = createFavoritePayload
            ? createFavoritePayload(release, title, posterUrl)
            : null;

        const link = document.createElement('a');
        link.className = 'anime-card__link';
        const watchUrl = createWatchUrl(release, favoritePayload);
        link.href = watchUrl;
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

        const cardIdentifier = favoritePayload?.id ?? release?.id ?? null;
        if (cardIdentifier !== null && cardIdentifier !== undefined) {
            card.dataset.animeId = String(cardIdentifier);
        }

        const actions = document.createElement('div');
        actions.className = 'anime-card__actions';
        actions.dataset.animeCardActions = 'true';
        actions.setAttribute('aria-hidden', 'true');

        function wrapAction(action) {
            const section = document.createElement('div');
            section.className = 'anime-card__actions-section';
            section.appendChild(action);
            return section;
        }

        const detailsAction = document.createElement('a');
        detailsAction.className = 'anime-card__action anime-card__action--details';
        detailsAction.href = '/details';
        detailsAction.textContent = 'Описание';
        const watchAction = document.createElement('a');
        watchAction.className = 'anime-card__action anime-card__action--watch';
        watchAction.href = watchUrl;
        watchAction.textContent = 'Смотреть';

        actions.appendChild(wrapAction(watchAction));
        actions.appendChild(wrapAction(detailsAction));

        let favoriteControl = null;
        if (createFavoriteButton) {
            favoriteControl = createFavoriteButton(favoritePayload);
        }

        if (!favoriteControl) {
            const placeholder = document.createElement('button');
            placeholder.type = 'button';
            placeholder.className = 'anime-card__action anime-card__action--favorite anime-card__favorite';
            placeholder.textContent = 'В избранное';
            placeholder.disabled = true;
            placeholder.setAttribute('aria-disabled', 'true');
            favoriteControl = placeholder;
        }

        actions.appendChild(wrapAction(favoriteControl));

        if (typeof cardsModule.normalizeActions === 'function') {
            cardsModule.normalizeActions(actions);
        }

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

            const nextPage = currentPage + 1;
            loading = true;
            updateStatus(nextPage === 1 ? 'Загружаем подборку…' : 'Загружаем ещё тайтлы…', { showSpinner: true });
            if (moreButton) {
                moreButton.disabled = true;
            }

            try {
                const { releases, hasNext } = await fetchPage(nextPage);

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
                console.error('Failed to load catalog', error);
                updateStatus('Не удалось загрузить список. Попробуйте обновить страницу позже.', { showSpinner: false });
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

            const title =
                titleCandidates.find((value) => typeof value === 'string' && value.trim().length > 0) || 'Без названия';

            const englishCandidates = [
                release?.title_english ?? null,
                release?.name?.english ?? null,
                release?.name?.alternative ?? null,
            ];

            const englishTitleCandidate = englishCandidates.find(
                (value) => typeof value === 'string' && value.trim().length > 0,
            ) || null;
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
            const parsedYear =
                typeof yearValue === 'number' ? yearValue : Number.parseInt(String(yearValue ?? '').trim(), 10);
            const normalizedYear = Number.isNaN(parsedYear) ? null : parsedYear;

            const episodesValue = release.episodes_total ?? null;
            const parsedEpisodes =
                typeof episodesValue === 'number' ? episodesValue : Number.parseInt(String(episodesValue ?? '').trim(), 10);
            const normalizedEpisodes = Number.isNaN(parsedEpisodes) ? null : parsedEpisodes;

            const alias =
                typeof release.alias === 'string' && release.alias.trim().length > 0 ? release.alias.trim() : null;

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

        let appSearchUnavailable = false;

        function buildAppSearchUrl(query, page) {
            const url = new URL(APP_SEARCH_URL);
            url.searchParams.set('query', query);
            if (page > 1) {
                url.searchParams.set('page', String(page));
            }
            return url.toString();
        }

        function buildExternalSearchUrl(query, page) {
            const url = new URL(ANILIBRIA_SEARCH_URL);
            url.searchParams.set('query', query);
            if (page > 1) {
                url.searchParams.set('page', String(page));
            }
            return url.toString();
        }

        function extractSearchPayload(payload) {
            let rawReleases = [];
            let hasNext = false;

            if (Array.isArray(payload?.data)) {
                rawReleases = payload.data;
                hasNext = Boolean(payload?.meta?.has_next_page);
            } else if (Array.isArray(payload)) {
                rawReleases = payload;
                hasNext = false;
            }

            const releases = rawReleases.map((item) => normalizeSearchRelease(item)).filter(Boolean);

            return { releases, hasNext };
        }

        async function fetchFromAppSearch(query, page) {
            if (appSearchUnavailable) {
                throw new Error('App search endpoint is unavailable');
            }

            let response;
            try {
                response = await fetch(buildAppSearchUrl(query, page), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                    },
                });
            } catch (error) {
                appSearchUnavailable = true;
                throw error instanceof Error ? error : new Error('Failed to reach app search endpoint');
            }

            if (!response.ok) {
                if (response.status === 404 || response.status >= 500) {
                    appSearchUnavailable = true;
                }

                throw new Error(`Request failed with status ${response.status}`);
            }

            try {
                const payload = await response.json();
                return extractSearchPayload(payload);
            } catch (error) {
                appSearchUnavailable = true;
                throw error instanceof Error ? error : new Error('Failed to parse app search response');
            }
        }

        async function fetchFromExternalSearch(query, page) {
            const response = await fetch(buildExternalSearchUrl(query, page));
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const payload = await response.json();
            return extractSearchPayload(payload);
        }

        function persistSearchResults(query, page) {
            if (appSearchUnavailable) {
                return;
            }

            try {
                const storageKey = 'neanime.search';
                const stored = window.localStorage.getItem(storageKey);
                const parsed = stored ? JSON.parse(stored) : {};
                parsed.latest = {
                    query,
                    page,
                    timestamp: Date.now(),
                };
                window.localStorage.setItem(storageKey, JSON.stringify(parsed));
            } catch (error) {
                console.warn('Failed to persist search results', error);
            }
        }

        async function fetchSearchPage(query, page) {
            try {
                const result = await fetchFromAppSearch(query, page);
                persistSearchResults(query, page);
                return result;
            } catch (error) {
                console.warn('Primary search failed, falling back to external source', error);

                try {
                    const fallbackResult = await fetchFromExternalSearch(query, page);
                    persistSearchResults(query, page);
                    return fallbackResult;
                } catch (fallbackError) {
                    console.error('Fallback search failed', fallbackError);
                    throw fallbackError;
                }
            }
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

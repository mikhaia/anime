(function () {
    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function initWatchPage() {
        const player = document.querySelector('[data-watch-player]');
        const playlistItems = Array.from(document.querySelectorAll('[data-episode-item]'));
        const watchContainer = document.querySelector('[data-watch-anime]');
        const animeId = watchContainer?.dataset?.animeId;
        const initialEpisodeNumber = Number.parseInt(watchContainer?.dataset?.activeEpisodeNumber ?? '', 10);
        let isAuthenticated = document.body?.dataset?.authenticated === 'true';
        let lastSavedEpisode = Number.isFinite(initialEpisodeNumber) ? initialEpisodeNumber : null;
        let saveInFlight = null;
        const qualityContainer = document.querySelector('[data-watch-quality]');
        const initialQualityButton = qualityContainer?.querySelector('[data-quality-option][aria-checked="true"]') || null;
        let preferredQuality = initialQualityButton?.dataset?.quality ?? null;
        let qualityButtons = Array.from(qualityContainer?.querySelectorAll('[data-quality-option]') || []);
        let currentStreamOptions = {};

        const navLinks = Array.from(document.querySelectorAll('.navbar .nav-links .nav-link'));
        const navActions = Array.from(document.querySelectorAll('.navbar .nav-actions .nav-button'));
        const previousEpisodeButton = document.querySelector('[data-episode-previous]');
        const nextEpisodeButton = document.querySelector('[data-episode-next]');

        if (!player || playlistItems.length === 0) {
            return;
        }

        const titleElement = document.querySelector('[data-watch-title]');
        const descriptionElement = document.querySelector('[data-watch-description]');
        const durationElement = document.querySelector('[data-watch-duration]');
        let hlsInstance = null;

        function normalizeStreams(streams) {
            if (!streams || typeof streams !== 'object') {
                return {};
            }

            const entries = Object.entries(streams)
                .filter((entry) => {
                    const [quality, url] = entry;
                    return typeof quality === 'string' && quality.trim() !== '' && typeof url === 'string' && url.trim() !== '';
                })
                .map(([quality, url]) => [quality.trim(), url.trim()]);

            if (entries.length === 0) {
                return {};
            }

            entries.sort((left, right) => {
                const parseQuality = (value) => {
                    const match = String(value).match(/(\d+)/);
                    return match ? Number.parseInt(match[1], 10) : 0;
                };

                return parseQuality(right[0]) - parseQuality(left[0]);
            });

            const normalized = {};
            entries.forEach(([quality, url]) => {
                normalized[quality] = url;
            });

            return normalized;
        }

        function setQualityContainerDisabled(disabled) {
            if (!qualityContainer) {
                return;
            }

            if (disabled) {
                qualityContainer.setAttribute('data-disabled', 'true');
                qualityContainer.setAttribute('aria-disabled', 'true');
                qualityContainer.disabled = true;
            } else {
                qualityContainer.removeAttribute('data-disabled');
                qualityContainer.removeAttribute('aria-disabled');
                qualityContainer.disabled = false;
            }
        }

        function setQualitySelection(quality) {
            if (!qualityContainer) {
                return;
            }

            qualityButtons.forEach((button) => {
                const buttonQuality = button?.dataset?.quality || null;
                const isActive = Boolean(quality && buttonQuality === quality);

                button.classList.toggle('watch-player__quality-button--active', isActive);
                button.setAttribute('aria-checked', isActive ? 'true' : 'false');
                button.tabIndex = isActive ? 0 : -1;
            });
        }

        function focusQualityControls() {
            if (!qualityContainer) {
                return;
            }

            const enabledButtons = qualityButtons.filter((button) => button && !button.disabled);
            if (enabledButtons.length === 0) {
                return;
            }

            const activeButton = enabledButtons.find((button) => button.classList.contains('watch-player__quality-button--active'))
                || enabledButtons[0];

            if (activeButton) {
                focusElement(activeButton, { preventScroll: true });
            }
        }

        function selectQuality(quality, { shouldLoad = true, updatePreference = true } = {}) {
            if (!quality || !currentStreamOptions[quality]) {
                return;
            }

            setQualitySelection(quality);

            if (updatePreference) {
                preferredQuality = quality;
            }

            if (shouldLoad) {
                loadStream(currentStreamOptions[quality]);
            }
        }

        function moveQualitySelection(offset) {
            if (!Number.isFinite(offset) || offset === 0) {
                return null;
            }

            const enabledButtons = qualityButtons.filter((button) => button && !button.disabled);
            if (enabledButtons.length === 0) {
                return null;
            }

            const currentIndex = enabledButtons.findIndex((button) => button.classList.contains('watch-player__quality-button--active'));
            const safeIndex = currentIndex >= 0 ? currentIndex : 0;
            const nextIndex = safeIndex + offset;

            if (nextIndex < 0 || nextIndex >= enabledButtons.length) {
                return null;
            }

            const nextButton = enabledButtons[nextIndex];
            const nextQuality = nextButton?.dataset?.quality || null;

            if (!nextQuality || !currentStreamOptions[nextQuality]) {
                return null;
            }

            selectQuality(nextQuality, { shouldLoad: true, updatePreference: true });
            focusElement(nextButton, { preventScroll: true });

            return nextButton;
        }

        function handleQualityButtonKeydown(event, button) {
            if (!button) {
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                focusFirstNavItem();
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                focusActivePlaylistOrPlayer();
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                const moved = moveQualitySelection(-1);
                if (!moved) {
                    const activeItem = getActiveItem();
                    if (activeItem) {
                        focusElement(activeItem, { preventScroll: true });
                    }
                }
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                const moved = moveQualitySelection(1);
                if (!moved) {
                    const activeItem = getActiveItem();
                    if (activeItem) {
                        focusElement(activeItem, { preventScroll: true });
                    }
                }
                return;
            }

            if (event.key === 'MediaPlayPause') {
                event.preventDefault();
                event.stopPropagation();
                togglePlayback();
                return;
            }

            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                button.click();
            }
        }

        function updateQualityOptions(streams, defaultQuality) {
            if (!qualityContainer) {
                return null;
            }

            const normalizedStreams = normalizeStreams(streams);
            const qualities = Object.keys(normalizedStreams);

            currentStreamOptions = normalizedStreams;
            qualityContainer.innerHTML = '';
            qualityButtons = [];

            if (qualities.length === 0) {
                setQualityContainerDisabled(true);
                return null;
            }

            const desiredQuality = (() => {
                if (preferredQuality && normalizedStreams[preferredQuality]) {
                    return preferredQuality;
                }

                if (defaultQuality && normalizedStreams[defaultQuality]) {
                    return defaultQuality;
                }

                return qualities[0] ?? null;
            })();

            qualities.forEach((quality) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'watch-player__quality-button';
                button.textContent = quality;
                button.dataset.quality = quality;
                button.setAttribute('data-quality-option', '');
                button.setAttribute('role', 'radio');
                button.setAttribute('aria-checked', 'false');
                button.tabIndex = -1;

                if (qualities.length <= 1) {
                    button.disabled = true;
                }

                button.addEventListener('click', () => {
                    if (!currentStreamOptions[quality]) {
                        return;
                    }

                    selectQuality(quality, { shouldLoad: true, updatePreference: true });
                });

                button.addEventListener('keydown', (event) => {
                    handleQualityButtonKeydown(event, button);
                });

                qualityContainer.append(button);
                qualityButtons.push(button);
            });

            setQualityContainerDisabled(qualities.length <= 1);
            setQualitySelection(desiredQuality);

            return desiredQuality ? normalizedStreams[desiredQuality] : null;
        }

        function detachHls() {
            if (hlsInstance) {
                hlsInstance.destroy();
                hlsInstance = null;
            }
        }

        function loadStream(url) {
            if (!url) {
                return;
            }

            detachHls();

            if (player.canPlayType('application/vnd.apple.mpegurl')) {
                player.src = url;
                player.load();
            } else if (window.Hls) {
                hlsInstance = new window.Hls();
                hlsInstance.loadSource(url);
                hlsInstance.attachMedia(player);
            } else {
                player.src = url;
                player.load();
            }
        }

        function rememberEpisode(episodeNumber) {
            if (!animeId || !Number.isFinite(episodeNumber)) {
                return;
            }

            if (!isAuthenticated) {
                return;
            }

            if (episodeNumber === lastSavedEpisode && !saveInFlight) {
                return;
            }

            if (saveInFlight) {
                saveInFlight.abort();
                saveInFlight = null;
            }

            const controller = new AbortController();
            saveInFlight = controller;

            fetch('/watch-progress', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    anime_id: Number.parseInt(animeId, 10),
                    episode_number: episodeNumber,
                }),
                signal: controller.signal,
            })
                .then((response) => {
                    if (response.status === 401) {
                        isAuthenticated = false;
                        return null;
                    }

                    if (!response.ok) {
                        throw new Error(`Request failed with status ${response.status}`);
                    }

                    return response.json();
                })
                .then((payload) => {
                    if (!payload) {
                        return;
                    }

                    if (Number.isFinite(payload?.progress?.episode_number)) {
                        lastSavedEpisode = payload.progress.episode_number;
                    } else {
                        lastSavedEpisode = episodeNumber;
                    }
                })
                .catch((error) => {
                    if (error.name !== 'AbortError') {
                        console.warn('Failed to save watch progress', error);
                    }
                })
                .finally(() => {
                    if (saveInFlight === controller) {
                        saveInFlight = null;
                    }
                });
        }

        function setActive(item) {
            playlistItems.forEach((entry) => {
                entry.classList.toggle('watch-playlist__item--active', entry === item);
            });

            updateEpisodeNavigationButtons();

            const {
                episodeTitle,
                episodeDescription,
                episodeDuration,
                episodeStream,
                episodeStreams,
                episodeDefaultQuality,
            } = item.dataset;
            const episodeNumber = Number.parseInt(item.dataset.episodeNumber ?? '', 10);
            let selectedStream = episodeStream || '';

            if (episodeStreams) {
                try {
                    const parsed = JSON.parse(episodeStreams);
                    const stream = updateQualityOptions(parsed, episodeDefaultQuality || null);
                    if (stream) {
                        selectedStream = stream;
                    }
                } catch (error) {
                    console.warn('Failed to parse episode streams', error);
                    updateQualityOptions({}, null);
                }
            } else {
                updateQualityOptions({}, null);
            }

            if (titleElement) {
                titleElement.textContent = episodeTitle || '';
            }

            if (descriptionElement) {
                descriptionElement.textContent = episodeDescription || '';
            }

            if (durationElement) {
                durationElement.textContent = episodeDuration || '';
            }

            loadStream(selectedStream);
            rememberEpisode(episodeNumber);
        }

        function focusElement(element, { preventScroll = false, ensureVisible = true } = {}) {
            if (!element || typeof element.focus !== 'function') {
                return;
            }

            try {
                element.focus({ preventScroll });
            } catch (error) {
                element.focus();
            }

            if (ensureVisible) {
                try {
                    element.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                } catch (error) {
                    element.scrollIntoView();
                }
            }
        }

        function getActiveItem() {
            return playlistItems.find((entry) => entry.classList.contains('watch-playlist__item--active')) || null;
        }

        function focusFirstNavItem() {
            const target = navLinks[0] || navActions[0] || null;
            if (target) {
                focusElement(target, { preventScroll: true, ensureVisible: false });
            }
        }

        function focusActivePlaylistOrPlayer() {
            const activeItem = getActiveItem();
            if (activeItem) {
                focusElement(activeItem, { preventScroll: true });
                return;
            }

            if (player) {
                focusElement(player, { preventScroll: true });
            }
        }

        function updateEpisodeNavigationButtons() {
            const activeIndex = playlistItems.findIndex((entry) => entry.classList.contains('watch-playlist__item--active'));
            const hasEpisodes = playlistItems.length > 0 && activeIndex >= 0;
            const lastIndex = playlistItems.length - 1;

            if (previousEpisodeButton) {
                const shouldDisable = !hasEpisodes || activeIndex <= 0;
                previousEpisodeButton.disabled = shouldDisable;
                if (shouldDisable) {
                    previousEpisodeButton.setAttribute('aria-disabled', 'true');
                } else {
                    previousEpisodeButton.removeAttribute('aria-disabled');
                }
            }

            if (nextEpisodeButton) {
                const shouldDisable = !hasEpisodes || activeIndex >= lastIndex;
                nextEpisodeButton.disabled = shouldDisable;
                if (shouldDisable) {
                    nextEpisodeButton.setAttribute('aria-disabled', 'true');
                } else {
                    nextEpisodeButton.removeAttribute('aria-disabled');
                }
            }
        }

        function activateItemByOffset(offset, { focus = true } = {}) {
            if (!Number.isFinite(offset) || offset === 0) {
                return null;
            }

            const currentIndex = playlistItems.findIndex((entry) => entry.classList.contains('watch-playlist__item--active'));
            if (currentIndex < 0) {
                return null;
            }

            const nextIndex = Math.max(0, Math.min(playlistItems.length - 1, currentIndex + offset));
            if (nextIndex === currentIndex) {
                return null;
            }

            const nextItem = playlistItems[nextIndex];
            if (!nextItem) {
                return null;
            }

            setActive(nextItem);

            if (focus) {
                focusElement(nextItem);
            }

            return nextItem;
        }

        function togglePlayback() {
            if (!player) {
                return;
            }

            if (player.paused) {
                player.play().catch(() => {});
            } else {
                player.pause();
            }
        }

        const qualityControls = {
            container: qualityContainer,
            focus: focusQualityControls,
            isDisabled: () => {
                if (!qualityContainer) {
                    return true;
                }

                return qualityButtons.every((button) => !button || button.disabled);
            },
            getActiveButton: () => qualityButtons.find((button) => button.classList.contains('watch-player__quality-button--active')) || null,
        };

        const controlContext = {
            player,
            playlistItems,
            navLinks,
            navActions,
            qualitySelect: qualityContainer,
            qualityControls,
            setActive,
            getActiveItem,
            focusElement,
            focusFirstNavItem,
            focusActivePlaylistOrPlayer,
            activateItemByOffset,
            togglePlayback,
        };

        window.watchControlContext = controlContext;
        window.watchRemoteContext = controlContext;
        document.dispatchEvent(
            new CustomEvent('watch:control-context-ready', { detail: controlContext })
        );
        document.dispatchEvent(
            new CustomEvent('watch:remote-context-ready', { detail: controlContext })
        );

        playlistItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                setActive(item);
            });

        });

        if (previousEpisodeButton) {
            previousEpisodeButton.addEventListener('click', () => {
                activateItemByOffset(-1);
            });
        }

        if (nextEpisodeButton) {
            nextEpisodeButton.addEventListener('click', () => {
                activateItemByOffset(1);
            });
        }

        const activeItem = playlistItems.find((item) => item.dataset.active === 'true') || playlistItems[0] || null;
        if (activeItem) {
            setActive(activeItem);
            if (document.activeElement === document.body || document.activeElement === null) {
                focusElement(activeItem, { preventScroll: true, ensureVisible: false });
            }
        } else {
            updateEpisodeNavigationButtons();
        }
    }

    ready(initWatchPage);
})();

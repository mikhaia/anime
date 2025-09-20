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
        const qualitySelect = document.querySelector('[data-watch-quality]');
        let preferredQuality = qualitySelect?.value ?? null;

        const navLinks = Array.from(document.querySelectorAll('.navbar .nav-links .nav-link'));
        const navActions = Array.from(document.querySelectorAll('.navbar .nav-actions .nav-button'));

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

        function updateQualityOptions(streams, defaultQuality) {
            if (!qualitySelect) {
                return null;
            }

            const normalizedStreams = normalizeStreams(streams);
            const qualities = Object.keys(normalizedStreams);

            qualitySelect.innerHTML = '';

            qualities.forEach((quality) => {
                const option = document.createElement('option');
                option.value = quality;
                option.textContent = quality;
                qualitySelect.append(option);
            });

            if (qualities.length <= 1) {
                qualitySelect.disabled = true;
            } else {
                qualitySelect.disabled = false;
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

            if (desiredQuality) {
                qualitySelect.value = desiredQuality;
            }

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

        const controlContext = {
            player,
            playlistItems,
            navLinks,
            navActions,
            qualitySelect,
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

        if (qualitySelect) {
            qualitySelect.addEventListener('change', () => {
                const currentItem = getActiveItem();
                preferredQuality = qualitySelect.value || null;

                if (!currentItem) {
                    return;
                }

                const { episodeStreams } = currentItem.dataset;
                if (!episodeStreams) {
                    return;
                }

                try {
                    const parsed = JSON.parse(episodeStreams);
                    const normalized = normalizeStreams(parsed);
                    const selectedQuality = qualitySelect.value;
                    const streamUrl = normalized[selectedQuality];

                    if (streamUrl) {
                        loadStream(streamUrl);
                    }
                } catch (error) {
                    console.warn('Failed to switch quality', error);
                }
            });
        }

        const activeItem = playlistItems.find((item) => item.dataset.active === 'true') || playlistItems[0] || null;
        if (activeItem) {
            setActive(activeItem);
            if (document.activeElement === document.body || document.activeElement === null) {
                focusElement(activeItem, { preventScroll: true, ensureVisible: false });
            }
        }
    }

    ready(initWatchPage);
})();

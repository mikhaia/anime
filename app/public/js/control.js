(function () {
    let initialized = false;
    let currentContext = null;

    const GAMEPAD_AXIS_DEADZONE = 0.35;
    const GAMEPAD_AXIS_REPEAT_MS = 280;

    const connectedGamepads = new Map();
    let gamepadLoopId = null;

    function getFocusableTarget(context) {
        if (!context) {
            return null;
        }

        const active = document.activeElement;
        if (active && active !== document.body) {
            return active;
        }

        const activeItem = typeof context.getActiveItem === 'function' ? context.getActiveItem() : null;
        if (activeItem) {
            return activeItem;
        }

        if (context.playlistItems && context.playlistItems.length > 0) {
            return context.playlistItems[0];
        }

        return context.player || null;
    }

    function dispatchKeyToTarget(key, context, options = {}) {
        if (!context) {
            return;
        }

        const target = options.target || getFocusableTarget(context);
        if (!target || typeof target.dispatchEvent !== 'function') {
            if (key === 'ArrowUp' && typeof context.focusFirstNavItem === 'function') {
                context.focusFirstNavItem();
            } else if (key === 'ArrowDown' && typeof context.focusActivePlaylistOrPlayer === 'function') {
                context.focusActivePlaylistOrPlayer();
            }
            return;
        }

        const event = new KeyboardEvent('keydown', {
            key,
            bubbles: true,
            cancelable: true,
        });

        target.dispatchEvent(event);
    }

    function handleEpisodeOffset(offset, context) {
        if (!context || typeof context.activateItemByOffset !== 'function') {
            return;
        }

        const keepPlayerFocus = document.activeElement === context.player;
        const activated = context.activateItemByOffset(offset, { focus: !keepPlayerFocus });
        if (activated && keepPlayerFocus && typeof context.focusElement === 'function') {
            context.focusElement(context.player);
        }
    }

    function handleGamepadButtonPress(buttonIndex, context) {
        if (!context) {
            return;
        }

        switch (buttonIndex) {
            case 0: {
                dispatchKeyToTarget('Enter', context);
                break;
            }
            case 1: {
                dispatchKeyToTarget('ArrowUp', context);
                break;
            }
            case 2: {
                handleEpisodeOffset(-1, context);
                break;
            }
            case 3: {
                handleEpisodeOffset(1, context);
                break;
            }
            case 4:
            case 6: {
                handleEpisodeOffset(-1, context);
                break;
            }
            case 5:
            case 7: {
                handleEpisodeOffset(1, context);
                break;
            }
            case 8:
            case 9:
            case 16: {
                if (typeof context.togglePlayback === 'function') {
                    context.togglePlayback();
                }
                break;
            }
            case 10: {
                if (typeof context.focusFirstNavItem === 'function') {
                    context.focusFirstNavItem();
                }
                break;
            }
            case 11: {
                if (typeof context.focusActivePlaylistOrPlayer === 'function') {
                    context.focusActivePlaylistOrPlayer();
                }
                break;
            }
            case 12: {
                dispatchKeyToTarget('ArrowUp', context);
                break;
            }
            case 13: {
                dispatchKeyToTarget('ArrowDown', context);
                break;
            }
            case 14: {
                dispatchKeyToTarget('ArrowLeft', context);
                break;
            }
            case 15: {
                dispatchKeyToTarget('ArrowRight', context);
                break;
            }
            default:
                break;
        }
    }

    function handleGamepadAxis(axisIndex, direction, context) {
        if (!context || direction === 0) {
            return;
        }

        if (axisIndex === 0) {
            dispatchKeyToTarget(direction < 0 ? 'ArrowLeft' : 'ArrowRight', context);
        } else if (axisIndex === 1) {
            dispatchKeyToTarget(direction < 0 ? 'ArrowUp' : 'ArrowDown', context);
        }
    }

    function ensureGamepadState(gamepad) {
        if (!connectedGamepads.has(gamepad.index)) {
            connectedGamepads.set(gamepad.index, {
                buttons: [],
                axes: [],
            });
        }

        return connectedGamepads.get(gamepad.index);
    }

    function pollGamepads() {
        if (!currentContext) {
            return;
        }

        const gamepads = typeof navigator.getGamepads === 'function' ? navigator.getGamepads() : [];
        const now = typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();

        Array.from(gamepads || []).forEach((gamepad) => {
            if (!gamepad) {
                return;
            }

            const state = ensureGamepadState(gamepad);

            gamepad.buttons.forEach((button, index) => {
                const pressed = Boolean(button?.pressed || (button?.value ?? 0) > 0.5);
                const wasPressed = Boolean(state.buttons[index]);

                if (pressed && !wasPressed) {
                    handleGamepadButtonPress(index, currentContext);
                }

                state.buttons[index] = pressed;
            });

            gamepad.axes.forEach((value, index) => {
                const normalized = Math.abs(value) < GAMEPAD_AXIS_DEADZONE ? 0 : value;
                const direction = normalized === 0 ? 0 : normalized < 0 ? -1 : 1;
                const axisState = state.axes[index] || { direction: 0, lastFired: 0 };

                if (direction === 0) {
                    axisState.direction = 0;
                    axisState.lastFired = 0;
                } else if (axisState.direction !== direction || now - axisState.lastFired >= GAMEPAD_AXIS_REPEAT_MS) {
                    handleGamepadAxis(index, direction, currentContext);
                    axisState.direction = direction;
                    axisState.lastFired = now;
                }

                state.axes[index] = axisState;
            });
        });

        gamepadLoopId = window.requestAnimationFrame(pollGamepads);
    }

    function startGamepadLoop() {
        if (gamepadLoopId != null) {
            return;
        }

        gamepadLoopId = window.requestAnimationFrame(pollGamepads);
    }

    function stopGamepadLoop() {
        if (gamepadLoopId != null) {
            window.cancelAnimationFrame(gamepadLoopId);
            gamepadLoopId = null;
        }
    }

    function handleGamepadConnected(event) {
        if (!event?.gamepad) {
            return;
        }

        ensureGamepadState(event.gamepad);
        if (currentContext) {
            startGamepadLoop();
        }
    }

    function handleGamepadDisconnected(event) {
        if (!event?.gamepad) {
            return;
        }

        connectedGamepads.delete(event.gamepad.index);
        if (connectedGamepads.size === 0) {
            stopGamepadLoop();
        }
    }

    function initControlHandlers(context) {
        if (initialized || !context) {
            return;
        }

        initialized = true;
        currentContext = context;

        const {
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
        } = context;

        if (!player || !Array.isArray(playlistItems) || playlistItems.length === 0) {
            return;
        }

        function handleMediaKeys(event) {
            if (event.defaultPrevented) {
                return;
            }

            const { key } = event;

            if (key === 'MediaPlayPause') {
                event.preventDefault();
                togglePlayback();
                return;
            }

            if (key === 'Play') {
                if (player.paused) {
                    event.preventDefault();
                    player.play().catch(() => {});
                }
                return;
            }

            if (key === 'Pause') {
                if (!player.paused) {
                    event.preventDefault();
                    player.pause();
                }
                return;
            }

            if (key === 'MediaTrackNext') {
                const shouldKeepPlayerFocus = document.activeElement === player;
                const nextItem = activateItemByOffset(1, { focus: !shouldKeepPlayerFocus });
                if (nextItem) {
                    event.preventDefault();
                    if (shouldKeepPlayerFocus) {
                        focusElement(player);
                    }
                }
                return;
            }

            if (key === 'MediaTrackPrevious') {
                const shouldKeepPlayerFocus = document.activeElement === player;
                const previousItem = activateItemByOffset(-1, { focus: !shouldKeepPlayerFocus });
                if (previousItem) {
                    event.preventDefault();
                    if (shouldKeepPlayerFocus) {
                        focusElement(player);
                    }
                }
            }
        }

        document.addEventListener('keydown', handleMediaKeys);

        playlistItems.forEach((item, index) => {
            item.addEventListener('keydown', (event) => {
                switch (event.key) {
                    case 'Enter':
                    case ' ': {
                        event.preventDefault();
                        setActive(item);
                        break;
                    }
                    case 'ArrowDown': {
                        event.preventDefault();
                        const nextItem = playlistItems[Math.min(index + 1, playlistItems.length - 1)];
                        if (nextItem && nextItem !== item) {
                            focusElement(nextItem);
                        }
                        break;
                    }
                    case 'ArrowUp': {
                        event.preventDefault();
                        const previousItem = playlistItems[Math.max(index - 1, 0)];
                        if (previousItem && previousItem !== item) {
                            focusElement(previousItem);
                        } else {
                            focusFirstNavItem();
                        }
                        break;
                    }
                    case 'ArrowLeft': {
                        event.preventDefault();
                        focusElement(player);
                        break;
                    }
                    case 'ArrowRight': {
                        if (qualitySelect && !qualitySelect.disabled) {
                            event.preventDefault();
                            focusElement(qualitySelect);
                        }
                        break;
                    }
                    case 'MediaPlayPause': {
                        event.preventDefault();
                        event.stopPropagation();
                        togglePlayback();
                        break;
                    }
                    case 'MediaTrackNext': {
                        event.preventDefault();
                        event.stopPropagation();
                        activateItemByOffset(1);
                        break;
                    }
                    case 'MediaTrackPrevious': {
                        event.preventDefault();
                        event.stopPropagation();
                        activateItemByOffset(-1);
                        break;
                    }
                    default:
                        break;
                }
            });
        });

        if (!player.hasAttribute('tabindex')) {
            player.setAttribute('tabindex', '0');
        }

        player.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                event.stopPropagation();
                focusFirstNavItem();
            } else if (event.key === 'ArrowDown') {
                const activeItem = getActiveItem() || playlistItems[0] || null;
                if (activeItem) {
                    event.preventDefault();
                    event.stopPropagation();
                    focusElement(activeItem);
                }
            } else if (event.key === 'MediaPlayPause') {
                event.preventDefault();
                event.stopPropagation();
                togglePlayback();
            } else if (event.key === 'MediaTrackNext') {
                const nextItem = activateItemByOffset(1, { focus: false });
                if (nextItem) {
                    event.preventDefault();
                    event.stopPropagation();
                    focusElement(player);
                }
            } else if (event.key === 'MediaTrackPrevious') {
                const previousItem = activateItemByOffset(-1, { focus: false });
                if (previousItem) {
                    event.preventDefault();
                    event.stopPropagation();
                    focusElement(player);
                }
            }
        });

        if (qualitySelect) {
            qualitySelect.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    focusFirstNavItem();
                } else if (event.key === 'ArrowLeft') {
                    const activeItem = getActiveItem();
                    if (activeItem) {
                        event.preventDefault();
                        focusElement(activeItem);
                    }
                } else if (event.key === 'MediaPlayPause') {
                    event.preventDefault();
                    event.stopPropagation();
                    togglePlayback();
                }
            });
        }

        navLinks.forEach((link, index) => {
            link.addEventListener('keydown', (event) => {
                switch (event.key) {
                    case 'ArrowRight': {
                        event.preventDefault();
                        const nextLink = navLinks[Math.min(index + 1, navLinks.length - 1)] || null;
                        if (nextLink && nextLink !== link) {
                            focusElement(nextLink, { preventScroll: true, ensureVisible: false });
                        } else if (navActions[0]) {
                            focusElement(navActions[0], { preventScroll: true, ensureVisible: false });
                        }
                        break;
                    }
                    case 'ArrowLeft': {
                        event.preventDefault();
                        const previousLink = navLinks[Math.max(index - 1, 0)] || null;
                        if (previousLink && previousLink !== link) {
                            focusElement(previousLink, { preventScroll: true, ensureVisible: false });
                        }
                        break;
                    }
                    case 'ArrowDown': {
                        event.preventDefault();
                        focusActivePlaylistOrPlayer();
                        break;
                    }
                    default:
                        break;
                }
            });
        });

        navActions.forEach((action, index) => {
            action.addEventListener('keydown', (event) => {
                switch (event.key) {
                    case 'ArrowRight': {
                        event.preventDefault();
                        const nextAction = navActions[Math.min(index + 1, navActions.length - 1)] || null;
                        if (nextAction && nextAction !== action) {
                            focusElement(nextAction, { preventScroll: true, ensureVisible: false });
                        }
                        break;
                    }
                    case 'ArrowLeft': {
                        event.preventDefault();
                        const previousAction = navActions[Math.max(index - 1, 0)] || null;
                        if (previousAction && previousAction !== action) {
                            focusElement(previousAction, { preventScroll: true, ensureVisible: false });
                        } else if (navLinks.length > 0) {
                            focusElement(navLinks[navLinks.length - 1], { preventScroll: true, ensureVisible: false });
                        }
                        break;
                    }
                    case 'ArrowDown': {
                        event.preventDefault();
                        focusActivePlaylistOrPlayer();
                        break;
                    }
                    case 'ArrowUp': {
                        if (navLinks.length > 0) {
                            event.preventDefault();
                            focusElement(navLinks[0], { preventScroll: true, ensureVisible: false });
                        }
                        break;
                    }
                    default:
                        break;
                }
            });
        });

        window.addEventListener('gamepadconnected', handleGamepadConnected);
        window.addEventListener('gamepaddisconnected', handleGamepadDisconnected);

        if (typeof navigator.getGamepads === 'function') {
            const existing = Array.from(navigator.getGamepads() || []).filter(Boolean);
            if (existing.length > 0) {
                existing.forEach((gamepad) => {
                    ensureGamepadState(gamepad);
                });
                startGamepadLoop();
            }
        }
    }

    function handleContextReady(event) {
        initControlHandlers(event.detail);
    }

    if (window.watchControlContext) {
        initControlHandlers(window.watchControlContext);
    } else if (window.watchRemoteContext) {
        initControlHandlers(window.watchRemoteContext);
    }

    document.addEventListener('watch:control-context-ready', handleContextReady);
    document.addEventListener('watch:remote-context-ready', handleContextReady);
})();

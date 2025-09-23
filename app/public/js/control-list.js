(function () {
    const animeLists = Array.from(document.querySelectorAll('[data-anime-list]'));

    if (animeLists.length === 0) {
        return;
    }

    function shouldIgnoreListNavigationTarget(target) {
        if (!(target instanceof HTMLElement)) {
            return false;
        }

        const tagName = target.tagName;
        return (
            target.isContentEditable ||
            tagName === 'INPUT' ||
            tagName === 'TEXTAREA' ||
            tagName === 'SELECT' ||
            tagName === 'BUTTON'
        );
    }

    function getFocusableAnimeItems(listElement) {
        if (!listElement) {
            return [];
        }

        return Array.from(
            listElement.querySelectorAll('.anime-card__link, [data-anime-card] a[href]')
        ).filter((element, index, array) => array.indexOf(element) === index);
    }

    function focusAnimeItem(element) {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        if (typeof element.focus === 'function') {
            try {
                element.focus({ preventScroll: true });
            } catch (error) {
                element.focus();
            }
        }
    }

    function findListWithItems() {
        for (const list of animeLists) {
            if (getFocusableAnimeItems(list).length > 0) {
                return list;
            }
        }

        return null;
    }

    function pickDirectionalCandidate(items, currentItem, direction) {
        if (!currentItem) {
            return null;
        }

        const currentRect = currentItem.getBoundingClientRect();
        const currentCenterX = currentRect.left + currentRect.width / 2;
        const currentCenterY = currentRect.top + currentRect.height / 2;
        let bestCandidate = null;
        let bestScore = Infinity;

        const verticalTolerance = currentRect.height / 2 + 24;

        items.forEach((item) => {
            if (item === currentItem) {
                return;
            }

            const rect = item.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const deltaX = centerX - currentCenterX;
            const deltaY = centerY - currentCenterY;

            let isCandidate = false;
            let score = Infinity;

            switch (direction) {
                case 'ArrowLeft':
                    if (deltaX < -1 && Math.abs(deltaY) <= verticalTolerance) {
                        isCandidate = true;
                        score = Math.abs(deltaX) * 1000 + Math.abs(deltaY);
                    }
                    break;
                case 'ArrowRight':
                    if (deltaX > 1 && Math.abs(deltaY) <= verticalTolerance) {
                        isCandidate = true;
                        score = Math.abs(deltaX) * 1000 + Math.abs(deltaY);
                    }
                    break;
                case 'ArrowUp':
                    if (deltaY < -1) {
                        isCandidate = true;
                        score = Math.abs(deltaY) * 1000 + Math.abs(deltaX);
                    }
                    break;
                case 'ArrowDown':
                    if (deltaY > 1) {
                        isCandidate = true;
                        score = Math.abs(deltaY) * 1000 + Math.abs(deltaX);
                    }
                    break;
                default:
                    break;
            }

            if (!isCandidate) {
                return;
            }

            if (score < bestScore) {
                bestScore = score;
                bestCandidate = item;
            } else if (score === bestScore && bestCandidate) {
                const bestRect = bestCandidate.getBoundingClientRect();
                if (
                    (direction === 'ArrowLeft' && rect.left > bestRect.left) ||
                    (direction === 'ArrowRight' && rect.left < bestRect.left) ||
                    (direction === 'ArrowUp' && rect.top > bestRect.top) ||
                    (direction === 'ArrowDown' && rect.top < bestRect.top)
                ) {
                    bestCandidate = item;
                }
            }
        });

        if (bestCandidate) {
            return bestCandidate;
        }

        const currentIndex = items.indexOf(currentItem);
        if (currentIndex === -1) {
            return null;
        }

        if (direction === 'ArrowLeft' || direction === 'ArrowUp') {
            return items[currentIndex - 1] || null;
        }

        if (direction === 'ArrowRight' || direction === 'ArrowDown') {
            return items[currentIndex + 1] || null;
        }

        return null;
    }

    function handleAnimeListKeydown(event) {
        const { key } = event;
        if (key !== 'ArrowUp' && key !== 'ArrowDown' && key !== 'ArrowLeft' && key !== 'ArrowRight') {
            return;
        }

        if (event.altKey || event.ctrlKey || event.metaKey) {
            return;
        }

        if (shouldIgnoreListNavigationTarget(event.target)) {
            return;
        }

        const activeElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        let currentList = null;

        if (activeElement) {
            currentList = animeLists.find((list) => list.contains(activeElement));
        }

        if (!currentList) {
            const listWithItems = findListWithItems();
            if (!listWithItems) {
                return;
            }

            const focusableItems = getFocusableAnimeItems(listWithItems);
            if (focusableItems.length === 0) {
                return;
            }

            event.preventDefault();
            focusAnimeItem(focusableItems[0]);
            return;
        }

        const focusableItems = getFocusableAnimeItems(currentList);
        if (focusableItems.length === 0) {
            return;
        }

        let currentItem = focusableItems.find((item) => item === activeElement || item.contains(activeElement));
        if (!currentItem) {
            currentItem = activeElement ? activeElement.closest('.anime-card__link, [data-anime-card] a[href]') : null;
        }

        if (!(currentItem instanceof HTMLElement)) {
            const listWithItems = findListWithItems();
            if (!listWithItems) {
                return;
            }

            const firstItem = getFocusableAnimeItems(listWithItems)[0];
            if (!firstItem) {
                return;
            }

            event.preventDefault();
            focusAnimeItem(firstItem);
            return;
        }

        const nextItem = pickDirectionalCandidate(focusableItems, currentItem, key);

        if (!nextItem || nextItem === currentItem) {
            return;
        }

        event.preventDefault();
        focusAnimeItem(nextItem);
    }

    document.addEventListener('keydown', handleAnimeListKeydown);
})();

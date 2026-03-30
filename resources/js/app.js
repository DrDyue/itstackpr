import './bootstrap';

const Alpine = window.Alpine;
const THEME_STORAGE_KEY = 'itstack-theme';

const getStoredTheme = () => {
    try {
        return window.localStorage.getItem(THEME_STORAGE_KEY) === 'dark' ? 'dark' : 'light';
    } catch (error) {
        return 'light';
    }
};

const applyTheme = (theme) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = normalizedTheme;
    document.documentElement.style.colorScheme = normalizedTheme;

    if (document.body) {
        document.body.dataset.theme = normalizedTheme;
    }

    window.dispatchEvent(new CustomEvent('app-theme-changed', {
        detail: { theme: normalizedTheme },
    }));
};

const syncThemeToggleUi = () => {
    const currentTheme = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';

    document.querySelectorAll('[data-theme-choice]').forEach((button) => {
        const buttonTheme = button.dataset.themeValue === 'dark' ? 'dark' : 'light';
        const isActive = buttonTheme === currentTheme;

        button.dataset.active = isActive ? 'true' : 'false';
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
};

const initializeThemeToggle = () => {
    applyTheme(getStoredTheme());
    syncThemeToggleUi();

    document.querySelectorAll('[data-theme-choice]').forEach((button) => {
        if (button.dataset.themeBound === '1') {
            return;
        }

        button.dataset.themeBound = '1';
        button.addEventListener('click', () => {
            const nextTheme = button.dataset.themeValue === 'dark' ? 'dark' : 'light';

            try {
                window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
            } catch (error) {
                // Ja localStorage nav pieejams, tema darbosies vismaz konkretas sesijas laika.
            }

            applyTheme(nextTheme);
            syncThemeToggleUi();
        });
    });
};

const registerAlpineData = () => {
    if (!Alpine || window.__appAlpineDataRegistered) {
        return;
    }

    window.__appAlpineDataRegistered = true;

    Alpine.data('localizedDatePicker', ({ value = '' } = {}) => ({
        open: false,
        value: value || '',
        viewDate: null,
        weekdays: ['Pr', 'Ot', 'Tr', 'Ce', 'Pk', 'Se', 'Sv'],
        months: ['Janvaris', 'Februaris', 'Marts', 'Aprilis', 'Maijs', 'Junijs', 'Julijs', 'Augusts', 'Septembris', 'Oktobris', 'Novembris', 'Decembris'],
        init() {
            this.viewDate = this.value ? this.parseDate(this.value) : new Date();
        },
        toggle() {
            this.open = !this.open;
        },
        previousMonth() {
            this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() - 1, 1);
        },
        nextMonth() {
            this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);
        },
        select(selectedValue) {
            this.value = selectedValue;
            this.viewDate = this.parseDate(selectedValue);
            this.open = false;
        },
        clear() {
            this.value = '';
            this.open = false;
        },
        parseDate(dateValue) {
            const [year, month, day] = dateValue.split('-').map(Number);
            return new Date(year, month - 1, day);
        },
        formatDate(dateValue) {
            if (!dateValue) {
                return '';
            }

            const [year, month, day] = dateValue.split('-');
            return `${day}.${month}.${year}`;
        },
        toIso(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        },
        get displayValue() {
            return this.formatDate(this.value);
        },
        get monthLabel() {
            return `${this.months[this.viewDate.getMonth()]} ${this.viewDate.getFullYear()}`;
        },
        get days() {
            const startOfMonth = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), 1);
            const endOfMonth = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 0);
            const startWeekday = (startOfMonth.getDay() + 6) % 7;
            const days = [];

            for (let i = startWeekday; i > 0; i -= 1) {
                const date = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), 1 - i);
                days.push({
                    key: `prev-${this.toIso(date)}`,
                    label: date.getDate(),
                    value: this.toIso(date),
                    isCurrentMonth: false,
                    isSelected: false,
                });
            }

            for (let day = 1; day <= endOfMonth.getDate(); day += 1) {
                const date = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth(), day);
                const iso = this.toIso(date);

                days.push({
                    key: iso,
                    label: day,
                    value: iso,
                    isCurrentMonth: true,
                    isSelected: this.value === iso,
                });
            }

            while (days.length < 42) {
                const offset = days.length - (startWeekday + endOfMonth.getDate()) + 1;
                const date = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, offset);
                days.push({
                    key: `next-${this.toIso(date)}`,
                    label: date.getDate(),
                    value: this.toIso(date),
                    isCurrentMonth: false,
                    isSelected: false,
                });
            }

            return days;
        },
    }));

    Alpine.data('filterChipGroup', ({ selected = [], minimum = 1 } = {}) => ({
        selected: Array.from(new Set((selected ?? []).map((value) => String(value)))),
        minimum: Math.max(Number(minimum) || 1, 1),
        isSelected(value) {
            return this.selected.includes(String(value));
        },
        toggle(value) {
            const normalizedValue = String(value);

            if (this.isSelected(normalizedValue)) {
                if (this.selected.length <= this.minimum) {
                    return;
                }

                this.selected = this.selected.filter((item) => item !== normalizedValue);

                return;
            }

            this.selected = [...this.selected, normalizedValue];
        },
    }));

    Alpine.data('liveRequestNotifications', ({ endpoint = '', storageKey = 'live-request-notifications', pollSeconds = 12, pageKind = '' } = {}) => ({
        endpoint,
        storageKey,
        pollSeconds: Math.max(Number(pollSeconds) || 12, 5),
        pageKind,
        items: [],
        seenIds: [],
        bootstrapped: false,
        timerId: null,
        refreshTimerId: null,
        onVisibilityChange: null,
        init() {
            this.seenIds = this.readSeenIds();
            this.fetchNotifications(true);
            this.startPolling();
            this.onVisibilityChange = () => {
                if (document.visibilityState === 'visible') {
                    this.fetchNotifications(false);
                }
            };
            document.addEventListener('visibilitychange', this.onVisibilityChange);
        },
        destroy() {
            this.stopPolling();

            if (this.refreshTimerId) {
                window.clearTimeout(this.refreshTimerId);
            }

            if (this.onVisibilityChange) {
                document.removeEventListener('visibilitychange', this.onVisibilityChange);
            }
        },
        startPolling() {
            this.stopPolling();
            this.timerId = window.setInterval(() => {
                if (document.visibilityState !== 'hidden') {
                    this.fetchNotifications(false);
                }
            }, this.pollSeconds * 1000);
        },
        stopPolling() {
            if (!this.timerId) {
                return;
            }

            window.clearInterval(this.timerId);
            this.timerId = null;
        },
        async fetchNotifications(isInitial = false) {
            if (!this.endpoint || !window.axios) {
                return;
            }

            try {
                const response = await window.axios.get(this.endpoint, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const notifications = Array.isArray(response?.data?.notifications)
                    ? response.data.notifications
                    : [];

                if (!this.bootstrapped || isInitial) {
                    notifications.forEach((notification) => this.remember(notification.id));
                    this.bootstrapped = true;

                    return;
                }

                notifications.forEach((notification) => {
                    if (!notification?.id || this.hasSeen(notification.id) || this.items.some((item) => item.id === notification.id)) {
                        return;
                    }

                    const toast = {
                        ...notification,
                        visible: false,
                        busy: false,
                    };

                    this.items = [toast, ...this.items].slice(0, 4);
                    this.remember(notification.id);
                    window.requestAnimationFrame(() => {
                        const createdToast = this.items.find((item) => item.id === notification.id);
                        if (createdToast) {
                            createdToast.visible = true;
                        }
                    });
                    window.setTimeout(() => this.dismiss(notification.id), 9000);

                    if (this.shouldRefreshForNotification(notification)) {
                        this.scheduleRefresh();
                    }
                });
            } catch (error) {
                // Ignore transient polling errors and retry on the next cycle.
            }
        },
        hasSeen(id) {
            return this.seenIds.includes(String(id));
        },
        remember(id) {
            const normalizedId = String(id ?? '');
            if (!normalizedId || this.hasSeen(normalizedId)) {
                return;
            }

            this.seenIds = [...this.seenIds, normalizedId].slice(-120);
            this.writeSeenIds();
        },
        dismiss(id) {
            const notification = this.items.find((item) => item.id === id);

            if (!notification) {
                return;
            }

            notification.visible = false;

            window.setTimeout(() => {
                this.items = this.items.filter((item) => item.id !== id);
            }, 260);
        },
        open(notification) {
            if (!notification?.url) {
                return;
            }

            window.location.assign(notification.url);
        },
        async runAction(notification, action) {
            if (!window.axios || !action?.url || notification?.busy) {
                return;
            }

            notification.busy = true;

            try {
                await window.axios.post(action.url, action.payload ?? {}, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                this.dismiss(notification.id);
                if (this.shouldRefreshForNotification(notification)) {
                    this.scheduleRefresh(250);
                }
                await this.fetchNotifications(false);
            } catch (error) {
                notification.busy = false;
            }
        },
        shouldRefreshForNotification(notification) {
            if (!this.pageKind || !notification?.type) {
                return false;
            }

            if (this.pageKind === 'repair-requests') {
                return notification.type === 'repair';
            }

            if (this.pageKind === 'writeoff-requests') {
                return notification.type === 'writeoff';
            }

            if (this.pageKind === 'device-transfers') {
                return notification.type === 'transfer' || notification.type === 'incoming-transfer';
            }

            return false;
        },
        scheduleRefresh(delayMs = 1200) {
            if (this.refreshTimerId) {
                return;
            }

            this.refreshTimerId = window.setTimeout(() => {
                window.location.reload();
            }, delayMs);
        },
        accentClasses(accent) {
            return {
                amber: 'border-amber-200 bg-amber-50/95 text-amber-950',
                emerald: 'border-emerald-200 bg-emerald-50/95 text-emerald-950',
                rose: 'border-rose-200 bg-rose-50/95 text-rose-950',
                sky: 'border-sky-200 bg-sky-50/95 text-sky-950',
            }[accent] ?? 'border-slate-200 bg-white text-slate-900';
        },
        badgeClasses(accent) {
            return {
                amber: 'bg-amber-500 text-white',
                emerald: 'bg-emerald-600 text-white',
                rose: 'bg-rose-600 text-white',
                sky: 'bg-sky-600 text-white',
            }[accent] ?? 'bg-slate-700 text-white';
        },
        badgeText(type) {
            return {
                repair: 'Remonts',
                writeoff: 'Norakstīšana',
                transfer: 'Nodošana',
                'incoming-transfer': 'Jāizskata',
            }[type] ?? 'Pieprasījums';
        },
        actionClasses(tone) {
            return {
                approve: 'border-emerald-200 bg-emerald-600 text-white hover:bg-emerald-700',
                reject: 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100',
            }[tone] ?? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50';
        },
        readSeenIds() {
            try {
                const raw = window.localStorage.getItem(this.storageKey);
                const parsed = raw ? JSON.parse(raw) : [];

                return Array.isArray(parsed)
                    ? parsed.map((value) => String(value)).filter(Boolean)
                    : [];
            } catch (error) {
                return [];
            }
        },
        writeSeenIds() {
            try {
                window.localStorage.setItem(this.storageKey, JSON.stringify(this.seenIds));
            } catch (error) {
                // Ignore storage write issues; notifications will still work during the session.
            }
        },
    }));

    Alpine.data('searchableSelect', ({ selected = '', query = '', options = [], placeholder = '', emptyMessage = '', identifier = '' } = {}) => ({
        open: false,
        selected: String(selected ?? ''),
        query: query || '',
        identifier,
        showAllOptions: false,
        options: options.map((option) => ({
            value: String(option.value ?? ''),
            label: option.label ?? '',
            description: option.description ?? '',
            search: (option.search ?? `${option.label ?? ''} ${option.description ?? ''}`).toLowerCase(),
        })),
        placeholder,
        emptyMessage,
        highlightedIndex: 0,
        pointerActive: false,
        pointerMode: null,
        surfacePointerId: null,
        wasOpenBeforePointer: false,
        dragging: false,
        suppressClick: false,
        scrubDirection: null,
        scrubVisualOffset: 0,
        scrubAnimationFrame: null,
        startY: 0,
        startScrollTop: 0,
        dragStartIndex: 0,
        scrubStepPx: 50,
        init() {
            if (this.selected && !this.query) {
                const current = this.options.find((option) => option.value === this.selected);
                if (current) {
                    this.query = current.label;
                }
            }
        },
        get filteredOptions() {
            if (this.open && this.showAllOptions) {
                return this.options;
            }

            const term = this.query.trim().toLowerCase();

            if (term === '') {
                return this.options;
            }

            return this.options.filter((option) => option.search.includes(term));
        },
        get scrubPreviousOption() {
            return this.options[this.highlightedIndex - 1] ?? null;
        },
        get scrubCurrentOption() {
            return this.options[this.highlightedIndex] ?? null;
        },
        get scrubNextOption() {
            return this.options[this.highlightedIndex + 1] ?? null;
        },
        togglePanel() {
            this.open = !this.open;
            if (this.open) {
                this.showAllOptions = true;
                this.preparePanel();
            } else {
                this.resetPointerState();
            }
        },
        openPanel() {
            this.open = true;
            this.showAllOptions = true;
            this.preparePanel();
            this.$nextTick(() => {
                this.$refs.input?.select();
            });
        },
        handleTriggerClick() {
            if (this.suppressClick) {
                this.suppressClick = false;
                return;
            }

            this.openPanel();
        },
        closePanelOnly() {
            this.open = false;
            this.showAllOptions = false;
        },
        resetPointerState() {
            this.pointerActive = false;
            this.pointerMode = null;
            this.surfacePointerId = null;
            this.dragging = false;
            this.suppressClick = false;
            this.wasOpenBeforePointer = false;
            this.scrubDirection = null;
            this.scrubVisualOffset = 0;

            if (this.scrubAnimationFrame) {
                window.cancelAnimationFrame(this.scrubAnimationFrame);
                this.scrubAnimationFrame = null;
            }
        },
        close() {
            this.closePanelOnly();
            this.resetPointerState();
        },
        clearSelection() {
            this.selected = '';
            this.query = '';
            this.highlightedIndex = 0;
            this.dispatchUpdate();
            this.closePanelOnly();
        },
        preparePanel() {
            const selectedIndex = this.filteredOptions.findIndex((option) => option.value === this.selected);
            this.highlightedIndex = selectedIndex >= 0 ? selectedIndex : 0;
            this.$nextTick(() => this.scrollToHighlighted());
        },
        handleInput() {
            this.open = true;
            this.showAllOptions = false;
            const normalized = this.query.trim().toLowerCase();
            const exact = this.options.find((option) => option.label.toLowerCase() === normalized);
            this.selected = exact ? exact.value : '';
            this.highlightedIndex = 0;
        },
        beginScrub(event) {
            const isPrimaryButton = event.button === 0 || event.button === -1 || event.buttons === 1;
            if (!isPrimaryButton) {
                return;
            }

            this.wasOpenBeforePointer = this.open;
            this.pointerActive = true;
            this.pointerMode = 'scrub';
            this.surfacePointerId = event.pointerId ?? null;
            this.dragging = false;
            this.suppressClick = false;
            this.startY = event.clientY;
            const selectedIndex = this.options.findIndex((option) => option.value === this.selected);
            this.dragStartIndex = selectedIndex >= 0 ? selectedIndex : 0;
            this.highlightedIndex = this.dragStartIndex;

            if (typeof event.currentTarget?.setPointerCapture === 'function' && event.pointerId !== undefined) {
                event.currentTarget.setPointerCapture(event.pointerId);
            }
        },
        handleSurfacePointerMove(event) {
            if (this.pointerMode !== 'scrub') {
                return;
            }

            if (this.surfacePointerId !== null && event.pointerId !== this.surfacePointerId) {
                return;
            }

            this.handleScrubMove(event);
        },
        finishSurfacePointer(event) {
            if (this.pointerMode !== 'scrub') {
                return;
            }

            if (this.surfacePointerId !== null && event.pointerId !== this.surfacePointerId) {
                return;
            }

            if (typeof event.currentTarget?.releasePointerCapture === 'function' && event.pointerId !== undefined) {
                try {
                    event.currentTarget.releasePointerCapture(event.pointerId);
                } catch (error) {
                    // Ignore capture release errors; they are harmless if capture was already lost.
                }
            }

            this.stopPointer();
        },
        cancelSurfacePointer(event) {
            if (this.pointerMode !== 'scrub') {
                return;
            }

            if (this.surfacePointerId !== null && event.pointerId !== this.surfacePointerId) {
                return;
            }

            this.resetPointerState();
        },
        move(direction) {
            if (!this.open) {
                this.openPanel();
            }

            if (this.filteredOptions.length === 0) {
                return;
            }

            const maxIndex = this.filteredOptions.length - 1;
            this.highlightedIndex = Math.min(maxIndex, Math.max(0, this.highlightedIndex + direction));
            this.$nextTick(() => this.scrollToHighlighted());
        },
        commit() {
            const option = this.filteredOptions[this.highlightedIndex] ?? this.filteredOptions[0];
            if (!option) {
                return;
            }

            this.choose(option);
        },
        choose(option, force = false) {
            if (this.suppressClick && !force) {
                return;
            }

            this.selected = option.value;
            this.query = option.label;
            this.dispatchUpdate();
            this.closePanelOnly();
        },
        dispatchUpdate() {
            if (!this.identifier) {
                return;
            }

            this.$dispatch('searchable-select-updated', {
                identifier: this.identifier,
                value: this.selected,
                query: this.query,
            });
        },
        optionClasses(index, option) {
            const isActive = this.highlightedIndex === index;
            const isSelected = this.selected === option.value;

            if (isActive || isSelected) {
                return 'bg-slate-900 text-white';
            }

            return 'text-slate-700 hover:bg-slate-50';
        },
        scrollToHighlighted() {
            const panel = this.$refs.panel;
            if (!panel) {
                return;
            }

            const option = panel.querySelectorAll('.searchable-select-option')[this.highlightedIndex];
            if (!option) {
                return;
            }

            const optionTop = option.offsetTop;
            const optionBottom = optionTop + option.offsetHeight;
            const viewTop = panel.scrollTop;
            const viewBottom = viewTop + panel.clientHeight;

            if (optionTop < viewTop) {
                panel.scrollTop = optionTop;
            } else if (optionBottom > viewBottom) {
                panel.scrollTop = optionBottom - panel.clientHeight;
            }
        },
        startPointer(event) {
            if (!this.open || !this.$refs.panel) {
                return;
            }

            this.pointerActive = true;
            this.pointerMode = 'panel';
            this.dragging = false;
            this.startY = event.clientY;
            this.startScrollTop = this.$refs.panel.scrollTop;
        },
        handlePointerMove(event) {
            if (!this.pointerActive) {
                return;
            }

            if (!this.$refs.panel) {
                return;
            }

            if (this.pointerMode === 'panel') {
                const delta = event.clientY - this.startY;

                if (Math.abs(delta) > 3) {
                    this.dragging = true;
                    this.suppressClick = true;
                }

                if (this.dragging) {
                    this.$refs.panel.scrollTop = this.startScrollTop - delta;
                    this.syncHighlightFromPointer(event);
                }
            }
        },
        stopPointer() {
            if (!this.pointerActive) {
                return;
            }

            if (this.pointerMode === 'scrub') {
                this.finishScrub();
            } else if (this.pointerMode === 'panel') {
                if (this.suppressClick && this.filteredOptions[this.highlightedIndex]) {
                    this.choose(this.filteredOptions[this.highlightedIndex], true);
                }

                if (this.suppressClick) {
                    window.setTimeout(() => {
                        this.suppressClick = false;
                    }, 80);
                }
            }

            this.pointerActive = false;
            this.pointerMode = null;
            this.dragging = false;
        },
        syncHighlightFromPointer(event) {
            const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('.searchable-select-option');
            if (!target) {
                return;
            }

            const nextIndex = Number(target.dataset.index ?? '-1');
            if (!Number.isNaN(nextIndex) && nextIndex >= 0) {
                this.highlightedIndex = nextIndex;
            }
        },
        handleScrubMove(event) {
            if (this.options.length === 0) {
                return;
            }

            const delta = event.clientY - this.startY;

            if (Math.abs(delta) > 3) {
                this.dragging = true;
                this.suppressClick = true;
            }

            const offset = Math.trunc(delta / this.scrubStepPx);
            const maxIndex = Math.max(0, this.options.length - 1);
            const nextIndex = Math.min(maxIndex, Math.max(0, this.dragStartIndex + offset));

            if (nextIndex !== this.highlightedIndex) {
                const direction = nextIndex > this.highlightedIndex ? 'down' : 'up';
                this.highlightedIndex = nextIndex;
                this.query = this.options[nextIndex]?.label ?? this.query;
                this.animateScrubPreview(direction);
            }
        },
        animateScrubPreview(direction) {
            this.scrubDirection = direction;
            this.scrubVisualOffset = direction === 'down' ? -14 : 14;

            if (this.scrubAnimationFrame) {
                window.cancelAnimationFrame(this.scrubAnimationFrame);
            }

            this.scrubAnimationFrame = window.requestAnimationFrame(() => {
                this.scrubVisualOffset = 0;
                this.scrubAnimationFrame = null;
            });
        },
        finishScrub() {
            if (this.dragging && this.options[this.highlightedIndex]) {
                this.choose(this.options[this.highlightedIndex], true);
                window.setTimeout(() => {
                    this.suppressClick = false;
                }, 80);
                return;
            }

            this.openPanel();
        },
    }));
};

const repairTransitionRules = {
    waiting: ['in-progress', 'cancelled'],
    'in-progress': ['waiting', 'completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

const appendHiddenInput = (form, name, value) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value ?? '';
    form.appendChild(input);
};

window.submitRepairTransition = (transitionBaseUrl, csrfToken, repairId, targetStatus, extra = {}) => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${transitionBaseUrl}/${repairId}/transition`;
    form.style.display = 'none';

    appendHiddenInput(form, '_token', csrfToken);
    appendHiddenInput(form, 'target_status', targetStatus);

    Object.entries(extra).forEach(([key, value]) => {
        appendHiddenInput(form, key, value);
    });

    document.body.appendChild(form);
    form.submit();
};

const canRepairTransition = (fromStatus, toStatus) => {
    return (repairTransitionRules[fromStatus] ?? []).includes(toStatus);
};

window.repairBoard = (config) => ({
    draggedRepair: null,
    dropTargetStatus: null,
    startDrag(repair, event) {
        if (!repair?.id) {
            return;
        }

        this.draggedRepair = repair;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(repair.id));
    },
    clearDrag() {
        this.draggedRepair = null;
        this.dropTargetStatus = null;
    },
    canDrop(targetStatus) {
        return Boolean(
            this.draggedRepair
            && this.draggedRepair.status !== targetStatus
            && canRepairTransition(this.draggedRepair.status, targetStatus)
        );
    },
    onDragOver(targetStatus) {
        if (!this.canDrop(targetStatus)) {
            return;
        }

        this.dropTargetStatus = targetStatus;
    },
    clearDropTarget(targetStatus = null) {
        if (!targetStatus || this.dropTargetStatus === targetStatus) {
            this.dropTargetStatus = null;
        }
    },
    handleDrop(targetStatus) {
        if (!this.canDrop(targetStatus)) {
            return;
        }

        if (targetStatus === 'completed') {
            this.submitCompletion(this.draggedRepair);
            this.clearDrag();
            return;
        }

        this.submitTransition(this.draggedRepair.id, targetStatus);
        this.clearDrag();
    },
    submitTransition(repairId, targetStatus, extra = {}) {
        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, repairId, targetStatus, extra);
    },
    submitCompletion(repair) {
        if (!repair?.id) {
            return;
        }

        const repairName = repair.name ?? 'šo remontu';
        if (!window.confirm(`Vai tiešām gribat pabeigt ierīces remontu "${repairName}"?`)) {
            return;
        }

        this.submitTransition(repair.id, 'completed');
    },
});

window.repairProcess = (config) => ({
    repairType: config.repairType,
    status: config.status,
    submitTransition(repairId, targetStatus, extra = {}) {
        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, repairId, targetStatus, extra);
    },
    submitCompletion() {
        if (!window.confirm('Vai tiešām gribat pabeigt šo ierīces remontu?')) {
            return;
        }

        this.submitTransition(config.repairId, 'completed');
    },
});

registerAlpineData();
document.addEventListener('alpine:init', registerAlpineData);
document.addEventListener('DOMContentLoaded', initializeThemeToggle);

if (document.readyState !== 'loading') {
    initializeThemeToggle();
}

if (Alpine && !window.__appAlpineStarted) {
    window.__appAlpineStarted = true;
    Alpine.start();
}

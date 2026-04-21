import './bootstrap';
import { initializeThemeToggle } from './modules/theme';
import { initializeAppConfirm, registerFeedbackGlobals } from './modules/feedback';
import { initializeAsyncTableFilters, registerAsyncTableGlobals, restoreHighlightedSearchFromUrl } from './modules/async-table';
import { registerRepairWorkflowGlobals } from './modules/repair-workflow';

const Alpine = window.Alpine;

/* ==========================================================================
   Shared helpers (kept in this file by request to minimize file count)
   ========================================================================== */
const runOnDomReady = (callback) => {
    if (typeof callback !== 'function') {
        return;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
};

const readStorageValue = (key, fallback = null) => {
    try {
        return window.localStorage.getItem(key) ?? fallback;
    } catch (error) {
        return fallback;
    }
};

const writeStorageValue = (key, value) => {
    try {
        window.localStorage.setItem(key, value);
        return true;
    } catch (error) {
        return false;
    }
};

const resolveFocusableErrorField = (fieldName) => {
    if (!fieldName) {
        return null;
    }

    const queryFieldFallbacks = {
        device_id: 'device_query',
        device_type_id: 'device_type_query',
        assigned_to_id: 'assigned_to_query',
        room_id: 'room_query',
        building_id: 'building_query',
        status: 'status_query',
        requester_id: 'requester_query',
        transfered_to_id: 'transfered_to_query',
        target_room_id: 'target_room_query',
        target_assigned_to_id: 'target_assigned_to_query',
        user_id: 'user_query',
        floor: 'floor_query',
        type: 'type_query',
    };

    const directField = document.querySelector(`[name="${CSS.escape(fieldName)}"]:not([type="hidden"])`);
    if (directField) {
        return directField;
    }

    const queryField = queryFieldFallbacks[fieldName]
        ? document.querySelector(`[name="${CSS.escape(queryFieldFallbacks[fieldName])}"]`)
        : null;

    return queryField;
};

window.focusValidationField = (fieldName) => {
    const field = resolveFocusableErrorField(fieldName);
    if (!field) {
        return false;
    }

    const scrollContainer = field.closest('.overflow-y-auto');
    if (scrollContainer) {
        const containerRect = scrollContainer.getBoundingClientRect();
        const fieldRect = field.getBoundingClientRect();
        const nextTop = scrollContainer.scrollTop + (fieldRect.top - containerRect.top) - (containerRect.height / 2) + (fieldRect.height / 2);
        scrollContainer.scrollTo({ top: Math.max(0, nextTop), behavior: 'smooth' });
    } else {
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    window.setTimeout(() => {
        field.focus({ preventScroll: true });
        if (typeof field.select === 'function' && field instanceof HTMLInputElement && field.type !== 'file') {
            field.select();
        }
    }, 40);

    return true;
};

const focusFirstValidationError = () => {
    const summaries = Array.from(document.querySelectorAll('.validation-summary[data-first-error-field]'));
    const visibleSummary = summaries.find((summary) => summary.offsetParent !== null && summary.getClientRects().length > 0);
    const targetSummary = visibleSummary ?? summaries[0];

    if (!targetSummary) {
        return;
    }

    const fieldName = targetSummary.getAttribute('data-first-error-field');
    if (!fieldName) {
        return;
    }

    window.setTimeout(() => {
        window.focusValidationField(fieldName);
    }, 140);
};

registerFeedbackGlobals();
registerAsyncTableGlobals();
registerRepairWorkflowGlobals();
runOnDomReady(focusFirstValidationError);

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
        minimum: Math.max(Number.isFinite(Number(minimum)) ? Number(minimum) : 1, 0),
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

    Alpine.data('requestDetailsDrawer', () => ({
        open: false,
        item: null,
        iconSvg(name = 'view') {
            const icons = {
                view: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" /></svg>',
                repair: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m11.42 3.73 1.18 1.77a2.25 2.25 0 0 1-.28 2.84l-6.7 6.7a2.25 2.25 0 1 0 3.18 3.18l6.7-6.7a2.25 2.25 0 0 1 2.84-.28l1.77 1.18a6 6 0 1 0-8.69-8.69Z" /></svg>',
                audit: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-9-5.25h12A2.25 2.25 0 0 1 20.25 6.75v5.568a5.25 5.25 0 0 1-2.06 4.164l-4.44 3.33a2.25 2.25 0 0 1-2.7 0l-4.44-3.33a5.25 5.25 0 0 1-2.06-4.164V6.75A2.25 2.25 0 0 1 6 4.5Z" /></svg>',
                'information-circle': '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9h.008v.008H12V9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 12h.75v4.5h.75" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
                user: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.93 17.93 0 0 1 12 21.75a17.93 17.93 0 0 1-7.5-1.632Z" /></svg>',
                stats: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v18m0 0h16.5m-16.5 0 4.5-6 4.5 3.75 6-9" /></svg>',
                'repair-request': '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h6.75l4.5 4.5V18A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18V6A2.25 2.25 0 0 1 7.5 3.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75V8.25H18.75M8.25 12h7.5M8.25 15.75h5.25" /></svg>',
                'check-circle': '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
                clock: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2.25m5.25-2.25a9.75 9.75 0 1 1-19.5 0 9.75 9.75 0 0 1 19.5 0Z" /></svg>',
                calendar: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3.75v3m7.5-3v3M3.75 8.25h16.5M5.25 5.25h13.5A1.5 1.5 0 0 1 20.25 6.75v11.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z" /></svg>',
                tag: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m16.5 6.75 3.75 3.75-9.75 9.75H6.75v-3.75L16.5 6.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 8.25h.008v.008H15V8.25Z" /></svg>',
                flag: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 3v18m0-12.75c1.5 0 2.25-.75 3.75-.75 1.5 0 2.25.75 3.75.75s2.25-.75 3.75-.75 2.25.75 3.75.75V15c-1.5 0-2.25-.75-3.75-.75s-2.25.75-3.75.75-2.25-.75-3.75-.75-2.25.75-3.75.75" /></svg>',
                send: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.27 3.873a.75.75 0 0 1 1.05-.91L21 12 4.32 21.037a.75.75 0 0 1-1.05-.91L6 12Zm0 0h7.5" /></svg>',
                clear: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>',
                device: '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 17.25V6.75A2.25 2.25 0 0 1 6.75 4.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 16.5h6" /></svg>',
                'exclamation-triangle': '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm8.625 2.228L13.81 4.26a2.062 2.062 0 0 0-3.62 0L3.375 18.732A2.062 2.062 0 0 0 5.186 21.75h13.628a2.062 2.062 0 0 0 1.811-3.018Z" /></svg>',
            };

            return icons[name] ?? icons.view;
        },
        show(item) {
            this.item = item || null;
            this.open = true;
            document.body.classList.add('overflow-hidden');
        },
        close() {
            this.open = false;
            this.item = null;
            document.body.classList.remove('overflow-hidden');
        },
        panelVariantClass() {
            if (this.item?.drawer_variant === 'audit') {
                return 'request-detail-panel-audit';
            }

            if (this.item?.drawer_variant === 'repair') {
                return 'request-detail-panel-repair';
            }

            return '';
        },
        summaryItems() {
            if (Array.isArray(this.item?.summary_items) && this.item.summary_items.length > 0) {
                return this.item.summary_items.filter((entry) => entry?.value);
            }

            const items = [];

            if (this.item?.status_label) {
                items.push({
                    label: this.item.drawer_variant === 'audit' ? 'SvarĆ„Ā«gums' : 'Statuss',
                    value: this.item.status_label,
                    icon: this.item.hero_icon || 'information-circle',
                    tone: this.item.hero_tone || 'slate',
                    badgeClass: this.item.status_badge_class || '',
                });
            }

            if (this.item?.submitted_at) {
                items.push({
                    label: this.item.drawer_variant === 'audit' ? 'FiksĆ„ā€ts' : 'Datums',
                    value: this.item.submitted_at,
                    icon: 'calendar',
                    tone: 'slate',
                });
            }

            if (this.item?.hero_meta) {
                items.push({
                    label: this.item.drawer_variant === 'audit' ? 'Objekts' : 'Kopsavilkums',
                    value: this.item.hero_meta,
                    icon: this.item.drawer_variant === 'audit' ? 'audit' : 'tag',
                    tone: this.item.drawer_variant === 'audit' ? 'violet' : 'sky',
                });
            }

            return items.slice(0, 3);
        },
        infoCards() {
            return [
                {
                    label: this.item?.primary_label || 'GalvenĆ„Ā informĆ„Ācija',
                    value: this.item?.primary_value || this.item?.device_code || '',
                    meta: this.item?.primary_meta || this.item?.device_serial || '',
                    notes: [this.item?.primary_note, this.item?.primary_note_secondary].filter(Boolean),
                    icon: this.item?.primary_icon || 'information-circle',
                    tone: this.item?.primary_tone || 'slate',
                },
                {
                    label: this.item?.secondary_label || 'PapildinformĆ„Ācija',
                    value: this.item?.secondary_value || this.item?.requester_name || '',
                    meta: this.item?.secondary_meta || this.item?.requester_meta || '',
                    notes: [this.item?.secondary_note].filter(Boolean),
                    icon: this.item?.secondary_icon || 'user',
                    tone: this.item?.secondary_tone || 'sky',
                },
                {
                    label: this.item?.tertiary_label || 'Papildlauks',
                    value: this.item?.tertiary_value || this.item?.recipient_name || '',
                    meta: this.item?.tertiary_meta || this.item?.recipient_meta || '',
                    notes: [this.item?.tertiary_note].filter(Boolean),
                    icon: this.item?.tertiary_icon || 'stats',
                    tone: this.item?.tertiary_tone || 'slate',
                },
            ].filter((entry) => entry.value || entry.meta || entry.notes.length > 0);
        },
        textLines(value) {
            return String(value || '')
                .split('\n')
                .map((line) => line.trim())
                .filter(Boolean);
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
        lastSessionId: null,
        lastViewMode: null,
        init() {
            // Ć„Ā¢enerĆ„ā€ sesijas ID, lai noteiktu lapas pĆ„ĀrlĆ„Ādi vai navigĆ„Āciju
            this.lastSessionId = this.generateSessionId();
            this.lastViewMode = this.getViewMode();
            this.seenIds = this.readSeenIds();
            this.fetchNotifications(true);
            this.startPolling();
            this.onVisibilityChange = () => {
                if (document.visibilityState === 'visible') {
                    this.fetchNotifications(false);
                }
            };
            document.addEventListener('visibilitychange', this.onVisibilityChange);

            // Clean up stale notifications on page unload
            window.addEventListener('beforeunload', () => this.cleanup());
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
        generateSessionId() {
            // Ć„Ā¢enerĆ„ā€ unikĆ„Ālu sesijas ID Ć…ļ£¼Ć„Ā«s lapas sesijai
            return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },
        getViewMode() {
            // Nolasa paĆ…ļ£¼reizĆ„ā€jo skata reĆ…Ā¾Ć„Ā«mu no glabĆ„Ātuves atslĆ„ā€gas
            const parts = this.storageKey.split(':');
            return parts.length > 2 ? parts[parts.length - 1] : 'user';
        },
        detectViewModeChange() {
            const currentViewMode = this.getViewMode();
            const hasChanged = this.lastViewMode !== currentViewMode;
            this.lastViewMode = currentViewMode;
            return hasChanged;
        },
        cleanup() {
            // SaglabĆ„Ā sesijas informĆ„Āciju, lai nĆ„ĀkamajĆ„Ā ielĆ„ĀdĆ„ā€ varĆ„ā€tu veikt tĆ„Ā«rĆ„Ā«Ć…ļ£¼anu
            try {
                const staleData = sessionStorage.getItem(this.storageKey + ':stale');
                if (staleData) {
                    const parsed = JSON.parse(staleData);
                    // NoĆ…ā€ em vecos sesijas datus, kas ir vecĆ„Āki par 5 minĆ…Ā«tĆ„ā€m
                    const now = Date.now();
                    const freshData = parsed.filter(item => (now - item.timestamp) < 300000);
                    sessionStorage.setItem(this.storageKey + ':stale', JSON.stringify(freshData));
                }
            } catch (e) {
                // IgnorĆ„ā€ glabĆ„Ātuves kĆ„Ā¼Ć…Ā«das
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

                // Nosaka skata reĆ…Ā¾Ć„Ā«ma maiĆ…ā€ u, lai atiestatĆ„Ā«tu redzĆ„ā€tos paziĆ…ā€ ojumus
                const viewModeChanged = this.detectViewModeChange();

                if (!this.bootstrapped || isInitial) {
                    // Ja skata reĆ…Ā¾Ć„Ā«ms mainĆ„Ā«jies, neatzĆ„Ā«mĆ„ā€ paziĆ…ā€ ojumus kĆ„Ā redzĆ„ā€tus uzreiz
                    // Tas Ć„Ā¼auj parĆ„ĀdĆ„Ā«t animĆ„Ācijas, pĆ„ĀrslĆ„ā€dzoties starp admina un lietotĆ„Āja skatu
                    if (viewModeChanged) {
                        // NotĆ„Ā«ra redzĆ„ā€to ID sarakstu jaunajam skata reĆ…Ā¾Ć„Ā«mam, lai paziĆ…ā€ ojumi animĆ„ā€tos
                        this.seenIds = [];
                        this.writeSeenIds();
                        this.bootstrapped = true;
                        // Turpina izpildi, lai paziĆ…ā€ ojumi tiktu parĆ„ĀdĆ„Ā«ti ar animĆ„Āciju
                    } else {
                        notifications.forEach((notification) => this.remember(notification.id));
                        this.bootstrapped = true;
                        return;
                    }
                }

                const now = Date.now();
                const maxAgeMs = 30000; // Only show notifications created within last 30 seconds

                notifications.forEach((notification) => {
                    if (!notification?.id || this.hasSeen(notification.id) || this.items.some((item) => item.id === notification.id)) {
                        return;
                    }

                    // Skip stale notifications (older than 30 seconds)
                    const notificationAge = now - (notification.created_unix * 1000);
                    if (notificationAge > maxAgeMs) {
                        this.remember(notification.id);
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
                // IgnorĆ„ā€ Ć„Ā«slaicĆ„Ā«gas aptaujas kĆ„Ā¼Ć…Ā«das un mĆ„ā€Ć„Ā£ina vĆ„ā€lreiz nĆ„ĀkamajĆ„Ā ciklĆ„Ā.
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
                writeoff: 'NorakstĆ„Ā«Ć…ļ£¼ana',
                transfer: 'NodoĆ…ļ£¼ana',
                'incoming-transfer': 'JĆ„Āizskata',
            }[type] ?? 'PieprasĆ„Ā«jums';
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
                // IgnorĆ„ā€ glabĆ„Ātuves rakstĆ„Ā«Ć…ļ£¼anas kĆ„Ā¼Ć…Ā«das; paziĆ…ā€ ojumi sesijas laikĆ„Ā joprojĆ„Ām darbosies.
            }
        },
    }));

    Alpine.data('navNotificationCenter', ({
        initialCount = 0,
        endpoint = '',
        markReadUrl = '',
        csrfToken = '',
        storageKey = 'nav-notification-center',
        pollSeconds = 15,
    } = {}) => ({
        open: false,
        unreadCount: Number(initialCount) || 0,
        markReadUrl,
        endpoint,
        csrfToken,
        storageKey,
        pollSeconds: Math.max(Number(pollSeconds) || 15, 5),
        pollTimer: null,
        markingAllRead: false,
        readFeedbackVisible: false,
        feedbackTimer: null,
        init() {
            this.refreshUnreadCount();
            this.startPolling();
        },
        destroy() {
            if (this.pollTimer) {
                window.clearInterval(this.pollTimer);
            }

            if (this.feedbackTimer) {
                window.clearTimeout(this.feedbackTimer);
            }
        },
        startPolling() {
            if (!this.endpoint) {
                return;
            }

            this.pollTimer = window.setInterval(() => {
                if (document.visibilityState === 'visible') {
                    this.refreshUnreadCount();
                }
            }, this.pollSeconds * 1000);
        },
        readCutoff() {
            try {
                return Number(window.localStorage.getItem(this.storageKey) || 0) || 0;
            } catch (error) {
                return 0;
            }
        },
        writeCutoff(timestamp) {
            try {
                window.localStorage.setItem(this.storageKey, String(timestamp));
            } catch (error) {
                // IgnorĆ„ā€ glabĆ„Ātuves kĆ„Ā¼Ć…Ā«das; navigĆ„Ācijas emblĆ„ā€ma Ć…ļ£¼ajĆ„Ā attĆ„ā€lojumĆ„Ā joprojĆ„Ām darbosies.
            }
        },
        showFeedback() {
            this.readFeedbackVisible = true;

            if (this.feedbackTimer) {
                window.clearTimeout(this.feedbackTimer);
            }

            this.feedbackTimer = window.setTimeout(() => {
                this.readFeedbackVisible = false;
            }, 2200);
        },
        async refreshUnreadCount() {
            if (!this.endpoint || !window.axios) {
                return;
            }

            try {
                const response = await window.axios.get(this.endpoint, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const cutoff = this.readCutoff();
                const notifications = Array.isArray(response?.data?.notifications)
                    ? response.data.notifications
                    : [];

                this.unreadCount = notifications.filter((notification) => {
                    const createdUnix = Number(notification?.created_unix || 0);
                    return createdUnix > 0 && (createdUnix * 1000) > cutoff;
                }).length;
            } catch (error) {
                // IgnorĆ„ā€ Ć„Ā«slaicĆ„Ā«gas emblĆ„ā€mas atjaunoĆ…ļ£¼anas kĆ„Ā¼Ć…Ā«das.
            }
        },
        async markAllAsRead() {
            if (this.markingAllRead || !this.markReadUrl) {
                return;
            }

            this.markingAllRead = true;
            const cutoff = Date.now();
            const previousCount = this.unreadCount;

            this.writeCutoff(cutoff);
            this.unreadCount = 0;
            this.showFeedback();

            try {
                await fetch(this.markReadUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
            } catch (error) {
                this.unreadCount = previousCount;
            } finally {
                this.markingAllRead = false;
            }
        },
    }));

    Alpine.data('searchableSelect', ({ selected = '', query = '', options = [], placeholder = '', emptyMessage = '', identifier = '' } = {}) => ({
        open: false,
        selected: String(selected ?? ''),
        query: query || '',
        identifier,
        pointerSelecting: false,
        triggerDragging: false,
        dragPreviewActive: false,
        suppressNextClick: false,
        dragCommitted: false,
        dragStartY: 0,
        dragStartIndex: 0,
        dragStepPx: 20,
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
        init() {
            if (this.selected && !this.query) {
                const current = this.options.find((option) => option.value === this.selected);
                if (current) {
                    this.query = current.label;
                }
            }
        },
        get interactionOptions() {
            if (this.triggerDragging || this.dragPreviewActive) {
                return this.options;
            }

            if (this.open && this.showAllOptions) {
                return this.options;
            }

            const term = this.query.trim().toLowerCase();

            if (term === '') {
                return this.options;
            }

            return this.options.filter((option) => option.search.includes(term));
        },
        get filteredOptions() {
            return this.interactionOptions;
        },
        get activeDescendantId() {
            if (!this.open || this.filteredOptions.length === 0) {
                return null;
            }

            return this.optionId(this.highlightedIndex);
        },
        optionId(index) {
            const baseIdentifier = this.identifier || 'searchable-select';
            return `${baseIdentifier}-option-${index}`;
        },
        togglePanel() {
            if (this.open) {
                this.closePanelOnly();
                return;
            }

            this.openPanel();
        },
        openPanel() {
            this.open = true;
            this.showAllOptions = true;
            this.preparePanel();
            this.$nextTick(() => {
                this.$refs.input?.focus({ preventScroll: true });
                this.$refs.input?.select();
            });
        },
        currentOptionIndex() {
            const selectedIndex = this.interactionOptions.findIndex((option) => option.value === this.selected);
            if (selectedIndex >= 0) {
                return selectedIndex;
            }

            return Math.min(this.highlightedIndex, Math.max(this.interactionOptions.length - 1, 0));
        },
        previewOptionAt(index) {
            if (index < 0 || index >= this.interactionOptions.length) {
                return null;
            }

            return this.interactionOptions[index] ?? null;
        },
        currentPreviewOption() {
            return this.previewOptionAt(this.highlightedIndex);
        },
        previousPreviewOption() {
            return this.previewOptionAt(this.highlightedIndex - 1);
        },
        nextPreviewOption() {
            return this.previewOptionAt(this.highlightedIndex + 1);
        },
        startTriggerInteraction(event) {
            this.triggerDragging = true;
            this.dragPreviewActive = false;
            this.suppressNextClick = false;
            this.dragCommitted = false;
            this.dragStartY = event?.clientY ?? 0;
            this.dragStartIndex = this.currentOptionIndex();
            this.highlightedIndex = this.dragStartIndex;
        },
        handleTriggerClick() {
            if (this.suppressNextClick) {
                this.suppressNextClick = false;
                return;
            }

            if (!this.open) {
                this.openPanel();
            }
        },
        handleTogglePointerDown() {
            this.togglePanel();
        },
        handleTriggerDrag(event) {
            if (!this.triggerDragging || this.interactionOptions.length === 0) {
                return;
            }

            const delta = (event?.clientY ?? 0) - this.dragStartY;
            const steps = delta === 0 ? 0 : Math.trunc(delta / this.dragStepPx);
            const maxIndex = this.interactionOptions.length - 1;
            const nextIndex = Math.min(maxIndex, Math.max(0, this.dragStartIndex + steps));

            if (Math.abs(delta) >= Math.max(6, this.dragStepPx / 3)) {
                this.dragPreviewActive = true;
                this.open = false;
                this.showAllOptions = false;
            }

            if (nextIndex !== this.highlightedIndex) {
                this.highlightedIndex = nextIndex;
                this.dragCommitted = true;
                const option = this.currentPreviewOption();
                if (option) {
                    this.selected = option.value;
                    this.query = option.label;
                }
            }
        },
        finishTriggerInteraction() {
            if (!this.triggerDragging) {
                return;
            }

            this.triggerDragging = false;
            this.suppressNextClick = this.dragPreviewActive;

            if (!this.dragCommitted) {
                this.dragPreviewActive = false;
                return;
            }

            const option = this.interactionOptions[this.highlightedIndex];
            if (!option) {
                this.dragPreviewActive = false;
                return;
            }

            this.selected = option.value;
            this.query = option.label;
            this.dispatchUpdate({ submit: true });
            this.dragPreviewActive = false;
        },
        closePanelOnly() {
            this.open = false;
            this.showAllOptions = false;
            this.pointerSelecting = false;
            this.triggerDragging = false;
            this.dragPreviewActive = false;
            this.suppressNextClick = false;
            this.dragCommitted = false;
        },
        close() {
            this.closePanelOnly();
        },
        clearSelection() {
            this.selected = '';
            this.query = '';
            this.highlightedIndex = 0;
            this.dispatchUpdate({ submit: true });
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
        choose(option) {
            this.selected = option.value;
            this.query = option.label;
            this.dispatchUpdate({ submit: true });
            this.closePanelOnly();
        },
        handleOptionDrag(index, event) {
            if (event?.buttons === 1 || this.pointerSelecting) {
                this.highlightedIndex = index;
                this.$nextTick(() => this.scrollToHighlighted());
            }
        },
        startPointerSelection(index) {
            this.pointerSelecting = true;
            this.highlightedIndex = index;
            this.$nextTick(() => this.scrollToHighlighted());
        },
        finishPointerSelection(index) {
            if (!this.pointerSelecting) {
                return;
            }

            this.pointerSelecting = false;
            const option = this.filteredOptions[index];
            if (option) {
                this.choose(option);
            }
        },
        dispatchUpdate({ submit = true } = {}) {
            if (!this.identifier) {
                return;
            }

            this.$dispatch('searchable-select-updated', {
                identifier: this.identifier,
                value: this.selected,
                query: this.query,
                submit,
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
    }));
};

registerAlpineData();
document.addEventListener('alpine:init', registerAlpineData);

// Keep one explicit startup pipeline so future refactors are easy to follow.
const appInitializers = Object.freeze([
    () => initializeThemeToggle({ readStorageValue, writeStorageValue }),
    initializeAppConfirm,
    initializeAsyncTableFilters,
    restoreHighlightedSearchFromUrl,
]);

const runAppInitializers = () => {
    appInitializers.forEach((initializer) => initializer());
};

runOnDomReady(runAppInitializers);

if (Alpine && !window.__appAlpineStarted) {
    window.__appAlpineStarted = true;
    Alpine.start();
}

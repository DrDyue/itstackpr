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

const createToastIcon = (tone) => {
    if (tone === 'success') {
        return `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        `;
    }

    return `
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25 12 11.25v4.5m0-8.25h.008v.008H12V7.5ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    `;
};

const ensureAppToastRoot = () => {
    let root = document.querySelector('[data-app-toast-root]');

    if (root) {
        return root;
    }

    root = document.createElement('div');
    root.dataset.appToastRoot = 'true';
    root.className = 'pointer-events-none fixed bottom-4 right-4 z-[68] flex w-[min(26rem,calc(100vw-1.5rem))] flex-col gap-3 sm:bottom-6 sm:right-6';
    document.body.appendChild(root);

    return root;
};

const dismissAppToast = (toast) => {
    if (!toast || toast.dataset.closing === '1') {
        return;
    }

    toast.dataset.closing = '1';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(16px) scale(0.97)';

    window.setTimeout(() => {
        toast.remove();
    }, 240);
};

window.dispatchAppToast = ({ message = '', tone = 'info', title = '' } = {}) => {
    if (!message) {
        return;
    }

    const root = ensureAppToastRoot();
    const toast = document.createElement('div');
    const normalizedTone = tone === 'success' ? 'success' : 'info';

    toast.className = `flash-toast flash-toast-${normalizedTone} pointer-events-auto`;
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(16px) scale(0.96)';
    toast.style.transition = 'opacity 260ms ease, transform 260ms ease';
    toast.innerHTML = `
        <div class="flash-toast-icon">${createToastIcon(normalizedTone)}</div>
        <div class="flash-toast-body">
            <div class="flash-toast-title">${title || (normalizedTone === 'success' ? 'Veiksmīgi' : 'Paziņojums')}</div>
            <div class="flash-toast-message">${message}</div>
        </div>
        <button type="button" class="flash-toast-close" aria-label="Aizvērt">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    `;

    toast.querySelector('.flash-toast-close')?.addEventListener('click', () => dismissAppToast(toast));
    root.prepend(toast);

    window.requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0) scale(1)';
    });

    window.setTimeout(() => dismissAppToast(toast), 3800);
};

const ensureAppConfirmRoot = () => {
    let root = document.querySelector('[data-app-confirm-root]');

    if (root) {
        return root;
    }

    root = document.createElement('div');
    root.dataset.appConfirmRoot = 'true';
    root.className = 'app-confirm-overlay hidden';
    root.innerHTML = `
        <div class="app-confirm-backdrop" data-app-confirm-close="true"></div>
        <div class="app-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="app-confirm-title">
            <div class="app-confirm-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h3 id="app-confirm-title" class="app-confirm-title">Apstiprini darbību</h3>
            <p class="app-confirm-message" data-app-confirm-message></p>
            <div class="app-confirm-actions">
                <button type="button" class="btn-clear" data-app-confirm-cancel="true">Nē</button>
                <button type="button" class="btn-danger-solid" data-app-confirm-accept="true">Jā</button>
            </div>
        </div>
    `;

    document.body.appendChild(root);

    return root;
};

window.openAppConfirm = (options = {}) => {
    const {
        title = 'Apstiprini darbību',
        message = 'Vai tiešām vēlaties turpināt?',
        confirmLabel = 'Jā',
        cancelLabel = 'Nē',
        tone = 'danger',
    } = options;

    const root = ensureAppConfirmRoot();
    const titleNode = root.querySelector('#app-confirm-title');
    const messageNode = root.querySelector('[data-app-confirm-message]');
    const acceptButton = root.querySelector('[data-app-confirm-accept]');
    const cancelButtons = root.querySelectorAll('[data-app-confirm-cancel]');
    const dismissButtons = root.querySelectorAll('[data-app-confirm-cancel], [data-app-confirm-close]');

    titleNode.textContent = title;
    messageNode.textContent = message;
    acceptButton.textContent = confirmLabel;
    cancelButtons.forEach((button) => {
        button.textContent = cancelLabel;
    });

    acceptButton.classList.toggle('btn-danger-solid', tone !== 'warning');
    acceptButton.classList.toggle('btn-approve', tone === 'warning');

    root.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    window.requestAnimationFrame(() => root.classList.add('is-visible'));
    acceptButton.focus();

    return new Promise((resolve) => {
        const cleanup = (result) => {
            root.classList.remove('is-visible');
            document.body.classList.remove('overflow-hidden');
            window.setTimeout(() => root.classList.add('hidden'), 180);

            acceptButton.removeEventListener('click', handleAccept);
            dismissButtons.forEach((button) => button.removeEventListener('click', handleCancel));
            window.removeEventListener('keydown', handleKeyDown);

            resolve(result);
        };

        const handleAccept = () => cleanup(true);
        const handleCancel = () => cleanup(false);
        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                cleanup(false);
            }
        };

        acceptButton.addEventListener('click', handleAccept);
        dismissButtons.forEach((button) => button.addEventListener('click', handleCancel));
        window.addEventListener('keydown', handleKeyDown);
    });
};

const initializeAppConfirm = () => {
    if (window.__appConfirmInitialized) {
        return;
    }

    window.__appConfirmInitialized = true;

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = form.dataset.appConfirmMessage;
        if (!message) {
            return;
        }

        if (form.dataset.appConfirmBypass === '1') {
            form.dataset.appConfirmBypass = '0';
            return;
        }

        event.preventDefault();

        const accepted = await window.openAppConfirm({
            title: form.dataset.appConfirmTitle || 'Apstiprini darbību',
            message,
            confirmLabel: form.dataset.appConfirmAccept || 'Jā',
            cancelLabel: form.dataset.appConfirmCancel || 'Nē',
            tone: form.dataset.appConfirmTone || 'danger',
        });

        if (!accepted) {
            return;
        }

        form.dataset.appConfirmBypass = '1';
        form.requestSubmit();
    });
};

const asyncTableControllers = new Map();
const asyncTableDebounceTimers = new WeakMap();

const findAsyncTableRoot = (element) => {
    if (!element) {
        return null;
    }

    if (element.matches?.('[data-async-table-root]')) {
        return element;
    }

    return element.closest?.('[data-async-table-root]') ?? null;
};

const findAsyncTableForm = (element) => {
    if (!element) {
        return null;
    }

    if (element.matches?.('[data-async-table-form]')) {
        return element;
    }

    return element.closest?.('[data-async-table-form]') ?? null;
};

const buildAsyncTableUrl = (form, { resetPage = true } = {}) => {
    const action = form.getAttribute('action') || window.location.href;
    const url = new URL(action, window.location.origin);
    const formData = new window.FormData(form);

    url.search = '';

    for (const [key, value] of formData.entries()) {
        if (key === 'page' && resetPage) {
            continue;
        }

        if (typeof value === 'string' && value.trim() === '') {
            continue;
        }

        url.searchParams.append(key, value);
    }

    return url;
};

const swapAsyncTableRoot = (rootSelector, html) => {
    const parser = new DOMParser();
    const nextDocument = parser.parseFromString(html, 'text/html');
    const nextRoot = nextDocument.querySelector(rootSelector);
    const currentRoot = document.querySelector(rootSelector);

    if (!nextRoot || !currentRoot) {
        return false;
    }

    currentRoot.outerHTML = nextRoot.outerHTML;

    return true;
};

window.submitAsyncTableForm = async (form, { url = null, resetPage = true, toastMessage = '' } = {}) => {
    const rootSelector = form?.dataset?.asyncRoot;

    if (!form || !rootSelector) {
        return false;
    }

    const targetUrl = url instanceof URL ? url : buildAsyncTableUrl(form, { resetPage });
    
    // Add cache-busting timestamp for filter clear actions
    if (targetUrl.searchParams.has('clear')) {
        targetUrl.searchParams.set('_t', Date.now().toString());
    }
    
    const requestKey = rootSelector;

    if (asyncTableControllers.has(requestKey)) {
        asyncTableControllers.get(requestKey)?.abort();
    }

    const controller = new AbortController();
    asyncTableControllers.set(requestKey, controller);
    form.dataset.asyncLoading = 'true';

    try {
        const response = await window.fetch(targetUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
            priority: 'high',
        });

        if (!response.ok) {
            throw new Error('Async table request failed.');
        }

        const html = await response.text();
        const swapped = swapAsyncTableRoot(rootSelector, html);

        if (!swapped) {
            window.location.assign(targetUrl.toString());
            return false;
        }

        window.history.replaceState({}, '', `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`);

        if (toastMessage) {
            window.dispatchAppToast({
                message: toastMessage,
                tone: 'info',
            });
        }

        return true;
    } catch (error) {
        if (error.name !== 'AbortError') {
            window.location.assign(targetUrl.toString());
        }

        return false;
    } finally {
        if (asyncTableControllers.get(requestKey) === controller) {
            asyncTableControllers.delete(requestKey);
        }

        form.dataset.asyncLoading = 'false';
    }
};

const debounceAsyncTableSubmit = (form, delay = 260) => {
    if (asyncTableDebounceTimers.has(form)) {
        window.clearTimeout(asyncTableDebounceTimers.get(form));
    }

    const timerId = window.setTimeout(() => {
        submitAsyncTableForm(form, { resetPage: true });
        asyncTableDebounceTimers.delete(form);
    }, delay);

    asyncTableDebounceTimers.set(form, timerId);
};

const normalizeTableSearchValue = (value) => String(value ?? '').trim().toLocaleLowerCase();

const clearTableSearchHighlights = (root) => {
    root?.querySelectorAll('.table-search-hit').forEach((row) => {
        row.classList.remove('table-search-hit');
    });
};

const highlightTableRow = (row) => {
    row.classList.remove('table-search-hit');
    void row.offsetWidth;
    row.classList.add('table-search-hit');

    window.setTimeout(() => {
        row.classList.remove('table-search-hit');
    }, 2400);
};

const getManualSearchInput = (form) => {
    return form?.querySelector('[data-async-code-search="true"], [data-table-manual-search="true"]');
};

const getManualSearchMode = (input) => {
    if (!input) {
        return 'contains';
    }

    return input.dataset.searchMode || (input.matches('[data-async-code-search="true"]') ? 'exact' : 'contains');
};

const getRowSearchValue = (row) => {
    return row.dataset.tableSearchValue || row.dataset.tableCode || '';
};

const getRowSearchId = (row) => {
    return String(row?.dataset?.tableRowId || '').trim();
};

const rowMatchesSearch = (row, term, mode = 'contains') => {
    const value = normalizeTableSearchValue(getRowSearchValue(row));
    const normalizedTerm = normalizeTableSearchValue(term);

    if (!value || !normalizedTerm) {
        return false;
    }

    if (mode === 'exact') {
        return value === normalizedTerm;
    }

    return value.includes(normalizedTerm);
};

const findMatchingTableRow = (root, term, mode = 'contains') => {
    return Array.from(root?.querySelectorAll('[data-table-search-value], [data-table-code]') ?? [])
        .find((row) => rowMatchesSearch(row, term, mode)) || null;
};

const findTableRowById = (root, rowId) => {
    const normalizedRowId = String(rowId || '').trim();

    if (!normalizedRowId) {
        return null;
    }

    return root?.querySelector?.(`[data-table-row-id="${window.CSS?.escape ? window.CSS.escape(normalizedRowId) : normalizedRowId}"]`) || null;
};

const buildSearchNavigationUrl = (form, page, rawTerm, mode, highlightId = '') => {
    const targetUrl = new URL(form.getAttribute('action') || window.location.href, window.location.origin);
    const formData = new FormData(form);

    for (const [key, value] of formData.entries()) {
        if (value === '') {
            continue;
        }

        targetUrl.searchParams.append(key, value);
    }

    targetUrl.searchParams.set('page', String(page));
    targetUrl.searchParams.set('highlight', rawTerm);
    targetUrl.searchParams.set('highlight_mode', mode);
    if (highlightId) {
        targetUrl.searchParams.set('highlight_id', String(highlightId));
    } else {
        targetUrl.searchParams.delete('highlight_id');
    }

    return targetUrl;
};

const performManualTableSearch = async (form) => {
    const rootSelector = form?.dataset?.asyncRoot;
    const searchInput = getManualSearchInput(form);

    if (!rootSelector || !searchInput) {
        return false;
    }

    const root = document.querySelector(rootSelector);
    if (!root) {
        return false;
    }

    const rawTerm = searchInput.value.trim();
    const normalizedTerm = normalizeTableSearchValue(rawTerm);
    const searchMode = getManualSearchMode(searchInput);

    if (!normalizedTerm) {
        window.dispatchAppToast({
            title: 'Meklēšana',
            message: 'Ievadi meklējamo vērtību, lai atrastu konkrēto ierakstu.',
            tone: 'info',
        });
        searchInput.focus();

        return true;
    }

    clearTableSearchHighlights(root);

    const match = findMatchingTableRow(root, rawTerm, searchMode);

    if (match) {
        highlightTableRow(match);
        match.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest',
        });

        return true;
    }

    const searchEndpoint = form.dataset.searchEndpoint;
    if (!searchEndpoint) {
        window.dispatchAppToast({
            title: 'Ieraksts netika atrasts',
            message: `Pašreizējā skatā netika atrasts ieraksts "${rawTerm}".`,
            tone: 'info',
        });

        return true;
    }

    try {
        const endpointUrl = new URL(searchEndpoint, window.location.origin);
        const formData = new FormData(form);

        for (const [key, value] of formData.entries()) {
            if (value === '') {
                continue;
            }

            endpointUrl.searchParams.append(key, value);
        }

        const response = await fetch(endpointUrl.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Search request failed: ${response.status}`);
        }

        const result = await response.json();
        if (!result?.found) {
            window.dispatchAppToast({
                title: 'Ieraksts netika atrasts',
                message: `Ieraksts "${rawTerm}" netika atrasts nevienā lapā.`,
                tone: 'info',
            });

            return true;
        }

        const targetUrl = buildSearchNavigationUrl(form, result.page, rawTerm, searchMode, result.highlight_id ?? '');
        const swapped = await submitAsyncTableForm(form, {
            url: targetUrl,
            resetPage: false,
        });

        if (swapped) {
            restoreHighlightedSearchFromUrl();
            return true;
        }

        window.location.assign(targetUrl.toString());
    } catch (error) {
        window.dispatchAppToast({
            title: 'Meklēšana neizdevās',
            message: 'Neizdevās atrast ierakstu. Mēģini vēlreiz.',
            tone: 'error',
        });
    }

    return true;
};

const shouldRunManualSearch = (form, submitter) => {
    const searchInput = getManualSearchInput(form);
    if (!searchInput) {
        return false;
    }

    if (submitter?.matches?.('[data-code-search-submit="true"], [data-table-search-submit="true"]')) {
        return true;
    }

    return document.activeElement === searchInput;
};

const restoreHighlightedSearchFromUrl = () => {
    const currentUrl = new URL(window.location.href);
    const term = currentUrl.searchParams.get('highlight');
    const mode = currentUrl.searchParams.get('highlight_mode') || 'contains';
    const highlightId = currentUrl.searchParams.get('highlight_id');

    if (!term && !highlightId) {
        return;
    }

    const match = findTableRowById(document, highlightId) || findMatchingTableRow(document, term, mode);
    if (!match) {
        return;
    }

    highlightTableRow(match);
    window.setTimeout(() => {
        match.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'nearest',
        });
    }, 120);

    currentUrl.searchParams.delete('highlight');
    currentUrl.searchParams.delete('highlight_mode');
    currentUrl.searchParams.delete('highlight_id');
    window.history.replaceState({}, '', `${currentUrl.pathname}${currentUrl.search}${currentUrl.hash}`);
};

const initializeAsyncTableFilters = () => {
    if (window.__asyncTableFiltersInitialized) {
        return;
    }

    window.__asyncTableFiltersInitialized = true;

    document.addEventListener('submit', async (event) => {
        const form = findAsyncTableForm(event.target);

        if (!form) {
            return;
        }

        event.preventDefault();

        if (shouldRunManualSearch(form, event.submitter) && await performManualTableSearch(form)) {
            return;
        }

        submitAsyncTableForm(form, { resetPage: true });
    });

    document.addEventListener('input', (event) => {
        const target = event.target;
        const form = findAsyncTableForm(target);

        if (!form) {
            return;
        }

        if (target.closest('.searchable-select')) {
            return;
        }

        if (target.matches('[data-async-manual=\"true\"]')) {
            return;
        }

        if (!target.matches('input[type=\"text\"], input[type=\"search\"], input[type=\"number\"], textarea')) {
            return;
        }

        debounceAsyncTableSubmit(form, 280);
    });

    document.addEventListener('change', (event) => {
        const target = event.target;
        const form = findAsyncTableForm(target);

        if (!form) {
            return;
        }

        if (target.matches('[data-sort-hidden]')) {
            return;
        }

        submitAsyncTableForm(form, { resetPage: true });
    });

    document.addEventListener('click', (event) => {
        const sortTrigger = event.target.closest('[data-sort-trigger]');
        if (sortTrigger) {
            const root = findAsyncTableRoot(sortTrigger);
            const form = findAsyncTableForm(sortTrigger) || root?.querySelector('[data-async-table-form]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const fieldInput = form.querySelector('[data-sort-hidden=\"field\"]');
            const directionInput = form.querySelector('[data-sort-hidden=\"direction\"]');

            if (fieldInput) {
                fieldInput.value = sortTrigger.dataset.sortField || '';
            }

            if (directionInput) {
                directionInput.value = sortTrigger.dataset.sortDirection || 'asc';
            }

            // Don't show toast for sorting operations - too noisy
            submitAsyncTableForm(form, {
                resetPage: true,
                toastMessage: '',
            });

            return;
        }

        const asyncLink = event.target.closest('a[data-async-link=\"true\"], a.quick-status-filter');
        if (!asyncLink) {
            return;
        }

        const root = findAsyncTableRoot(asyncLink);
        if (!root) {
            return;
        }

        const form = root.querySelector('[data-async-table-form]');
        if (!form) {
            return;
        }

        const href = asyncLink.getAttribute('href');
        if (!href) {
            return;
        }

        event.preventDefault();
        submitAsyncTableForm(form, {
            url: new URL(href, window.location.origin),
            resetPage: false,
        });
    });

    document.addEventListener('searchable-select-updated', (event) => {
        const form = findAsyncTableForm(event.target);

        if (!form) {
            return;
        }

        window.setTimeout(() => {
            submitAsyncTableForm(form, { resetPage: true });
        }, 0);
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
            // Generate session ID to detect page reloads/navigation
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
            // Generate unique session ID for this page session
            return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },
        getViewMode() {
            // Get current view mode from the storage key
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
            // Store session info for cleanup on next load
            try {
                const staleData = sessionStorage.getItem(this.storageKey + ':stale');
                if (staleData) {
                    const parsed = JSON.parse(staleData);
                    // Remove old session data that's more than 5 minutes old
                    const now = Date.now();
                    const freshData = parsed.filter(item => (now - item.timestamp) < 300000);
                    sessionStorage.setItem(this.storageKey + ':stale', JSON.stringify(freshData));
                }
            } catch (e) {
                // Ignore storage errors
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

                // Detect view mode change to reset seen notifications
                const viewModeChanged = this.detectViewModeChange();

                if (!this.bootstrapped || isInitial) {
                    // If view mode changed, don't mark notifications as seen immediately
                    // This allows animations to show when switching between admin/user views
                    if (viewModeChanged) {
                        // Clear seen IDs for new view mode to show notifications with animation
                        this.seenIds = [];
                        this.writeSeenIds();
                        this.bootstrapped = true;
                        // Fall through to show notifications with animation
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
                // Ignore storage issues; nav badge will still work for current render.
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
                // Ignore transient badge refresh errors.
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
        if (targetStatus === 'in-progress' && this.showMissingRequirements('waiting', 'Remontu sākt')) {
            return;
        }

        if (targetStatus === 'completed' && this.showMissingRequirements('in-progress', 'Remontu pabeigt')) {
            return;
        }

        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, repairId, targetStatus, extra);
    },
    async submitCompletion(repair) {
        if (!repair?.id) {
            return;
        }

        const repairName = repair.name ?? 'šo remontu';
        const accepted = await window.openAppConfirm({
            title: 'Pabeigt remontu?',
            message: `Vai tiešām gribat pabeigt ierīces remontu "${repairName}"?`,
            confirmLabel: 'Jā, pabeigt',
            cancelLabel: 'Nē',
            tone: 'warning',
        });

        if (!accepted) {
            return;
        }

        this.submitTransition(repair.id, 'completed');
    },
});

window.repairProcess = (config) => ({
    repairType: config.repairType,
    repairStatus: config.status,
    priority: config.priority ?? 'medium',
    description: config.description ?? '',
    vendorName: config.vendorName ?? '',
    vendorContact: config.vendorContact ?? '',
    invoiceNumber: config.invoiceNumber ?? '',
    cost: config.cost ?? '',
    isExternal() {
        return this.repairType === 'external';
    },
    normalizedCost() {
        return String(this.cost ?? '').trim();
    },
    transitionFormPayload() {
        return {
            description: this.description ?? '',
            repair_type: this.repairType ?? 'internal',
            priority: this.priority ?? 'medium',
            cost: this.normalizedCost(),
            vendor_name: this.vendorName ?? '',
            vendor_contact: this.vendorContact ?? '',
            invoice_number: this.invoiceNumber ?? '',
        };
    },
    requirementRows(targetStatus = this.repairStatus) {
        if (targetStatus !== 'completed' && this.repairStatus !== 'in-progress') {
            return [];
        }

        const rows = [
            { key: 'description', label: 'Apraksts', done: String(this.description ?? '').trim() !== '' },
        ];

        if (this.isExternal()) {
            rows.push(
                { key: 'vendor_name', label: 'Pakalpojuma sniedzējs', done: String(this.vendorName ?? '').trim() !== '' },
                { key: 'vendor_contact', label: 'Vendora kontakts', done: String(this.vendorContact ?? '').trim() !== '' },
                { key: 'invoice_number', label: 'Rēķina numurs', done: String(this.invoiceNumber ?? '').trim() !== '' },
            );
        }

        return rows;
    },
    nextStepLabel() {
        if (this.repairStatus === 'in-progress') {
            return this.isExternal()
                ? 'Lai pabeigtu ārējo remontu, jāaizpilda apraksts, pakalpojuma sniedzējs, vendora kontakts un rēķina numurs.'
                : 'Lai pabeigtu iekšējo remontu, jāaizpilda tikai apraksts.';
        }

        return 'Šim statusam papildu prasības nav nepieciešamas.';
    },
    nextStepReady() {
        const rows = this.requirementRows();
        return rows.length > 0 && rows.every((item) => item.done);
    },
    missingRequirementLabels(targetStatus) {
        return this.requirementRows(targetStatus)
            .filter((item) => !item.done)
            .map((item) => item.label);
    },
    showMissingRequirements(targetStatus, actionLabel) {
        const missing = this.missingRequirementLabels(targetStatus);
        if (missing.length === 0) {
            return false;
        }

        window.dispatchAppToast({
            title: 'Darbību nevar izpildīt',
            message: `${actionLabel} nevar, jo vēl trūkst: ${missing.join(', ')}.`,
            tone: 'info',
        });

        return true;
    },
    async submitTransition(repairId, targetStatus, extra = {}) {
        if (targetStatus === 'completed' && this.showMissingRequirements('completed', 'Remontu pabeigt')) {
            return;
        }

        const actionLabels = {
            waiting: 'atgriezt uz gaida',
            'in-progress': 'sākt remontu',
            completed: 'pabeigt remontu',
            cancelled: 'atcelt remontu',
        };

        const accepted = await window.openAppConfirm({
            title: 'Apstiprini statusa maiņu',
            message: `Vai tiešām vēlaties ${actionLabels[targetStatus] ?? 'mainīt remonta statusu'}?`,
            confirmLabel: 'Jā',
            cancelLabel: 'Nē',
            tone: targetStatus === 'cancelled' ? 'danger' : 'warning',
        });

        if (!accepted) {
            return;
        }

        window.submitRepairTransition(
            config.transitionBaseUrl,
            config.csrfToken,
            repairId,
            targetStatus,
            {
                ...this.transitionFormPayload(),
                ...extra,
            },
        );
    },
    async submitCompletion() {
        if (this.showMissingRequirements('completed', 'Remontu pabeigt')) {
            return;
        }

        const accepted = await window.openAppConfirm({
            title: 'Pabeigt remontu?',
            message: 'Vai tiešām gribat pabeigt šo ierīces remontu?',
            confirmLabel: 'Jā, pabeigt',
            cancelLabel: 'Nē',
            tone: 'warning',
        });

        if (!accepted) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `${config.transitionBaseUrl}/${config.repairId}/completion`;
        form.style.display = 'none';

        appendHiddenInput(form, '_token', config.csrfToken);

        Object.entries(this.transitionFormPayload()).forEach(([key, value]) => {
            appendHiddenInput(form, key, value);
        });

        document.body.appendChild(form);
        form.submit();
    },
});

registerAlpineData();
document.addEventListener('alpine:init', registerAlpineData);
document.addEventListener('DOMContentLoaded', initializeThemeToggle);
document.addEventListener('DOMContentLoaded', initializeAppConfirm);
document.addEventListener('DOMContentLoaded', initializeAsyncTableFilters);
document.addEventListener('DOMContentLoaded', restoreHighlightedSearchFromUrl);

if (document.readyState !== 'loading') {
    initializeThemeToggle();
    initializeAppConfirm();
    initializeAsyncTableFilters();
    restoreHighlightedSearchFromUrl();
}

if (Alpine && !window.__appAlpineStarted) {
    window.__appAlpineStarted = true;
    Alpine.start();
}

// Clear all filters function for request index pages
window.clearAllFilters = function(button) {
    const form = button.closest('form[data-async-table-form]');
    if (!form) return;

    const root = form.closest('[data-async-table-root]');
    if (!root) return;

    // Build URL with only statuses_filter=1 (to indicate filters were cleared)
    const url = new URL(form.action, window.location.origin);
    
    // Keep sort params
    const sortField = form.querySelector('input[data-sort-hidden="field"]');
    const sortDirection = form.querySelector('input[data-sort-hidden="direction"]');
    
    const params = new URLSearchParams();
    params.append('statuses_filter', '1');
    if (sortField) params.append('sort', sortField.value);
    if (sortDirection) params.append('direction', sortDirection.value);
    
    url.search = '?' + params.toString();

    // Use the existing submitAsyncTableForm function
    window.submitAsyncTableForm(form, {
        url: url,
        resetPage: true,
        toastMessage: 'Filtri notīrīti'
    });
};

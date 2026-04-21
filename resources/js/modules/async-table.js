const asyncTableControllers = new Map();
const asyncTableDebounceTimers = new WeakMap();
const searchableSelectSubmitTimers = new WeakMap();

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

    const mountedRoot = document.querySelector(rootSelector);
    if (mountedRoot && window.Alpine?.initTree) {
        window.Alpine.initTree(mountedRoot);
    }

    return true;
};

const debounceAsyncTableSubmit = (form, delay = 260) => {
    if (asyncTableDebounceTimers.has(form)) {
        window.clearTimeout(asyncTableDebounceTimers.get(form));
    }

    const timerId = window.setTimeout(() => {
        window.submitAsyncTableForm(form, { resetPage: true });
        asyncTableDebounceTimers.delete(form);
    }, delay);

    asyncTableDebounceTimers.set(form, timerId);
};

const cancelPendingAsyncTableWork = (form) => {
    if (!form) {
        return;
    }

    if (asyncTableDebounceTimers.has(form)) {
        window.clearTimeout(asyncTableDebounceTimers.get(form));
        asyncTableDebounceTimers.delete(form);
    }

    if (searchableSelectSubmitTimers.has(form)) {
        window.clearTimeout(searchableSelectSubmitTimers.get(form));
        searchableSelectSubmitTimers.delete(form);
    }

    const rootSelector = form.dataset?.asyncRoot;
    if (!rootSelector) {
        return;
    }

    const controller = asyncTableControllers.get(rootSelector);
    if (controller) {
        controller.abort();
        asyncTableControllers.delete(rootSelector);
    }
};

const getAlpineComponentData = (element) => {
    const stack = element?._x_dataStack;
    if (!Array.isArray(stack) || stack.length === 0) {
        return null;
    }

    return stack[0] ?? null;
};

const clearAsyncTableFormUi = (form, root) => {
    if (!form) {
        return;
    }

    form.querySelectorAll('input[type="text"], input[type="search"], input[type="number"], textarea').forEach((input) => {
        if (input.matches('[data-sort-hidden], [data-async-manual="true"]')) {
            return;
        }

        input.value = '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    form.querySelectorAll('input[type="hidden"]').forEach((input) => {
        if (input.matches('[data-sort-hidden]') || input.name === 'statuses_filter') {
            return;
        }

        input.value = '';
    });

    const defaultDateField = form.querySelector('input[type="radio"][name="date_field"][value="start_date"]');
    if (defaultDateField) {
        defaultDateField.checked = true;
    }

    form.querySelectorAll('.searchable-select').forEach((element) => {
        const data = getAlpineComponentData(element);
        if (!data) {
            return;
        }

        data.selected = '';
        data.query = '';
        data.highlightedIndex = 0;
        data.closePanelOnly?.();
    });

    form.querySelectorAll('.localized-date-picker').forEach((element) => {
        const data = getAlpineComponentData(element);
        if (!data) {
            return;
        }

        data.value = '';
        data.open = false;
    });

    form.querySelectorAll('.quick-filter-group').forEach((element) => {
        const data = getAlpineComponentData(element);
        if (!data || !Array.isArray(data.selected)) {
            return;
        }

        data.selected = [];
    });

    root?.querySelectorAll('.filter-summary, .active-filters').forEach((element) => {
        element.style.display = 'none';
    });

    form.dataset.asyncClearing = 'true';
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
        const swapped = await window.submitAsyncTableForm(form, {
            url: targetUrl,
            resetPage: false,
        });

        if (swapped) {
            await restoreHighlightedSearchFromUrl();
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

    if (document.activeElement === searchInput) {
        return true;
    }

    return searchInput.value.trim() !== '';
};

export const restoreHighlightedSearchFromUrl = async () => {
    const currentUrl = new URL(window.location.href);
    const term = currentUrl.searchParams.get('highlight');
    const mode = currentUrl.searchParams.get('highlight_mode') || 'contains';
    const highlightId = currentUrl.searchParams.get('highlight_id');

    if (!term && !highlightId) {
        return;
    }

    let match = findTableRowById(document, highlightId) || findMatchingTableRow(document, term, mode);
    if (!match) {
        const form = term ? document.querySelector('[data-async-table-form][data-search-endpoint]') : null;

        if (form?.dataset?.searchEndpoint) {
            try {
                const endpointUrl = new URL(form.dataset.searchEndpoint, window.location.origin);
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

                if (response.ok) {
                    const result = await response.json();

                    if (result?.found) {
                        const targetUrl = buildSearchNavigationUrl(form, result.page, term, mode, result.highlight_id ?? highlightId ?? '');
                        const targetUrlString = targetUrl.toString();

                        if (targetUrlString !== window.location.href) {
                            const swapped = await window.submitAsyncTableForm(form, {
                                url: targetUrl,
                                resetPage: false,
                            });

                            if (swapped) {
                                await restoreHighlightedSearchFromUrl();
                                return;
                            }

                            window.location.assign(targetUrlString);
                            return;
                        }

                        match = findTableRowById(document, result.highlight_id ?? highlightId) || findMatchingTableRow(document, term, mode);
                    }
                }
            } catch (error) {
                // Silent fallback: leave user on current view if highlight recovery fails.
            }
        }
    }

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

export const registerAsyncTableGlobals = () => {
    window.runManualTableSearchFromTrigger = (trigger) => {
        const form = findAsyncTableForm(trigger);

        if (!form || !getManualSearchInput(form)) {
            return false;
        }

        performManualTableSearch(form);

        return false;
    };

    window.submitAsyncTableForm = async (form, { url = null, resetPage = true, toastMessage = '' } = {}) => {
        const rootSelector = form?.dataset?.asyncRoot;

        if (!form || !rootSelector) {
            return false;
        }

        const targetUrl = url instanceof URL ? url : buildAsyncTableUrl(form, { resetPage });
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
};

export const initializeAsyncTableFilters = () => {
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

        window.submitAsyncTableForm(form, { resetPage: true });
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

        if (target.matches('[data-async-manual="true"]')) {
            return;
        }

        if (!target.matches('input[type="text"], input[type="search"], input[type="number"], textarea')) {
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

        window.submitAsyncTableForm(form, { resetPage: true });
    });

    document.addEventListener('click', (event) => {
        const manualSearchTrigger = event.target.closest('[data-code-search-submit="true"], [data-table-search-submit="true"]');
        if (manualSearchTrigger) {
            const form = findAsyncTableForm(manualSearchTrigger);

            if (form && getManualSearchInput(form)) {
                event.preventDefault();
                performManualTableSearch(form);
                return;
            }
        }

        const toastTrigger = event.target.closest('[data-app-toast-message]');
        if (toastTrigger) {
            event.preventDefault();

            window.dispatchAppToast({
                title: toastTrigger.dataset.appToastTitle || 'Darbība nav pieejama',
                message: toastTrigger.dataset.appToastMessage || '',
                tone: toastTrigger.dataset.appToastTone || 'info',
            });

            return;
        }

        const sortTrigger = event.target.closest('[data-sort-trigger]');
        if (sortTrigger) {
            const root = findAsyncTableRoot(sortTrigger);
            const form = findAsyncTableForm(sortTrigger) || root?.querySelector('[data-async-table-form]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const fieldInput = form.querySelector('[data-sort-hidden="field"]');
            const directionInput = form.querySelector('[data-sort-hidden="direction"]');

            if (fieldInput) {
                fieldInput.value = sortTrigger.dataset.sortField || '';
            }

            if (directionInput) {
                directionInput.value = sortTrigger.dataset.sortDirection || 'asc';
            }

            window.submitAsyncTableForm(form, {
                resetPage: true,
                toastMessage: '',
            });

            return;
        }

        const asyncLink = event.target.closest('a[data-async-link="true"], a.quick-status-filter');
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

        if (asyncLink.matches('[data-async-clear="true"]')) {
            cancelPendingAsyncTableWork(form);
            clearAsyncTableFormUi(form, root);
        }

        window.submitAsyncTableForm(form, {
            url: new URL(href, window.location.origin),
            resetPage: false,
        });
    });

    document.addEventListener('searchable-select-updated', (event) => {
        const form = findAsyncTableForm(event.target);

        if (!form) {
            return;
        }

        if (event.detail?.submit === false) {
            return;
        }

        if (searchableSelectSubmitTimers.has(form)) {
            window.clearTimeout(searchableSelectSubmitTimers.get(form));
        }

        const timerId = window.setTimeout(() => {
            window.submitAsyncTableForm(form, { resetPage: true });
            searchableSelectSubmitTimers.delete(form);
        }, 180);

        searchableSelectSubmitTimers.set(form, timerId);
    });
};

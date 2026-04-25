const asyncTableControllers = new Map();
const asyncTableDebounceTimers = new WeakMap();
const searchableSelectSubmitTimers = new WeakMap();
const TABLE_SEARCH_ROW_SELECTOR = '[data-table-search-value], [data-table-code]';
let tableSearchNavigatorState = null;
let tableSearchNavigatorElement = null;

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

const getNamedFormControls = (form, name) => {
    return Array.from(form?.elements || []).filter((element) => element?.name === name);
};

const isManualSearchField = (element) => {
    return element?.matches?.('[data-async-manual="true"], [data-async-code-search="true"], [data-table-manual-search="true"]') ?? false;
};

const shouldSkipSerializedField = (form, key, { resetPage = true, includeManual = false } = {}) => {
    if (key === 'page' && resetPage) {
        return true;
    }

    if (includeManual) {
        return false;
    }

    return getNamedFormControls(form, key).some((element) => isManualSearchField(element));
};

const appendSerializedFormParams = (form, targetUrl, { resetPage = true, includeManual = false } = {}) => {
    const formData = new window.FormData(form);

    for (const [key, value] of formData.entries()) {
        if (shouldSkipSerializedField(form, key, { resetPage, includeManual })) {
            continue;
        }

        if (typeof value === 'string' && value.trim() === '') {
            continue;
        }

        targetUrl.searchParams.append(key, value);
    }
};

const buildAsyncTableUrl = (form, { resetPage = true, includeManual = false } = {}) => {
    const action = form.getAttribute('action') || window.location.href;
    const url = new URL(action, window.location.origin);

    url.search = '';
    appendSerializedFormParams(form, url, { resetPage, includeManual });

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

    form.querySelectorAll('.quick-filter-groups').forEach((element) => {
        const data = getAlpineComponentData(element);
        if (!data) {
            return;
        }

        if (Array.isArray(data.selected)) {
            data.selected = [];
        }

        if (typeof data.incoming === 'boolean') {
            data.incoming = false;
        }
    });

    root?.querySelectorAll('.filter-summary, .active-filters').forEach((element) => {
        element.style.display = 'none';
    });

    form.dataset.asyncClearing = 'true';
};

const normalizeTableSearchValue = (value) => String(value ?? '').trim().toLocaleLowerCase();

const getTableSearchRows = (root) => Array.from(root?.querySelectorAll(TABLE_SEARCH_ROW_SELECTOR) ?? []);

const getAsyncTableFormByRootSelector = (rootSelector) => {
    return Array.from(document.querySelectorAll('[data-async-table-form]'))
        .find((form) => form.dataset?.asyncRoot === rootSelector) || null;
};

const TABLE_SEARCH_HIT_DURATION = 1000;
const TABLE_SEARCH_LINGER_DURATION = 10000;

const usesOutlineTableSearchHighlight = (row) => {
    if (!row) {
        return false;
    }

    const configuredStyle = (row.dataset?.tableSearchHighlightStyle || '').trim().toLowerCase();

    if (configuredStyle === 'outline') {
        return true;
    }

    if (configuredStyle === 'background') {
        return false;
    }

    return false;
};

const clearTableSearchHighlights = (root) => {
    root?.querySelectorAll('.table-search-hit, .table-search-linger, .table-search-match, .table-search-active').forEach((row) => {
        row.classList.remove('table-search-hit', 'table-search-linger', 'table-search-match', 'table-search-active');
        row.classList.remove('table-search-outline');
        delete row.dataset.tableSearchHighlightToken;
    });
};

const applyNavigatorHighlights = (root) => {
    if (!root || !tableSearchNavigatorState?.matches?.length) {
        return;
    }

    clearTableSearchHighlights(root);

    const { matches, currentIndex, term, mode } = tableSearchNavigatorState;
    const currentPage = getCurrentAsyncPage();

    matches.forEach((match, index) => {
        if (Number(match.page) !== currentPage) {
            return;
        }

        const row =
            findTableRowById(root, match.highlightId) ||
            findMatchingTableRows(root, term, mode)[match.rowIndex] ||
            null;

        if (row) {
            row.classList.toggle('table-search-outline', usesOutlineTableSearchHighlight(row));
            row.classList.add(index === currentIndex ? 'table-search-active' : 'table-search-match');
        }
    });
};

const highlightTableRow = (row) => {
    const highlightToken = `${Date.now()}-${Math.random().toString(36).slice(2)}`;

    row.dataset.tableSearchHighlightToken = highlightToken;
    row.classList.remove('table-search-hit', 'table-search-linger');
    row.classList.toggle('table-search-outline', usesOutlineTableSearchHighlight(row));
    void row.offsetWidth;
    row.classList.add('table-search-hit');

    window.setTimeout(() => {
        if (row.dataset.tableSearchHighlightToken !== highlightToken) {
            return;
        }

        row.classList.remove('table-search-hit');
        row.classList.add('table-search-linger');
    }, TABLE_SEARCH_HIT_DURATION);

    window.setTimeout(() => {
        if (row.dataset.tableSearchHighlightToken !== highlightToken) {
            return;
        }

        row.classList.remove('table-search-linger');
        row.classList.remove('table-search-outline');
        delete row.dataset.tableSearchHighlightToken;
    }, TABLE_SEARCH_HIT_DURATION + TABLE_SEARCH_LINGER_DURATION);
};

const getManualSearchInput = (form) => {
    return form?.querySelector('[data-async-code-search="true"], [data-table-manual-search="true"]');
};

const readManualSearchState = (form) => {
    const input = getManualSearchInput(form);

    if (!input) {
        return null;
    }

    return {
        name: input.name,
        value: input.value,
    };
};

const restoreManualSearchState = (rootSelector, state) => {
    if (!state?.name) {
        return;
    }

    const nextForm = document.querySelector(`${rootSelector} [data-async-table-form]`);
    const nextInput = nextForm?.querySelector(`[name="${window.CSS?.escape ? window.CSS.escape(state.name) : state.name}"]`);

    if (nextInput && isManualSearchField(nextInput)) {
        nextInput.value = state.value || '';
    }
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

const getRowSearchLabel = (row) => {
    const primaryCell = row?.querySelector?.('td');
    const strongValue = row?.querySelector?.('.app-table-cell-strong, .device-table-cell-primary, .dash-table-primary');
    const labelSource = strongValue?.textContent || primaryCell?.textContent || getRowSearchValue(row) || '';

    return String(labelSource).replace(/\s+/g, ' ').trim();
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

const findMatchingTableRows = (root, term, mode = 'contains') => {
    return getTableSearchRows(root).filter((row) => rowMatchesSearch(row, term, mode));
};

const findMatchingTableRow = (root, term, mode = 'contains') => {
    return findMatchingTableRows(root, term, mode)[0] || null;
};

const getCurrentAsyncPage = () => {
    const currentUrl = new URL(window.location.href);
    const currentPage = Number.parseInt(currentUrl.searchParams.get('page') || '1', 10);

    return Number.isFinite(currentPage) && currentPage > 0 ? currentPage : 1;
};

const supportsPaginatedManualSearch = (form) => {
    const setting = String(form?.dataset?.manualSearchPagination || '').trim().toLowerCase();

    return setting !== 'false';
};

const buildAsyncTablePageUrl = (form, page) => {
    const targetUrl = buildAsyncTableUrl(form, { resetPage: false });
    targetUrl.searchParams.set('page', String(page));

    return targetUrl;
};

const searchAcrossPaginatedMatches = async (form, rootSelector, rawTerm, mode = 'contains') => {
    const currentPage = getCurrentAsyncPage();
    const baseUrl = buildAsyncTableUrl(form, { resetPage: false });
    const maxProbePages = 50;
    const matches = [];
    const pageHtmlByPage = new Map();
    const currentRoot = document.querySelector(rootSelector);

    findMatchingTableRows(currentRoot, rawTerm, mode).forEach((row, index) => {
        matches.push({
            page: currentPage,
            rowIndex: index,
            highlightId: getRowSearchId(row),
            label: getRowSearchLabel(row),
        });
    });

    for (let page = 1; page <= maxProbePages; page += 1) {
        if (page === currentPage) {
            continue;
        }

        const targetUrl = new URL(baseUrl.toString());
        targetUrl.searchParams.set('page', String(page));

        const response = await fetch(targetUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            continue;
        }

        const html = await response.text();
        const parser = new DOMParser();
        const nextDocument = parser.parseFromString(html, 'text/html');
        const nextRoot = nextDocument.querySelector(rootSelector);

        if (!nextRoot) {
            continue;
        }

        const rowCount = nextRoot.querySelectorAll('[data-table-row-id], [data-table-code], [data-table-search-value]').length;
        if (rowCount === 0) {
            break;
        }

        const pageMatches = findMatchingTableRows(nextRoot, rawTerm, mode);
        if (pageMatches.length === 0) {
            continue;
        }

        pageHtmlByPage.set(page, html);

        pageMatches.forEach((row, index) => {
            matches.push({
                page,
                rowIndex: index,
                highlightId: getRowSearchId(row),
                label: getRowSearchLabel(row),
            });
        });
    }

    return {
        matches,
        pageHtmlByPage,
    };
};

const searchAcrossPaginatedHtml = async (form, rootSelector, rawTerm, mode = 'contains') => {
    const currentPage = getCurrentAsyncPage();
    const baseUrl = buildAsyncTableUrl(form, { resetPage: false });
    const pagesToTry = [];
    const maxProbePages = 50;

    for (let page = 1; page <= maxProbePages; page += 1) {
        if (page !== currentPage) {
            pagesToTry.push(page);
        }
    }

    for (const page of pagesToTry) {
        const targetUrl = new URL(baseUrl.toString());
        targetUrl.searchParams.set('page', String(page));

        const response = await fetch(targetUrl.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            continue;
        }

        const html = await response.text();
        const parser = new DOMParser();
        const nextDocument = parser.parseFromString(html, 'text/html');
        const nextRoot = nextDocument.querySelector(rootSelector);

        if (!nextRoot) {
            continue;
        }

        const rowCount = nextRoot.querySelectorAll('[data-table-row-id], [data-table-code], [data-table-search-value]').length;
        if (rowCount === 0) {
            break;
        }

        const match = findMatchingTableRow(nextRoot, rawTerm, mode);
        if (!match) {
            continue;
        }

        return {
            page,
            html,
            highlightId: getRowSearchId(match),
        };
    }

    return null;
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
    appendSerializedFormParams(form, targetUrl, { resetPage: false, includeManual: false });

    if (supportsPaginatedManualSearch(form)) {
        targetUrl.searchParams.set('page', String(page));
    } else {
        targetUrl.searchParams.delete('page');
    }

    targetUrl.searchParams.set('highlight', rawTerm);
    targetUrl.searchParams.set('highlight_mode', mode);
    if (highlightId) {
        targetUrl.searchParams.set('highlight_id', String(highlightId));
    } else {
        targetUrl.searchParams.delete('highlight_id');
    }

    return targetUrl;
};

const ensureTableSearchNavigatorElement = () => {
    if (tableSearchNavigatorElement?.isConnected) {
        return tableSearchNavigatorElement;
    }

    tableSearchNavigatorElement = document.createElement('div');
    tableSearchNavigatorElement.className = 'table-search-navigator';
    document.body.appendChild(tableSearchNavigatorElement);

    return tableSearchNavigatorElement;
};

const renderTableSearchNavigator = () => {
    const navigator = ensureTableSearchNavigatorElement();
    const state = tableSearchNavigatorState;

    if (!state || !Array.isArray(state.matches) || state.matches.length <= 1) {
        navigator.setAttribute('hidden', 'hidden');
        navigator.innerHTML = '';
        return;
    }

    const currentMatch = state.matches[state.currentIndex] ?? null;
    const currentLabel = currentMatch?.label || 'Atlasītais ieraksts';
    const escapedTerm = String(state.term || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const escapedLabel = String(currentLabel).replace(/</g, '&lt;').replace(/>/g, '&gt;');

    navigator.removeAttribute('hidden');
    navigator.innerHTML = `
        <div class="table-search-navigator-card">
            <div class="table-search-navigator-header">
                <div>
                    <div class="table-search-navigator-title">Meklēšanas rezultāti</div>
                    <div class="table-search-navigator-subtitle">"${escapedTerm}" atrasti ${state.matches.length} ieraksti</div>
                </div>
                <button type="button" class="table-search-navigator-close" data-table-search-nav-close="true" aria-label="Aizvērt meklēšanas rezultātus">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="table-search-navigator-body">
                <div class="table-search-navigator-current">
                    <span class="table-search-navigator-current-index">${state.currentIndex + 1} / ${state.matches.length}</span>
                    <span class="table-search-navigator-current-label">${escapedLabel}</span>
                </div>
                <div class="table-search-navigator-controls">
                    <button type="button" class="table-search-navigator-button" data-table-search-nav="prev">
                        <span aria-hidden="true">←</span>
                        <span>Iepriekšējais</span>
                    </button>
                    <button type="button" class="table-search-navigator-button table-search-navigator-button-primary" data-table-search-nav="next">
                        <span>Nākamais</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </div>
            </div>
        </div>
    `;
};

const clearTableSearchNavigator = ({ rootSelector = null } = {}) => {
    if (!tableSearchNavigatorState) {
        renderTableSearchNavigator();
        return;
    }

    if (rootSelector && tableSearchNavigatorState.rootSelector !== rootSelector) {
        return;
    }

    const previousRootSelector = tableSearchNavigatorState.rootSelector;
    tableSearchNavigatorState = null;

    if (previousRootSelector) {
        clearTableSearchHighlights(document.querySelector(previousRootSelector));
    }

    renderTableSearchNavigator();
};

const setTableSearchNavigatorState = (nextState) => {
    tableSearchNavigatorState = nextState;
    renderTableSearchNavigator();
};

const normalizeSearchNavigatorIndex = (index, total) => {
    if (total <= 0) {
        return 0;
    }

    return ((index % total) + total) % total;
};

const scrollToAndHighlightTableRow = (row) => {
    if (!row) {
        return;
    }

    highlightTableRow(row);
    row.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
        inline: 'nearest',
    });
};

const moveTableSearchNavigator = async (step) => {
    if (!tableSearchNavigatorState?.matches?.length) {
        return false;
    }

    const nextIndex = normalizeSearchNavigatorIndex(
        tableSearchNavigatorState.currentIndex + step,
        tableSearchNavigatorState.matches.length
    );
    const targetMatch = tableSearchNavigatorState.matches[nextIndex];

    if (!targetMatch) {
        return false;
    }

    const form = getAsyncTableFormByRootSelector(tableSearchNavigatorState.rootSelector);
    const root = document.querySelector(tableSearchNavigatorState.rootSelector);
    const currentPage = getCurrentAsyncPage();

    if (Number(targetMatch.page) === currentPage) {
        const row = findTableRowById(root, targetMatch.highlightId)
            || findMatchingTableRows(root, tableSearchNavigatorState.term, tableSearchNavigatorState.mode)[targetMatch.rowIndex]
            || null;

        tableSearchNavigatorState.currentIndex = nextIndex;
        applyNavigatorHighlights(root);

        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }

        renderTableSearchNavigator();
        return true;
    }

    if (!form) {
        return false;
    }

    const swapped = await window.submitAsyncTableForm(form, {
        url: buildAsyncTablePageUrl(form, targetMatch.page),
        resetPage: false,
        preserveSearchNavigator: true,
        manualSearchState: {
            name: getManualSearchInput(form)?.name || 'code',
            value: tableSearchNavigatorState.term,
        },
    });

    if (!swapped) {
        return false;
    }

    const nextRoot = document.querySelector(tableSearchNavigatorState.rootSelector);
    const row = findTableRowById(nextRoot, targetMatch.highlightId)
        || findMatchingTableRows(nextRoot, tableSearchNavigatorState.term, tableSearchNavigatorState.mode)[targetMatch.rowIndex]
        || null;

    tableSearchNavigatorState.currentIndex = nextIndex;
    applyNavigatorHighlights(nextRoot);

    if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
    }

    renderTableSearchNavigator();

    return true;
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
    const manualSearchState = {
        name: searchInput.name,
        value: rawTerm,
    };

    if (!normalizedTerm) {
        clearTableSearchNavigator({ rootSelector });
        window.dispatchAppToast({
            title: 'Meklēšana',
            message: 'Ievadi meklējamo vērtību, lai atrastu konkrēto ierakstu.',
            tone: 'info',
        });
        searchInput.focus();

        return true;
    }

    clearTableSearchHighlights(root);
    clearTableSearchNavigator({ rootSelector });

    const paginatedResults = supportsPaginatedManualSearch(form)
        ? await searchAcrossPaginatedMatches(form, rootSelector, rawTerm, searchMode)
        : {
            matches: findMatchingTableRows(root, rawTerm, searchMode).map((row, index) => ({
                page: getCurrentAsyncPage(),
                rowIndex: index,
                highlightId: getRowSearchId(row),
                label: getRowSearchLabel(row),
            })),
            pageHtmlByPage: new Map(),
        };
    if (paginatedResults.matches.length > 0) {
        const firstMatch = paginatedResults.matches[0];

        setTableSearchNavigatorState({
            term: rawTerm,
            mode: searchMode,
            rootSelector,
            matches: paginatedResults.matches,
            currentIndex: 0,
        });

        if (Number(firstMatch.page) !== getCurrentAsyncPage()) {
            const cachedHtml = paginatedResults.pageHtmlByPage.get(firstMatch.page);

            if (cachedHtml) {
                const swapped = swapAsyncTableRoot(rootSelector, cachedHtml);

                if (swapped) {
                    const targetUrl = buildAsyncTablePageUrl(form, firstMatch.page);
                    window.history.replaceState({}, '', `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`);
                }
            } else {
                await window.submitAsyncTableForm(form, {
                    url: buildAsyncTablePageUrl(form, firstMatch.page),
                    resetPage: false,
                    preserveSearchNavigator: true,
                    manualSearchState,
                });
            }
        }

        const activeRoot = document.querySelector(rootSelector);
        const activeRow = findTableRowById(activeRoot, firstMatch.highlightId)
            || findMatchingTableRows(activeRoot, rawTerm, searchMode)[firstMatch.rowIndex]
            || null;

        if (paginatedResults.matches.length > 1) {
            applyNavigatorHighlights(activeRoot);
            if (activeRow) {
                activeRow.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
            }
        } else if (activeRow) {
            scrollToAndHighlightTableRow(activeRow);
        }

        renderTableSearchNavigator();
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
        appendSerializedFormParams(form, endpointUrl, { resetPage: false, includeManual: false });
        endpointUrl.searchParams.set(searchInput.name, rawTerm);

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
        if (!result?.found && supportsPaginatedManualSearch(form)) {
            const paginatedMatch = await searchAcrossPaginatedHtml(form, rootSelector, rawTerm, searchMode);

            if (paginatedMatch?.html) {
                const swapped = swapAsyncTableRoot(rootSelector, paginatedMatch.html);

                if (swapped) {
                    const targetUrl = buildSearchNavigationUrl(
                        form,
                        paginatedMatch.page,
                        rawTerm,
                        searchMode,
                        paginatedMatch.highlightId || ''
                    );

                    window.history.replaceState({}, '', `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`);
                    await restoreHighlightedSearchFromUrl();
                    return true;
                }
            }
        }
        if (!result?.found) {
            window.dispatchAppToast({
                title: 'Ieraksts netika atrasts',
                message: supportsPaginatedManualSearch(form)
                    ? `Ieraksts "${rawTerm}" netika atrasts nevienā lapā.`
                    : `Ieraksts "${rawTerm}" netika atrasts pašreizējā remonta sarakstā.`,
                tone: 'info',
            });

            return true;
        }

        const targetUrl = buildSearchNavigationUrl(form, result.page, rawTerm, searchMode, result.highlight_id ?? '');
        const swapped = await window.submitAsyncTableForm(form, {
            url: targetUrl,
            resetPage: false,
            manualSearchState,
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
    const form = document.querySelector('[data-async-table-form][data-search-endpoint]');
    const manualSearchInput = form ? getManualSearchInput(form) : null;

    if (term && manualSearchInput) {
        manualSearchInput.value = term;
    }

    if (!term && !highlightId) {
        return;
    }

    let match = findTableRowById(document, highlightId) || findMatchingTableRow(document, term, mode);
    if (!match) {
        if (form?.dataset?.searchEndpoint) {
            try {
                const endpointUrl = new URL(form.dataset.searchEndpoint, window.location.origin);
                appendSerializedFormParams(form, endpointUrl, { resetPage: false, includeManual: false });
                endpointUrl.searchParams.set(manualSearchInput?.name || 'code', term);

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

    window.submitAsyncTableForm = async (form, { url = null, resetPage = true, toastMessage = '', preserveSearchNavigator = false, manualSearchState = null } = {}) => {
        const rootSelector = form?.dataset?.asyncRoot;

        if (!form || !rootSelector) {
            return false;
        }

        if (!preserveSearchNavigator) {
            clearTableSearchNavigator({ rootSelector });
        }

        const preservedManualSearchState = manualSearchState ?? readManualSearchState(form);
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

            restoreManualSearchState(rootSelector, preservedManualSearchState);

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
            clearTableSearchNavigator({ rootSelector: form.dataset?.asyncRoot });
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
            if (event.defaultPrevented) {
                return;
            }

            const form = findAsyncTableForm(manualSearchTrigger);

            if (form && getManualSearchInput(form)) {
                event.preventDefault();
                performManualTableSearch(form);
                return;
            }
        }

        const navigatorAction = event.target.closest('[data-table-search-nav], [data-table-search-nav-close]');
        if (navigatorAction) {
            event.preventDefault();

            if (navigatorAction.matches('[data-table-search-nav-close]')) {
                clearTableSearchNavigator();
                return;
            }

            const direction = navigatorAction.getAttribute('data-table-search-nav') === 'prev' ? -1 : 1;
            moveTableSearchNavigator(direction);
            return;
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

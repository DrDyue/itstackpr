import { appendInputValueParam } from './queryParams';

const SORT_FIELD_SELECTOR = 'input[data-sort-hidden="field"]';
const SORT_DIRECTION_SELECTOR = 'input[data-sort-hidden="direction"]';

export const isAsyncTableFilterForm = (form) => {
    return Boolean(form && form.closest('[data-async-table-root]'));
};

export const buildClearFiltersUrl = (form) => {
    const url = new URL(form.action, window.location.origin);
    const sortField = form.querySelector(SORT_FIELD_SELECTOR);
    const sortDirection = form.querySelector(SORT_DIRECTION_SELECTOR);
    const params = new URLSearchParams();

    params.append('statuses_filter', '1');
    appendInputValueParam(params, 'sort', sortField);
    appendInputValueParam(params, 'direction', sortDirection);
    url.search = `?${params.toString()}`;

    return url;
};

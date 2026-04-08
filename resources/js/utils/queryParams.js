export const appendInputValueParam = (params, key, input) => {
    if (!input || !key) {
        return;
    }

    const value = String(input.value ?? '').trim();
    if (value === '') {
        return;
    }

    params.append(key, value);
};

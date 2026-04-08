export const readStorageValue = (key, fallback = null) => {
    try {
        return window.localStorage.getItem(key) ?? fallback;
    } catch (error) {
        return fallback;
    }
};

export const writeStorageValue = (key, value) => {
    try {
        window.localStorage.setItem(key, value);
        return true;
    } catch (error) {
        return false;
    }
};

const THEME_STORAGE_KEY = 'itstack-theme';

const getStoredTheme = (readStorageValue) => {
    return readStorageValue(THEME_STORAGE_KEY, 'light') === 'dark' ? 'dark' : 'light';
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

export const initializeThemeToggle = ({ readStorageValue, writeStorageValue }) => {
    applyTheme(getStoredTheme(readStorageValue));
    syncThemeToggleUi();

    document.querySelectorAll('[data-theme-choice]').forEach((button) => {
        if (button.dataset.themeBound === '1') {
            return;
        }

        button.dataset.themeBound = '1';
        button.addEventListener('click', () => {
            const nextTheme = button.dataset.themeValue === 'dark' ? 'dark' : 'light';

            writeStorageValue(THEME_STORAGE_KEY, nextTheme);
            applyTheme(nextTheme);
            syncThemeToggleUi();
        });
    });
};

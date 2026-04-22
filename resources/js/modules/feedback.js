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
    root.className = 'app-toast-stack pointer-events-none fixed bottom-4 right-4 z-[70] flex w-[min(30rem,calc(100vw-1.5rem))] flex-col items-stretch gap-3 sm:bottom-6 sm:right-6';
    document.body.appendChild(root);

    return root;
};

const getToastPriority = ({ tone = 'info', title = '', message = '', priority = null } = {}) => {
    if (Number.isFinite(Number(priority))) {
        return Number(priority);
    }

    const normalizedTone = String(tone || '').toLowerCase();
    const titleText = String(title || '').toLowerCase();
    const messageText = String(message || '').toLowerCase();
    const combinedText = `${titleText} ${messageText}`;

    if (normalizedTone === 'error' || normalizedTone === 'danger') {
        return 400;
    }

    if (
        combinedText.includes('dzēšana nav pieejama')
        || combinedText.includes('nevar izdzēst')
        || combinedText.includes('nav pieejama')
    ) {
        return 350;
    }

    if (normalizedTone === 'warning') {
        return 300;
    }

    if (normalizedTone === 'success') {
        return 200;
    }

    return 100;
};

const insertToastByPriority = (root, toast) => {
    const targetPriority = Number(toast.dataset.toastPriority || 0);
    const siblings = Array.from(root.children);
    const referenceNode = siblings.find((sibling) => Number(sibling.dataset.toastPriority || 0) < targetPriority);

    if (referenceNode) {
        root.insertBefore(toast, referenceNode);
        return;
    }

    root.appendChild(toast);
};

const dismissAppToast = (toast) => {
    if (!toast || toast.dataset.closing === '1') {
        return;
    }

    if (toast.dataset.dismissTimer) {
        window.clearTimeout(Number(toast.dataset.dismissTimer));
        delete toast.dataset.dismissTimer;
    }

    toast.dataset.closing = '1';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(16px) scale(0.97)';

    window.setTimeout(() => {
        toast.remove();
    }, 240);
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

export const registerFeedbackGlobals = () => {
    window.dispatchAppToast = ({ message = '', tone = 'info', title = '', priority = null } = {}) => {
        if (!message) {
            return;
        }

        const root = ensureAppToastRoot();
        const toast = document.createElement('div');
        const normalizedTone = ['success', 'error'].includes(String(tone || '').toLowerCase())
            ? String(tone).toLowerCase()
            : 'info';
        const toastPriority = getToastPriority({ tone, title, message, priority });
        const toastKey = `${normalizedTone}::${title || ''}::${message}`;
        const existingToast = root.querySelector(`[data-toast-key="${CSS.escape(toastKey)}"]`);

        if (existingToast && existingToast.dataset.closing !== '1') {
            if (existingToast.dataset.dismissTimer) {
                window.clearTimeout(Number(existingToast.dataset.dismissTimer));
            }

            existingToast.dataset.dismissTimer = String(window.setTimeout(() => dismissAppToast(existingToast), 3800));
            existingToast.dataset.toastPriority = String(toastPriority);
            existingToast.style.opacity = '1';
            existingToast.style.transform = 'translateY(0) scale(1)';
            insertToastByPriority(root, existingToast);
            return;
        }

        toast.className = `flash-toast flash-toast-${normalizedTone} pointer-events-auto`;
        toast.dataset.toastKey = toastKey;
        toast.dataset.toastPriority = String(toastPriority);
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
        insertToastByPriority(root, toast);

        window.requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0) scale(1)';
        });

        toast.dataset.dismissTimer = String(window.setTimeout(() => dismissAppToast(toast), 3800));
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
        const dialogNode = root.querySelector('.app-confirm-dialog');
        const iconNode = root.querySelector('.app-confirm-icon');
        const cancelButtons = root.querySelectorAll('[data-app-confirm-cancel]');
        const dismissButtons = root.querySelectorAll('[data-app-confirm-cancel], [data-app-confirm-close]');

        titleNode.textContent = title;
        messageNode.textContent = message;
        acceptButton.textContent = confirmLabel;
        cancelButtons.forEach((button) => {
            button.textContent = cancelLabel;
        });

        dialogNode?.classList.remove('app-confirm-dialog-danger', 'app-confirm-dialog-warning');
        iconNode?.classList.remove('app-confirm-icon-danger', 'app-confirm-icon-warning');
        acceptButton.classList.toggle('btn-danger-solid', tone !== 'warning');
        acceptButton.classList.toggle('btn-approve', tone === 'warning');
        dialogNode?.classList.add(tone === 'warning' ? 'app-confirm-dialog-warning' : 'app-confirm-dialog-danger');
        iconNode?.classList.add(tone === 'warning' ? 'app-confirm-icon-warning' : 'app-confirm-icon-danger');

        root.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        window.requestAnimationFrame(() => root.classList.add('is-visible'));
        acceptButton.focus();

        return new Promise((resolve) => {
            const cleanup = (result) => {
                root.classList.remove('is-visible');
                document.body.classList.remove('overflow-hidden');
                window.setTimeout(() => root.classList.add('hidden'), 180);
                dialogNode?.classList.remove('app-confirm-dialog-danger', 'app-confirm-dialog-warning');
                iconNode?.classList.remove('app-confirm-icon-danger', 'app-confirm-icon-warning');

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
};

export const initializeAppConfirm = () => {
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

import './bootstrap';

const Alpine = window.Alpine;

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

registerAlpineData();
document.addEventListener('alpine:init', registerAlpineData);

if (Alpine && !window.__appAlpineStarted) {
    window.__appAlpineStarted = true;
    Alpine.start();
}

const repairTransitionRules = {
    waiting: ['in-progress', 'cancelled'],
    'in-progress': ['waiting', 'completed', 'cancelled'],
    completed: ['in-progress'],
    cancelled: ['waiting', 'in-progress'],
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

        const repairName = repair.name ?? 'so remontu';
        if (!window.confirm(`Vai tiesam gribat pabeigt ierices remontu "${repairName}"?`)) {
            return;
        }

        this.submitTransition(repair.id, 'completed');
    },
});

window.repairProcess = (config) => ({
    repairType: config.repairType,
    status: config.status,
    submitCompletion() {
        if (!window.confirm('Vai tiesam gribat pabeigt so ierices remontu?')) {
            return;
        }

        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, config.repairId, 'completed');
    },
});

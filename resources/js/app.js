import './bootstrap';

document.addEventListener('alpine:init', () => {
    if (window.__localizedDatePickerRegistered) {
        return;
    }

    window.__localizedDatePickerRegistered = true;

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

    Alpine.data('searchableSelect', ({ selected = '', query = '', options = [], placeholder = '', emptyMessage = '' } = {}) => ({
        open: false,
        selected: String(selected ?? ''),
        query: query || '',
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
        dragging: false,
        suppressClick: false,
        startY: 0,
        startScrollTop: 0,
        init() {
            if (this.selected && !this.query) {
                const current = this.options.find((option) => option.value === this.selected);
                if (current) {
                    this.query = current.label;
                }
            }
        },
        get filteredOptions() {
            const term = this.query.trim().toLowerCase();

            if (term === '') {
                return this.options;
            }

            return this.options.filter((option) => option.search.includes(term));
        },
        togglePanel() {
            this.open = !this.open;
            if (this.open) {
                this.preparePanel();
            }
        },
        openPanel() {
            if (this.open) {
                return;
            }

            this.open = true;
            this.preparePanel();
        },
        close() {
            this.open = false;
            this.stopPointer();
        },
        clearSelection() {
            this.selected = '';
            this.query = '';
            this.highlightedIndex = 0;
            this.close();
        },
        preparePanel() {
            const selectedIndex = this.filteredOptions.findIndex((option) => option.value === this.selected);
            this.highlightedIndex = selectedIndex >= 0 ? selectedIndex : 0;
            this.$nextTick(() => this.scrollToHighlighted());
        },
        handleInput() {
            this.open = true;
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
            if (this.suppressClick) {
                return;
            }

            this.selected = option.value;
            this.query = option.label;
            this.open = false;
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
            this.dragging = false;
            this.startY = event.clientY;
            this.startScrollTop = this.$refs.panel.scrollTop;
        },
        handlePointerMove(event) {
            if (!this.pointerActive || !this.$refs.panel) {
                return;
            }

            const delta = event.clientY - this.startY;

            if (Math.abs(delta) > 3) {
                this.dragging = true;
                this.suppressClick = true;
            }

            if (this.dragging) {
                this.$refs.panel.scrollTop = this.startScrollTop - delta;
            }
        },
        stopPointer() {
            if (!this.pointerActive) {
                return;
            }

            this.pointerActive = false;
            this.dragging = false;

            if (this.suppressClick) {
                window.setTimeout(() => {
                    this.suppressClick = false;
                }, 80);
            }
        },
    }));
});

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
        if (!window.confirm(`Vai tiesam gribat pabeigt remontu "${repairName}"?`)) {
            return;
        }

        this.submitTransition(repair.id, 'completed');
    },
});

window.repairProcess = (config) => ({
    repairType: config.repairType,
    status: config.status,
    submitCompletion() {
        if (!window.confirm('Vai tiesam gribat pabeigt remontu?')) {
            return;
        }

        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, config.repairId, 'completed');
    },
});

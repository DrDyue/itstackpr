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
    completeModalOpen: false,
    completionForm: {
        id: null,
        name: '',
        cost: '',
    },
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
            this.openCompletionModal(this.draggedRepair);
            this.clearDrag();
            return;
        }

        this.submitTransition(this.draggedRepair.id, targetStatus);
        this.clearDrag();
    },
    openCompletionModal(repair) {
        this.completionForm = {
            id: repair.id,
            name: repair.name ?? 'Ierice',
            cost: repair.cost ?? '',
        };
        this.completeModalOpen = true;
    },
    closeCompletionModal() {
        this.completeModalOpen = false;
    },
    submitTransition(repairId, targetStatus, extra = {}) {
        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, repairId, targetStatus, extra);
    },
    submitCompletion() {
        this.submitTransition(this.completionForm.id, 'completed', {
            cost: this.completionForm.cost,
        });
    },
});

window.repairProcess = (config) => ({
    repairType: config.repairType,
    status: config.status,
    completeModalOpen: false,
    completionForm: {
        cost: config.cost ?? '',
    },
    openCompletionModal() {
        this.completionForm.cost = config.cost ?? '';
        this.completeModalOpen = true;
    },
    closeCompletionModal() {
        this.completeModalOpen = false;
    },
    submitCompletion() {
        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, config.repairId, 'completed', {
            cost: this.completionForm.cost,
        });
    },
});

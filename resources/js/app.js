import './bootstrap';

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
        date: config.today,
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
            date: config.today,
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
            actual_completion: this.completionForm.date,
            cost: this.completionForm.cost,
        });
    },
});

window.repairProcess = (config) => ({
    repairType: config.repairType,
    status: config.status,
    completeModalOpen: false,
    completionForm: {
        date: config.actualCompletion || config.today,
        cost: config.cost ?? '',
    },
    openCompletionModal() {
        this.completionForm.date = config.actualCompletion || config.today;
        this.completionForm.cost = config.cost ?? '';
        this.completeModalOpen = true;
    },
    closeCompletionModal() {
        this.completeModalOpen = false;
    },
    submitCompletion() {
        window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, config.repairId, 'completed', {
            actual_completion: this.completionForm.date,
            cost: this.completionForm.cost,
        });
    },
});

const repairTransitionRules = {
    waiting: ['in-progress', 'cancelled'],
    'in-progress': ['waiting', 'completed', 'cancelled'],
    completed: [],
    cancelled: [],
};

const appendHiddenInput = (form, name, value) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value ?? '';
    form.appendChild(input);
};

const canRepairTransition = (fromStatus, toStatus) => {
    return (repairTransitionRules[fromStatus] ?? []).includes(toStatus);
};

export const registerRepairWorkflowGlobals = () => {
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
            if (targetStatus === 'in-progress' && this.showMissingRequirements('waiting', 'Remontu sākt')) {
                return;
            }

            if (targetStatus === 'completed' && this.showMissingRequirements('in-progress', 'Remontu pabeigt')) {
                return;
            }

            window.submitRepairTransition(config.transitionBaseUrl, config.csrfToken, repairId, targetStatus, extra);
        },
        async submitCompletion(repair) {
            if (!repair?.id) {
                return;
            }

            const repairName = repair.name ?? 'šo remontu';
            const accepted = await window.openAppConfirm({
                title: 'Pabeigt remontu?',
                message: `Vai tiešām gribat pabeigt ierīces remontu "${repairName}"?`,
                confirmLabel: 'Jā, pabeigt',
                cancelLabel: 'Nē',
                tone: 'warning',
            });

            if (!accepted) {
                return;
            }

            this.submitTransition(repair.id, 'completed');
        },
    });

    window.repairProcess = (config) => ({
        repairType: config.repairType,
        repairStatus: config.status,
        priority: config.priority ?? 'medium',
        description: config.description ?? '',
        vendorName: config.vendorName ?? '',
        vendorContact: config.vendorContact ?? '',
        invoiceNumber: config.invoiceNumber ?? '',
        cost: config.cost ?? '',
        isExternal() {
            return this.repairType === 'external';
        },
        normalizedCost() {
            return String(this.cost ?? '').trim();
        },
        transitionFormPayload() {
            return {
                description: this.description ?? '',
                repair_type: this.repairType ?? 'internal',
                priority: this.priority ?? 'medium',
                cost: this.normalizedCost(),
                vendor_name: this.vendorName ?? '',
                vendor_contact: this.vendorContact ?? '',
                invoice_number: this.invoiceNumber ?? '',
            };
        },
        requirementRows(targetStatus = this.repairStatus) {
            if (targetStatus !== 'completed' && this.repairStatus !== 'in-progress') {
                return [];
            }

            const rows = [
                { key: 'description', label: 'Apraksts', done: String(this.description ?? '').trim() !== '' },
            ];

            if (this.isExternal()) {
                rows.push(
                    { key: 'vendor_name', label: 'Pakalpojuma sniedzējs', done: String(this.vendorName ?? '').trim() !== '' },
                    { key: 'vendor_contact', label: 'Vendora kontakts', done: String(this.vendorContact ?? '').trim() !== '' },
                    { key: 'invoice_number', label: 'Rēķina numurs', done: String(this.invoiceNumber ?? '').trim() !== '' },
                );
            }

            return rows;
        },
        advisoryRows() {
            const rows = [];

            if (this.repairStatus === 'in-progress' && this.isExternal() && this.normalizedCost() === '') {
                rows.push({
                    key: 'external-cost',
                    label: 'Ja ārējā remonta summa ir zināma, pievieno izmaksas, lai vēsture būtu pilnīga.',
                });
            }

            return rows;
        },
        nextStepReady() {
            const rows = this.requirementRows();
            return rows.length > 0 && rows.every((item) => item.done);
        },
        nextStepIncompleteCount() {
            return this.requirementRows().filter((item) => !item.done).length;
        },
        completionTooltip() {
            const missing = this.missingRequirementLabels('completed');
            if (missing.length === 0) {
                return 'Visi obligātie dati ir aizpildīti. Remontu var pabeigt.';
            }

            return `Lai pabeigtu remontu, vēl jāaizpilda: ${missing.join(', ')}.`;
        },
        nextStepTitle() {
            if (this.repairStatus === 'waiting') {
                return 'Lai sāktu remontu, pārbaudi ierīci un saglabā korektu aprakstu, tipu un prioritāti.';
            }

            if (this.repairStatus === 'in-progress') {
                return this.isExternal()
                    ? 'Pirms pabeigšanas aizpildi aprakstu un ārējā remonta datus.'
                    : 'Pirms pabeigšanas pārliecinies, ka apraksts precīzi atspoguļo veikto darbu.';
            }

            if (this.repairStatus === 'completed') {
                return 'Remonts ir pabeigts. Ja nepieciešams, to var atgriezt procesā.';
            }

            return 'Remonts ir atcelts. Aktīvas nākamās darbības vairs nav pieejamas.';
        },
        advisoryTitle() {
            return 'Papildu informācija';
        },
        missingRequirementLabels(targetStatus) {
            return this.requirementRows(targetStatus)
                .filter((item) => !item.done)
                .map((item) => item.label);
        },
        showMissingRequirements(targetStatus, actionLabel) {
            const missing = this.missingRequirementLabels(targetStatus);
            if (missing.length === 0) {
                return false;
            }

            window.dispatchAppToast({
                title: 'Darbību nevar izpildīt',
                message: `${actionLabel} nevar, jo vēl trūkst: ${missing.join(', ')}.`,
                tone: 'info',
            });

            return true;
        },
        async submitTransition(repairId, targetStatus, extra = {}) {
            if (targetStatus === 'completed' && this.showMissingRequirements('completed', 'Remontu pabeigt')) {
                return;
            }

            const actionLabels = {
                waiting: 'atgriezt remontu gaidīšanā',
                'in-progress': 'sākt remontu',
                completed: 'pabeigt remontu',
                cancelled: 'atcelt remontu',
            };

            const accepted = await window.openAppConfirm({
                title: 'Apstiprini statusa maiņu',
                message: `Vai tiešām vēlaties ${actionLabels[targetStatus] ?? 'mainīt remonta statusu'}?`,
                confirmLabel: 'Jā',
                cancelLabel: 'Nē',
                tone: targetStatus === 'cancelled' ? 'danger' : 'warning',
            });

            if (!accepted) {
                return;
            }

            window.submitRepairTransition(
                config.transitionBaseUrl,
                config.csrfToken,
                repairId,
                targetStatus,
                {
                    ...this.transitionFormPayload(),
                    ...extra,
                },
            );
        },
        async submitCompletion() {
            if (this.showMissingRequirements('completed', 'Remontu pabeigt')) {
                return;
            }

            const accepted = await window.openAppConfirm({
                title: 'Pabeigt remontu?',
                message: 'Vai tiešām gribat pabeigt šo ierīces remontu?',
                confirmLabel: 'Jā, pabeigt',
                cancelLabel: 'Nē',
                tone: 'warning',
            });

            if (!accepted) {
                return;
            }

            window.submitRepairTransition(
                config.transitionBaseUrl,
                config.csrfToken,
                config.repairId,
                'completed',
                this.transitionFormPayload(),
            );
        },
    });
};

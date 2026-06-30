import './bootstrap';
import '../css/modal.css';

const mobileSidebar = document.querySelector('#mobile-account-navigation');
const mobileSidebarOpeners = document.querySelectorAll('[data-mobile-sidebar-open]');
const mobileSidebarCloser = document.querySelector('[data-mobile-sidebar-close]');

if (mobileSidebar) {
    let lastOpener = null;

    mobileSidebarOpeners.forEach((opener) => {
        opener.addEventListener('click', () => {
            lastOpener = opener;
            mobileSidebar.showModal();
            mobileSidebarCloser?.focus();
        });
    });

    mobileSidebarCloser?.addEventListener('click', () => mobileSidebar.close());

    mobileSidebar.addEventListener('click', (event) => {
        if (event.target === mobileSidebar) {
            mobileSidebar.close();
        }
    });

    mobileSidebar.addEventListener('close', () => lastOpener?.focus());
}

const modalReturnFocus = new WeakMap();

const focusModalContent = (modal) => {
    const target = modal.querySelector(
        '[aria-invalid="true"], [autofocus], input:not([type="hidden"]), select, textarea, button:not([data-modal-close])',
    );

    requestAnimationFrame(() => target?.focus());
};

const openModal = (modal, trigger = null) => {
    if (!modal || typeof modal.showModal !== 'function') {
        return false;
    }

    if (trigger) {
        modalReturnFocus.set(modal, trigger);
    }

    if (!modal.open) {
        modal.showModal();
    }

    focusModalContent(modal);

    return true;
};

const setFormValue = (form, name, value) => {
    const field = form.elements.namedItem(name);

    if (!field) {
        return;
    }

    field.value = value ?? '';
};

document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        const modal = document.getElementById(trigger.dataset.modalOpen);

        if (!modal) {
            return;
        }

        const form = modal.querySelector('[data-record-form]');

        if (form && trigger.dataset.modalRecord) {
            form.reset();

            try {
                const record = JSON.parse(trigger.dataset.modalRecord);
                Object.entries(record).forEach(([name, value]) => setFormValue(form, name, value));

                form.querySelectorAll('option[data-linked-record]').forEach((option) => {
                    const linkedRecord = option.dataset.linkedRecord;
                    option.disabled = Boolean(linkedRecord) && String(linkedRecord) !== String(record.__record_id);
                });
            } catch {
                return;
            }

            if (trigger.dataset.modalAction) {
                form.action = trigger.dataset.modalAction;
                setFormValue(form, '_record_action', trigger.dataset.modalAction);
            }
        }

        if (trigger.dataset.modalTemplate) {
            const template = document.getElementById(trigger.dataset.modalTemplate);
            const content = modal.querySelector('[data-modal-detail-content]');

            if (template && content) {
                content.replaceChildren(template.content.cloneNode(true));
            }
        }

        event.preventDefault();
        openModal(modal, trigger);
    });
});

document.querySelectorAll('[data-confirm-modal]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const modal = document.getElementById(trigger.dataset.confirmModal);
        const form = modal?.querySelector('[data-confirmation-form]');

        if (!modal || !form) {
            return;
        }

        form.action = trigger.dataset.confirmAction;
        modal.querySelector('[data-confirmation-heading]').textContent = trigger.dataset.confirmHeading;
        modal.querySelector('[data-confirmation-message]').textContent = trigger.dataset.confirmMessage;
        modal.querySelector('[data-confirmation-submit]').textContent = trigger.dataset.confirmLabel;
        openModal(modal, trigger);
    });
});

document.querySelectorAll('[data-modal]').forEach((modal) => {
    modal.querySelectorAll('[data-modal-close]').forEach((closer) => {
        closer.addEventListener('click', () => modal.close());
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.close();
        }
    });

    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.open) {
            event.preventDefault();
            modal.close();
        }
    });

    modal.addEventListener('close', () => modalReturnFocus.get(modal)?.focus());

    if (modal.hasAttribute('data-modal-open-on-load')) {
        openModal(modal);
    }
});

const setupCustomerTypeChooser = (form) => {
    const options = [...form.querySelectorAll('[data-customer-type-option]')];
    const panels = [...form.querySelectorAll('[data-customer-type-panel]')];
    const conditionalRequiredFields = [...form.querySelectorAll('[data-customer-type-required]')];

    if (options.length === 0) {
        return;
    }

    const update = () => {
        const selected = options.find((option) => option.checked)?.value ?? 'guest';

        options.forEach((option) => {
            const label = option.closest('label');
            const isSelected = option.value === selected;

            label?.classList.toggle('border-sage-400', isSelected);
            label?.classList.toggle('ring-2', isSelected);
            label?.classList.toggle('ring-sage-100', isSelected);
            label?.classList.toggle('border-cream-300', !isSelected);
        });

        panels.forEach((panel) => {
            const isActive = panel.dataset.customerTypePanel === selected;
            panel.classList.toggle('hidden', !isActive);

            panel.querySelectorAll('input, select, textarea').forEach((field) => {
                field.disabled = !isActive;
            });
        });

        conditionalRequiredFields.forEach((field) => {
            field.required = field.dataset.customerTypeRequired === selected;
        });
    };

    options.forEach((option) => option.addEventListener('change', update));
    update();
};

document.querySelectorAll('[data-appointment-booking-form]').forEach(setupCustomerTypeChooser);

const bookingForm = document.querySelector('[data-appointment-booking-form]');

if (bookingForm) {
    const serviceField = bookingForm.elements.namedItem('service_id');
    const therapistField = bookingForm.elements.namedItem('therapist_profile_id');
    const dateField = bookingForm.elements.namedItem('appointment_date');
    const selectedSlotField = bookingForm.querySelector('[data-selected-slot]');
    const slotStatus = bookingForm.querySelector('[data-slot-status]');
    const slotOptions = bookingForm.querySelector('[data-slot-options]');
    const slotEmpty = bookingForm.querySelector('[data-slot-empty]');
    const submitButton = bookingForm.querySelector('[data-booking-submit]');
    let requestController = null;

    const formatTime = (time) => {
        const [hours, minutes] = time.split(':').map(Number);

        return new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(2000, 0, 1, hours, minutes));
    };

    const addMinutes = (time, minutesToAdd) => {
        const [hours, minutes] = time.split(':').map(Number);
        const date = new Date(2000, 0, 1, hours, minutes + minutesToAdd);

        return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
    };

    const clearSelection = () => {
        selectedSlotField.value = '';
        submitButton.disabled = true;
        slotOptions.replaceChildren();
        slotOptions.classList.add('hidden');
        slotEmpty.classList.add('hidden');
    };

    const selectSlot = (button, value) => {
        slotOptions.querySelectorAll('button').forEach((slotButton) => {
            slotButton.setAttribute('aria-pressed', 'false');
            slotButton.classList.remove('border-sage-600', 'bg-sage-100', 'text-sage-900');
            slotButton.classList.add('border-cream-300', 'bg-white', 'text-cocoa-800');
        });

        button.setAttribute('aria-pressed', 'true');
        button.classList.remove('border-cream-300', 'bg-white', 'text-cocoa-800');
        button.classList.add('border-sage-600', 'bg-sage-100', 'text-sage-900');
        selectedSlotField.value = value;
        submitButton.disabled = false;
    };

    const renderSlots = (slots, previousValue) => {
        slotStatus.classList.add('hidden');

        if (slots.length === 0) {
            slotEmpty.classList.remove('hidden');

            return;
        }

        const duration = Number(serviceField.selectedOptions[0]?.dataset.duration ?? 0);
        slotOptions.classList.remove('hidden');
        slotOptions.classList.add('grid');

        slots.forEach((slot) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rounded-xl border border-cream-300 bg-white px-3 py-3 text-sm font-semibold text-cocoa-800 shadow-sm transition hover:border-sage-500 hover:bg-sage-50';
            button.dataset.slotValue = slot;
            button.setAttribute('aria-pressed', 'false');
            button.textContent = `${formatTime(slot)} - ${formatTime(addMinutes(slot, duration))}`;
            button.addEventListener('click', () => selectSlot(button, slot));
            slotOptions.append(button);

            if (slot === previousValue) {
                selectSlot(button, slot);
            }
        });
    };

    const loadSlots = async () => {
        const previousValue = selectedSlotField.value;
        clearSelection();
        requestController?.abort();

        if (!serviceField.value || !therapistField.value || !dateField.value) {
            slotStatus.textContent = 'Select all booking details to view available times.';
            slotStatus.classList.remove('hidden');

            return;
        }

        requestController = new AbortController();
        slotStatus.textContent = 'Checking available times...';
        slotStatus.classList.remove('hidden');

        const query = new URLSearchParams({
            service_id: serviceField.value,
            therapist_profile_id: therapistField.value,
            appointment_date: dateField.value,
        });

        try {
            const response = await fetch(`${bookingForm.dataset.slotsUrl}?${query}`, {
                headers: { Accept: 'application/json' },
                signal: requestController.signal,
            });

            if (!response.ok) {
                throw new Error('Unable to load appointment slots.');
            }

            const payload = await response.json();
            renderSlots(payload.slots ?? [], previousValue);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            slotStatus.textContent = 'Available times could not be loaded. Please try again.';
        }
    };

    [serviceField, therapistField, dateField].forEach((field) => field.addEventListener('change', loadSlots));
    loadSlots();
}

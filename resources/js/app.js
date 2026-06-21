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

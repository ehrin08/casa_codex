import './bootstrap';

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

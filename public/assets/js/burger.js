// === VARIABLES ===
let burger = null;
let overlay = null;

// === FUNCTIONS ===
function toggleMenu() {
    burger.classList.toggle('open');
    overlay.classList.toggle('is-open');
}

function closeMenu() {
    burger.classList.remove('open');
    overlay.classList.remove('is-open');
}

function setActiveLink(e) {
    document.querySelectorAll('.burger__overlay a').forEach(link => {
        link.classList.remove('is-active');
    });
    e.target.classList.add('is-active');
    closeMenu();
}

// === DOM ===
document.addEventListener('DOMContentLoaded', () => {
    burger = document.querySelector('.header__toggle');
    overlay = document.querySelector('.burger__overlay');

    if (burger && overlay) {
        burger.addEventListener('click', toggleMenu);

        overlay.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', setActiveLink);
        });
    }

    // === SIDEBAR EXPANDABLE ===
    document.querySelectorAll('.sidebar__link').forEach(link => {
        link.addEventListener('click', (e) => {
            const parentItem = link.parentElement;
            const submenu = parentItem.querySelector('.sidebar__submenu');

            if (submenu) {
                e.preventDefault();

                const isOpen = parentItem.classList.contains('is-open');

                document.querySelectorAll('.sidebar__item.is-open').forEach(item => {
                    item.classList.remove('is-open');
                    const sub = item.querySelector('.sidebar__submenu');
                    const trigger = item.querySelector('.sidebar__link');
                    if (sub) {
                        sub.style.maxHeight = null;
                        sub.setAttribute('aria-hidden', 'true');
                    }
                    if (trigger) {
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                });

                // Ouvre si pas déjà ouvert
                if (!isOpen) {
                    parentItem.classList.add('is-open');
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                    submenu.setAttribute('aria-hidden', 'false');
                    link.setAttribute('aria-expanded', 'true');
                }
            }
        });
    });
});
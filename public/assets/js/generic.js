// === VARIABLES ===


// === FUNCTIONS ===
function scrollToHighlight() {
    const highlightedTr = document.querySelector('tr.highlight');
    if (highlightedTr) {
        highlightedTr.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// === DOM ===
document.addEventListener('DOMContentLoaded', () => {

    const flashMessage = document.querySelector('.message__modal');
    if (flashMessage) {
        flashMessage.focus();
    }

    const printBtn = document.querySelector('#print-btn');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('message__close')) {
            e.target.closest('.message').remove();
        }
    });

    setTimeout(() => {
        const overlay = document.querySelector('.message');
        if (overlay) {
            overlay.remove();
        }
    }, 5000);

    scrollToHighlight();
});

// === VARIABLES ===
let page = 2;
let filter = 'all';
let stockTableBody = null;

let widthInput = null;
let heightInput = null;
let diameterInput = null;
let quantityInput = null;
let unitPriceInput = null;
let loadIndexInput = null;
let speedIndexInput = null;
let dotInput = null;
let seasonInput = null;
let qualityInputs = null;

let previewSize = null;
let previewQuantity = null;
let previewPrice = null;
let previewLoadSpeed = null;
let previewDot = null;
let previewSeason = null;
let previewQuality = null;

let originalQuantity = null;
let reasonWrapper = null;
let reasonSelect = null;
let reasonDetailWrapper = null;

let deleteModal;
let modalConfirmBtn;
let modalCancelBtn;
let modalProductIdInput;
let modalDeleteActionInput;
let modalReasonWrapper;
let modalReasonSelect;
let modalReasonDetailWrapper;
let modalArchiveBtn;
let modalDeleteBtn;
let modalChoiceBlock;
let modalValidateBlock;

let createBrandSelect = null;
let createBrandOtherInput = null;
let createWidthInput = null;
let createHeightInput = null;
let createDiameterInput = null;
let createLoadIndexInput = null;
let createSpeedIndexInput = null;
let createDotInput = null;
let createQuantityInput = null;
let createUnitPriceInput = null;
let createSeasonSelect = null;
let createQualitySelect = null;
let createFeaturesInput = null;

let createQuitButton = null;

let quitModal = null;
let quitConfirmBtn = null;
let quitCancelBtn = null;

let duplicateModal = null;
let duplicateConfirmBtn = null;
let duplicateProductId = null;
let duplicateAddedQty = null;
let duplicateCancelBtn = null;

let stockCreateForm = null;
let stockCreateTableBody = null;
let stockCreateMessageBox = null;

// === FUNCTIONS ===
async function loadMoreStock(event) {
    event.preventDefault();
    try {
        const checkedRadio = document.querySelector('input[name="stockFilter"]:checked');
        filter = checkedRadio ? checkedRadio.value : 'all';
        const url = `http://localhost/stockeasy/public/index.php?route=ajax-stock-list&stockFilter=${filter}&page=${page}`;
        const response = await fetch(url);
        const newLines = await response.text();
        stockTableBody.insertAdjacentHTML('beforeend', newLines);
        updateTableLabels();
        page++;
    } catch (error) {
        console.error('Erreur lors du chargement des pneus :', error);
    }
}

async function loadAllStock(event) {
    event.preventDefault();
    try {
        const checkedRadio = document.querySelector('input[name="stockFilter"]:checked');
        filter = checkedRadio ? checkedRadio.value : 'all';
        const url = `http://localhost/stockeasy/public/index.php?route=ajax-stock-list&stockFilter=${filter}&limit=9999`;
        const response = await fetch(url);
        const newLines = await response.text();
        stockTableBody.innerHTML = '';
        stockTableBody.insertAdjacentHTML('beforeend', newLines);
        updateTableLabels();
        page++;
    } catch (error) {
        console.error('Erreur lors du chargement des pneus :', error);
    }
}

function updateTableLabels() {
    const allCells = document.querySelectorAll('td');
    const allTh = document.querySelectorAll('th');

    for (let i = 0; i < allCells.length; i++) {
        const cell = allCells[i];
        const index = cell.cellIndex;
        const label = allTh[index].getAttribute("data-label");
        cell.setAttribute("data-label", label);
        cell.classList.add('table__td');
    }

    const allRows = document.querySelectorAll('tbody tr');
    allRows.forEach(row => {
        row.classList.add('table__tr');
    });

    const resultCount = stockTableBody.querySelectorAll('tr').length;
    document.querySelectorAll('.result-count').forEach(el => {
        el.textContent = resultCount;
    });
}

function updateSizePreview() {
    if (widthInput && heightInput && diameterInput && previewSize) {
        previewSize.textContent = widthInput.value + '/' + heightInput.value + '/' + diameterInput.value;
    }
}

function updateQuantityPreview() {
    if (quantityInput && previewQuantity) {
        previewQuantity.textContent = quantityInput.value;
    }
}

function updatePricePreview() {
    if (unitPriceInput && previewPrice) {
        previewPrice.textContent = unitPriceInput.value;
    }
}

function updateLoadSpeedPreview() {
    if (loadIndexInput && speedIndexInput && previewLoadSpeed) {
        previewLoadSpeed.textContent = loadIndexInput.value + speedIndexInput.value;
    }
}

function updateSeasonPreview() {
    const checked = document.querySelector('input[name="season"]:checked');
    previewSeason.textContent = checked ? checked.value : '';
}

function updateQualityPreview() {
    const checked = document.querySelector('input[name="quality"]:checked');
    if (checked) {
        previewQuality.textContent = checked.value;
    }
}

function toggleReasonWrapper() {
    const currentQuantity = parseInt(quantityInput.value, 10);
    if (quantityInput && currentQuantity !== originalQuantity) {
        reasonWrapper.classList.remove('is-hidden');
    } else {
        reasonWrapper.classList.add('is-hidden');
        reasonDetailWrapper.classList.add('is-hidden');
    }
}

function toggleReasonDetail() {
    if (reasonSelect && reasonSelect.value === 'autre') {
        reasonDetailWrapper.classList.remove('is-hidden');
    } else {
        reasonDetailWrapper.classList.add('is-hidden');
    }
}

function handleArchiveChoice() {
    modalDeleteActionInput.value = 'archive';
    modalChoiceBlock.classList.add('is-hidden');
    modalValidateBlock.classList.remove('is-hidden');
    modalReasonWrapper.classList.remove('is-hidden');
    modalReasonDetailWrapper.classList.add('is-hidden');
}

function openDeleteModal(pneuId) {
    deleteModal.classList.remove('is-hidden');
    modalProductIdInput.value = pneuId;
    modalDeleteActionInput.value = '';
    modalChoiceBlock.classList.remove('is-hidden');
    modalValidateBlock.classList.add('is-hidden');
    modalReasonWrapper.classList.add('is-hidden');
    modalReasonDetailWrapper.classList.add('is-hidden');
}

function closeDeleteModal() {
    deleteModal.classList.add('is-hidden');
    modalChoiceBlock.classList.remove('is-hidden');
    modalValidateBlock.classList.add('is-hidden');
    modalReasonWrapper.classList.add('is-hidden');
    modalReasonDetailWrapper.classList.add('is-hidden');
}

function toggleReasonDetailInModal() {
    if (modalReasonSelect.value === 'autre') {
        modalReasonDetailWrapper.classList.remove('is-hidden');
    } else {
        modalReasonDetailWrapper.classList.add('is-hidden');
    }
}

function openQuitModal() {
    quitModal.classList.remove('is-hidden');
}

function closeQuitModal() {
    quitModal.classList.add('is-hidden');
}

function confirmQuit() {
    window.location.href = 'index.php?route=stock-home';
}

function toggleBrandOtherInput() {
    if (createBrandSelect.value === 'other') {
        createBrandOtherInput.classList.remove('is-hidden');
    } else {
        createBrandOtherInput.classList.add('is-hidden');
        createBrandOtherInput.value = '';
    }
}

function validateInput(input, regex) {
    const value = input.value.trim();
    return regex.test(value);
}

function handleDot() {
    let dotField = createDotInput || dotInput;
    if (!dotField) return;

    let value = dotField.value.trim();
    if (/^\d{2}$/.test(value)) {
        value = '20' + value;
    }
    const year = parseInt(value, 10);
    if (year < 2000) {
        dotField.value = '';
    } else {
        dotField.value = year;
    }

    if (previewDot) {
        previewDot.textContent = dotField.value;
    }
}

function focusNext(currentInput, nextInput, regex) {
    if (validateInput(currentInput, regex)) {
        nextInput.focus();
    }
}

function prepareDuplicateForm() {
    const addedQty = createQuantityInput.value.trim();

    if (!addedQty || isNaN(addedQty) || addedQty <= 0) {
        alert("Veuillez saisir une quantité valide avant de confirmer l'ajout.");
        return;
    }

    duplicateProductId.value = duplicateModal.dataset.id;
    duplicateAddedQty.value = addedQty;

    document.getElementById('duplicateForm').submit();
}

// === DOM ===
document.addEventListener('DOMContentLoaded', () => {
    // === SECURITY ===
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        fetch('index.php?route=ajax-stock-list', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        });

    // === STOCK-LIST ===
    const loadMoreBtn = document.getElementById('load-more');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreStock);
    }

    stockTableBody = document.querySelector('.stock-listing tbody');

    const loadAllBtn = document.getElementById('load-all');
    if (loadAllBtn) {
        loadAllBtn.addEventListener('click', loadAllStock);
    }

    // === STOCK-UPDATE ===
    widthInput = document.getElementById('width');
    heightInput = document.getElementById('height');
    diameterInput = document.getElementById('diameter');
    quantityInput = document.getElementById('quantity_available');
    unitPriceInput = document.getElementById('unit_price_excluding_tax');
    loadIndexInput = document.getElementById('load_index');
    speedIndexInput = document.getElementById('speed_index');
    dotInput = document.getElementById('dot');
    seasonInput = document.querySelectorAll('input[name="season"]');
    qualityInputs = document.querySelectorAll('input[name="quality"]');

    previewSize = document.getElementById('preview-size');
    previewQuantity = document.getElementById('preview-quantity');
    previewPrice = document.getElementById('preview-price');
    previewLoadSpeed = document.getElementById('preview-load-speed');
    previewDot = document.getElementById('preview-dot');
    previewSeason = document.getElementById('preview-season');
    previewQuality = document.getElementById('preview-quality');

    if (widthInput) widthInput.addEventListener('input', updateSizePreview);
    if (heightInput) heightInput.addEventListener('input', updateSizePreview);
    if (diameterInput) diameterInput.addEventListener('input', updateSizePreview);
    if (quantityInput) quantityInput.addEventListener('input', updateQuantityPreview);
    if (unitPriceInput) unitPriceInput.addEventListener('input', updatePricePreview);
    if (loadIndexInput) loadIndexInput.addEventListener('input', updateLoadSpeedPreview);
    if (speedIndexInput) speedIndexInput.addEventListener('input', updateLoadSpeedPreview);
    if (dotInput) dotInput.addEventListener('blur', handleDot);
    seasonInput.forEach(input => input.addEventListener('change', updateSeasonPreview));
    qualityInputs.forEach(input => input.addEventListener('change', updateQualityPreview));

    const originalQuantityInput = document.getElementById('original_quantity');
    if (originalQuantityInput) {
        originalQuantity = parseInt(originalQuantityInput.value, 10);
    }

    reasonWrapper = document.getElementById('movement_reason_wrapper');
    reasonSelect = document.getElementById('movement_reason');
    reasonDetailWrapper = document.getElementById('reason_detail_wrapper');

    if (quantityInput && reasonWrapper) {
        quantityInput.addEventListener('input', toggleReasonWrapper);
    }
    if (reasonSelect && reasonDetailWrapper) {
        reasonSelect.addEventListener('change', toggleReasonDetail);
    }

    // === DELETE MODAL ===
    deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        modalCancelBtn = document.getElementById('modalCancelBtn');
        modalProductIdInput = document.getElementById('modal_product_id');
        modalDeleteActionInput = document.getElementById('modal_delete_action');
        modalReasonWrapper = document.getElementById('modal_reason_wrapper');
        modalReasonSelect = document.getElementById('movement_reason');
        modalReasonDetailWrapper = document.getElementById('modal_reason_detail_wrapper');
        modalArchiveBtn = document.getElementById('modalArchiveBtn');
        modalDeleteBtn = document.getElementById('modalDeleteBtn');
        modalChoiceBlock = document.querySelector('.modal__choice');
        modalValidateBlock = document.querySelector('.modal__validate');

        modalCancelBtn.addEventListener('click', closeDeleteModal);

        modalArchiveBtn.addEventListener('click', handleArchiveChoice);

        modalDeleteBtn = document.getElementById('modalDeleteBtn');
        if (modalDeleteBtn) {
            modalDeleteBtn.addEventListener('click', () => {
                modalDeleteActionInput.value = 'delete';
                modalChoiceBlock.classList.add('is-hidden');
                modalValidateBlock.classList.remove('is-hidden');
                modalReasonWrapper.classList.remove('is-hidden');
                modalReasonDetailWrapper.classList.add('is-hidden');
            });
        }

        if (modalReasonSelect) {
            modalReasonSelect.addEventListener('change', toggleReasonDetailInModal);
        }

        document.querySelectorAll('td[data-label="Supprimer"] button').forEach(btn => {
            btn.addEventListener('click', () => {
                const pneuId = btn.dataset.id;
                openDeleteModal(pneuId);
            });
        });    
    }

    // === STOCK-CREATE ===
    createBrandSelect = document.getElementById('brand');
    createBrandOtherInput = document.getElementById('brand_other');

    if (createBrandSelect) {
        createBrandSelect.addEventListener('change', toggleBrandOtherInput);
    }

    createWidthInput = document.getElementById('width');
    createHeightInput = document.getElementById('height');
    createDiameterInput = document.getElementById('diameter');
    createLoadIndexInput = document.getElementById('load_index');
    createSpeedIndexInput = document.getElementById('speed_index');
    createDotInput = document.getElementById('dot');

    createQuantityInput = document.getElementById('create_quantity_available');
    createUnitPriceInput = document.getElementById('unit_price_excluding_tax');
    createSeasonSelect = document.getElementById('season');
    createQualitySelect = document.getElementById('quality');
    createFeaturesInput = document.getElementById('features');

    createQuitButton = document.getElementById('quitButton');

    const createWidthRegex = /^\d{3}$/;
    const createHeightRegex = /^(\d{2}|[A-Za-z])$/;
    const createDiameterRegex = /^\d{2}$/;
    const createLoadIndexRegex = /^\d{2,3}$/;
    const createSpeedIndexRegex = /^[A-Za-z]$/;

    if (createWidthInput) {
        createWidthInput.addEventListener('blur', () => focusNext(createWidthInput, createHeightInput, createWidthRegex));
        createWidthInput.addEventListener('input', () => {
            if (validateInput(createWidthInput, createWidthRegex)) createHeightInput.focus();
        });
    }
    if (createHeightInput) {
        createHeightInput.addEventListener('blur', () => focusNext(createHeightInput, createDiameterInput, createHeightRegex));
        createHeightInput.addEventListener('input', () => {
            if (validateInput(createHeightInput, createHeightRegex)) createDiameterInput.focus();
        });
    }
    if (createDiameterInput) {
        createDiameterInput.addEventListener('blur', () => focusNext(createDiameterInput, createLoadIndexInput, createDiameterRegex));
        createDiameterInput.addEventListener('input', () => {
            if (validateInput(createDiameterInput, createDiameterRegex)) createLoadIndexInput.focus();
        });
    }
    if (createLoadIndexInput) {
        createLoadIndexInput.addEventListener('blur', () => focusNext(createLoadIndexInput, createSpeedIndexInput, createLoadIndexRegex));
    }
    if (createSpeedIndexInput) {
        createSpeedIndexInput.addEventListener('blur', () => focusNext(createSpeedIndexInput, createDotInput, createSpeedIndexRegex));
        createSpeedIndexInput.addEventListener('input', () => {
            if (validateInput(createSpeedIndexInput, createSpeedIndexRegex)) createDotInput.focus();
        });
    }
    if (createDotInput) {
        createDotInput.addEventListener('blur', handleDot);
    }

    quitModal = document.getElementById('quitModal');
    quitConfirmBtn = document.getElementById('quitConfirmBtn');
    quitCancelBtn = document.getElementById('quitCancelBtn');

    if (createQuitButton && quitModal) {
        createQuitButton.addEventListener('click', openQuitModal);
    }
    if (quitConfirmBtn) quitConfirmBtn.addEventListener('click', confirmQuit);
    if (quitCancelBtn) quitCancelBtn.addEventListener('click', closeQuitModal);

    duplicateModal = document.getElementById('duplicateModal');
    duplicateConfirmBtn = document.getElementById('duplicateConfirmBtn');
    duplicateProductId = document.getElementById('duplicateProductId');
    duplicateAddedQty = document.getElementById('duplicateAddedQty');

    const urlParams = new URLSearchParams(window.location.search);
    const duplicateIdFromUrl = urlParams.get('duplicate_id');

    if (duplicateIdFromUrl && duplicateModal) {
        duplicateModal.dataset.id = duplicateIdFromUrl;
        duplicateModal.classList.remove('is-hidden');
    }

    if (duplicateConfirmBtn) {
        duplicateConfirmBtn.addEventListener('click', prepareDuplicateForm);
    }

    duplicateCancelBtn = document.getElementById('duplicateCancelBtn');
    if (duplicateCancelBtn) {
        duplicateCancelBtn.addEventListener('click', () => {
            duplicateModal.classList.add('is-hidden');
        });
    }
});

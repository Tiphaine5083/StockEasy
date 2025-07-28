// === VARIABLES ===


// === FUNCTIONS ===


// === DOM ===
document.addEventListener('DOMContentLoaded', () => {
    
    const select = document.getElementById('id_role');
    const hint = document.getElementById('role-description');

    if(select && hint) {
        select.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.dataset.description || '';
            hint.textContent = 'Conditions du rôle choisi : ' + description;
        });
    }

});
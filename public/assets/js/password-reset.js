// === VARIABLES ===
let passwordToggles = null;
let newPasswordInput = null;
let confirmPasswordInput = null;
let passwordFeedback = null;

// === FUNCTIONS ===
function togglePasswordVisibility(button) {
    const targetId = button.getAttribute('data-target');
    const targetInput = document.getElementById(targetId);

    if (!targetInput) return;

    const icon = button.querySelector('i');

    if (targetInput.type === 'password') {
        targetInput.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
        button.setAttribute('aria-label', 'Masquer le mot de passe');
    } else {
        targetInput.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
        button.setAttribute('aria-label', 'Afficher le mot de passe');
    }
}

function checkPasswordMatch() {
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (!confirmPassword) {
        passwordFeedback.textContent = '';
        return;
    }

    if (newPassword === confirmPassword) {
        passwordFeedback.textContent = 'Les mots de passe correspondent ✅';
        passwordFeedback.classList.remove('form__hint--error');
        passwordFeedback.classList.add('form__hint--valid');
    } else {
        passwordFeedback.textContent = 'Les mots de passe ne correspondent pas ❌';
        passwordFeedback.classList.remove('form__hint--valid');
        passwordFeedback.classList.add('form__hint--error');
    }
}

// === DOM ===
document.addEventListener('DOMContentLoaded', () => {
    passwordToggles = document.querySelectorAll('.password-toggle');
    newPasswordInput = document.getElementById('new_password');
    confirmPasswordInput = document.getElementById('confirm_password');
    passwordFeedback = document.getElementById('password-match-feedback');

    if (passwordToggles.length > 0) {
        passwordToggles.forEach((btn) => {
            btn.addEventListener('click', () => {
                togglePasswordVisibility(btn);
            });
        });
    }

    if (newPasswordInput && confirmPasswordInput) {
        newPasswordInput.addEventListener('input', checkPasswordMatch);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    }
});

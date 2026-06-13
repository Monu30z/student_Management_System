// Auto-hide toast notifications
document.addEventListener('DOMContentLoaded', function() {
    const toast = document.querySelector('.toast-notification');
    if (toast) {
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
}

// Confirm delete
function confirmDelete(message = 'Are you sure you want to delete this?') {
    return confirm(message);
}

// Show loading spinner
function showLoader() {
    const loader = document.createElement('div');
    loader.className = 'spinner';
    loader.id = 'page-loader';
    document.body.appendChild(loader);
}

// Hide loading spinner
function hideLoader() {
    const loader = document.getElementById('page-loader');
    if (loader) loader.remove();
}

// Auto-focus first input
const firstInput = document.querySelector('input:not([type="hidden"])');
if (firstInput) {
    firstInput.focus();
}

// Add animation to slideOut
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
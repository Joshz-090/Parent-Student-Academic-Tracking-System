/**
 * Toast Notification System
 */

// Create toast container if it doesn't exist
function createToastContainer() {
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
}

// Show toast notification
function showToast(message, type = 'info', duration = 3000) {
    createToastContainer();
    
    const container = document.querySelector('.toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <span style="font-size: 20px;">${icons[type] || icons.info}</span>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => {
            container.removeChild(toast);
        }, 300);
    }, duration);
}

// Export for global use
window.showToast = showToast;

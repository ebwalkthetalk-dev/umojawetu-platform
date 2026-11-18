/**
 * Umojawetu Platform - Shared JavaScript
 * Features: Keyboard Shortcuts, Loading Spinner, Modals
 */

// ============================================
// LOADING SPINNER MANAGEMENT
// ============================================

const Spinner = {
    show: function(message = 'Loading...') {
        let spinner = document.getElementById('loading-spinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'loading-spinner';
            spinner.className = 'loading-spinner';
            spinner.innerHTML = `
                <div class="spinner"></div>
                <p>${message}</p>
            `;
            document.body.appendChild(spinner);
        } else {
            spinner.classList.remove('hidden');
            const p = spinner.querySelector('p');
            if (p) p.textContent = message;
        }
    },
    
    hide: function() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.classList.add('hidden');
        }
    },
    
    message: function(text) {
        const p = document.querySelector('#loading-spinner p');
        if (p) p.textContent = text;
    }
};

// Auto-hide spinner on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => Spinner.hide(), 300);
});

// ============================================
// MODAL MANAGEMENT
// ============================================

const Modal = {
    create: function(id, content, options = {}) {
        let modal = document.getElementById(id);
        if (!modal) {
            modal = document.createElement('div');
            modal.id = id;
            modal.className = 'modal hidden';
            
            const inner = document.createElement('div');
            inner.className = 'modal-content';
            
            const closeBtn = document.createElement('button');
            closeBtn.className = 'modal-close';
            closeBtn.innerHTML = '✕';
            closeBtn.onclick = () => Modal.close(id);
            
            inner.appendChild(closeBtn);
            
            if (typeof content === 'string') {
                inner.innerHTML = content + inner.innerHTML.replace(/✕/, '');
            } else {
                inner.insertBefore(content, closeBtn);
            }
            
            modal.appendChild(inner);
            document.body.appendChild(modal);
            
            // Click outside to close
            modal.addEventListener('click', (e) => {
                if (e.target === modal) Modal.close(id);
            });
        }
        return modal;
    },
    
    show: function(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            // Trap focus in modal
            this.trapFocus(modal);
        }
    },
    
    close: function(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
        }
    },
    
    trapFocus: function(modal) {
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        modal.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        });
        
        focusableElements[0]?.focus();
    }
};

// ============================================
// KEYBOARD SHORTCUTS
// ============================================

const KeyboardShortcuts = {
    shortcuts: {
        '?': () => KeyboardShortcuts.showHelp(),
        '/': () => KeyboardShortcuts.focusSearch(),
        'Escape': () => KeyboardShortcuts.closeModals(),
        'c': () => KeyboardShortcuts.openChat(),
        'p': () => KeyboardShortcuts.navigate('/public/pledge-wall.php'),
        'e': () => KeyboardShortcuts.navigate('/public/events.php'),
        'b': () => KeyboardShortcuts.navigate('/public/booking.php'),
        'g': () => KeyboardShortcuts.navigate('/public/gallery.php'),
    },
    
    adminShortcuts: {
        'a': () => KeyboardShortcuts.navigate('/admin/dashboard-unified.php'),
        'u': () => KeyboardShortcuts.navigate('/admin/attendees.php'),
        'v': () => KeyboardShortcuts.navigate('/admin/events.php'),
        't': () => KeyboardShortcuts.openChat(),
        's': () => KeyboardShortcuts.navigate('/admin/dashboard-unified.php'),
        'x': () => KeyboardShortcuts.navigate('/admin/analytics.php'),
    },
    
    init: function() {
        document.addEventListener('keydown', (e) => {
            // Don't trigger in inputs/textareas
            const activeElement = document.activeElement;
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(activeElement.tagName)) {
                if (e.key === 'Escape') {
                    activeElement.blur();
                }
                return;
            }
            
            // Handle Ctrl/Cmd + K for search (universal)
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                KeyboardShortcuts.focusSearch();
                return;
            }
            
            // Single letter shortcuts
            const key = e.key.toLowerCase();
            const isAdmin = document.body.dataset.isAdmin === 'true';
            const allShortcuts = { ...KeyboardShortcuts.shortcuts };
            
            if (isAdmin) {
                Object.assign(allShortcuts, KeyboardShortcuts.adminShortcuts);
            }
            
            if (allShortcuts[key]) {
                e.preventDefault();
                allShortcuts[key]();
            }
        });
        
        // Hide keyboard hints after first interaction
        this.hideHintsAfterInteraction();
    },
    
    showHelp: function() {
        const isAdmin = document.body.dataset.isAdmin === 'true';
        let shortcuts = [
            ['?', 'Show this help'],
            ['Ctrl/Cmd + K', 'Search events'],
            ['E', 'Go to events'],
            ['B', 'Go to booking'],
            ['P', 'Go to pledges'],
            ['C', 'Open live chat'],
            ['G', 'Open gallery'],
            ['Escape', 'Close modals/exit input']
        ];
        
        if (isAdmin) {
            shortcuts = [
                ['?', 'Show this help'],
                ['Ctrl/Cmd + K', 'Search'],
                ['A', 'Admin dashboard'],
                ['U', 'Attendees/Users'],
                ['V', 'Events'],
                ['T', 'Open chat'],
                ['S', 'Settings'],
                ['X', 'Analytics'],
                ['Escape', 'Close modals']
            ];
        }
        
        let html = '<h2 class="modal-header">⌨️ Keyboard Shortcuts</h2>';
        html += '<table style="width:100%;border-collapse:collapse;">';
        
        shortcuts.forEach(([key, desc]) => {
            html += `
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:8px;">
                        <code style="background:#f0f0f0;padding:4px 8px;border-radius:3px;font-weight:bold;color:#d12828;">${key}</code>
                    </td>
                    <td style="padding:8px;text-align:right;">${desc}</td>
                </tr>
            `;
        });
        
        html += '</table>';
        html += '<p style="margin-top:1rem;font-size:0.875rem;color:#999;">💡 Tip: Dismiss this hint after first use</p>';
        
        const modal = Modal.create('help-modal', html);
        Modal.show('help-modal');
    },
    
    focusSearch: function() {
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="Search"], input[name="search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    },
    
    closeModals: function() {
        document.querySelectorAll('.modal:not(.hidden)').forEach(modal => {
            modal.classList.add('hidden');
        });
    },
    
    openChat: function() {
        const chatToggle = document.getElementById('chat-toggle');
        if (chatToggle) {
            chatToggle.click();
        }
    },
    
    navigate: function(path) {
        window.location.href = path;
    },
    
    hideHintsAfterInteraction: function() {
        document.addEventListener('keydown', () => {
            const hints = document.querySelector('.keyboard-hints:not(.hidden)');
            if (hints) {
                hints.classList.add('hidden');
                localStorage.setItem('hideKeyboardHints', 'true');
            }
        }, { once: true });
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    KeyboardShortcuts.init();
    
    // Check if user dismissed hints
    if (localStorage.getItem('hideKeyboardHints')) {
        document.querySelector('.keyboard-hints')?.classList.add('hidden');
    }
});

// ============================================
// FORM HELPER UTILITIES
// ============================================

const FormHelper = {
    /**
     * Add tooltip to a form field
     */
    addTooltip: function(fieldId, text) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        const wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '0.5rem';
        
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
        
        const tooltip = document.createElement('span');
        tooltip.className = 'tooltip-trigger';
        tooltip.setAttribute('role', 'button');
        tooltip.setAttribute('tabindex', '0');
        tooltip.innerHTML = '?';
        tooltip.title = text;
        
        const tooltipBox = document.createElement('div');
        tooltipBox.className = 'tooltip';
        tooltipBox.textContent = text;
        
        wrapper.appendChild(tooltip);
        wrapper.appendChild(tooltipBox);
    },
    
    /**
     * Show validation error
     */
    showError: function(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        field.classList.add('border-red-500');
        
        let error = field.nextElementSibling;
        if (!error || !error.classList.contains('error-message')) {
            error = document.createElement('p');
            error.className = 'error-message text-red-600 text-sm mt-1';
            field.parentNode.insertBefore(error, field.nextSibling);
        }
        
        error.textContent = message;
    },
    
    /**
     * Clear validation error
     */
    clearError: function(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        field.classList.remove('border-red-500');
        
        const error = field.nextElementSibling;
        if (error && error.classList.contains('error-message')) {
            error.remove();
        }
    }
};

// ============================================
// FETCH WRAPPER WITH SPINNER
// ============================================

async function fetchWithSpinner(url, options = {}, spinnerMessage = 'Loading...') {
    try {
        Spinner.show(spinnerMessage);
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        Spinner.hide();
        return data;
    } catch (error) {
        Spinner.hide();
        console.error('Fetch error:', error);
        Modal.create('error-modal', `<h2 class="modal-header">⚠️ Error</h2><p>${error.message}</p>`);
        Modal.show('error-modal');
        throw error;
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const modal = Modal.create('copy-modal', '<h2 class="modal-header">✅ Copied!</h2><p>Link copied to clipboard.</p>');
        Modal.show('copy-modal');
        setTimeout(() => Modal.close('copy-modal'), 2000);
    });
}

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'GBP') {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-GB', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
}

/**
 * Countdown Timer Component
 * Displays real-time countdown to event date
 * Usage: Add data-countdown="YYYY-MM-DD HH:MM:SS" to any element
 */

class CountdownTimer {
    constructor() {
        this.timers = new Map();
        this.init();
    }
    
    /**
     * Initialize all countdown elements on page
     */
    init() {
        document.querySelectorAll('[data-countdown]').forEach(element => {
            this.attachToElement(element);
        });
        
        // Update every second
        setInterval(() => this.updateAll(), 1000);
        
        // Initial update
        this.updateAll();
    }
    
    /**
     * Attach countdown to an HTML element
     */
    attachToElement(element) {
        const targetDate = element.getAttribute('data-countdown');
        if (!targetDate) return;
        
        const id = Math.random().toString(36).substr(2, 9);
        element.id = element.id || `countdown-${id}`;
        
        this.timers.set(element.id, {
            element: element,
            target: new Date(targetDate).getTime()
        });
        
        // Initialize display
        this.createCountdownDisplay(element);
        this.updateTimer(element);
    }
    
    /**
     * Create countdown display structure
     */
    createCountdownDisplay(element) {
        if (element.querySelector('.countdown-display')) {
            return; // Already created
        }
        
        const display = document.createElement('div');
        display.className = 'countdown-display';
        display.style.cssText = `
            display: flex;
            gap: 1rem;
            justify-content: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%);
            border-radius: 8px;
            margin: 1rem 0;
            font-weight: bold;
            flex-wrap: wrap;
        `;
        
        display.innerHTML = `
            <div class="countdown-item" style="display:flex; flex-direction:column; align-items:center;">
                <span class="countdown-value" data-unit="days" style="font-size:1.875rem; color:#d12828; min-width:60px; text-align:center;">-</span>
                <span class="countdown-label" style="font-size:0.75rem; color:#666; text-transform:uppercase; margin-top:0.25rem;">Days</span>
            </div>
            <div class="countdown-item" style="display:flex; flex-direction:column; align-items:center;">
                <span class="countdown-value" data-unit="hours" style="font-size:1.875rem; color:#d12828; min-width:60px; text-align:center;">-</span>
                <span class="countdown-label" style="font-size:0.75rem; color:#666; text-transform:uppercase; margin-top:0.25rem;">Hours</span>
            </div>
            <div class="countdown-item" style="display:flex; flex-direction:column; align-items:center;">
                <span class="countdown-value" data-unit="minutes" style="font-size:1.875rem; color:#d12828; min-width:60px; text-align:center;">-</span>
                <span class="countdown-label" style="font-size:0.75rem; color:#666; text-transform:uppercase; margin-top:0.25rem;">Minutes</span>
            </div>
            <div class="countdown-item" style="display:flex; flex-direction:column; align-items:center;">
                <span class="countdown-value" data-unit="seconds" style="font-size:1.875rem; color:#d12828; min-width:60px; text-align:center;">-</span>
                <span class="countdown-label" style="font-size:0.75rem; color:#666; text-transform:uppercase; margin-top:0.25rem;">Seconds</span>
            </div>
        `;
        
        element.insertBefore(display, element.firstChild);
    }
    
    /**
     * Update all timers
     */
    updateAll() {
        this.timers.forEach((timer, id) => {
            this.updateTimer(timer.element);
        });
    }
    
    /**
     * Update individual timer
     */
    updateTimer(element) {
        const targetDate = element.getAttribute('data-countdown');
        if (!targetDate) return;
        
        const target = new Date(targetDate).getTime();
        const now = new Date().getTime();
        const difference = target - now;
        
        const display = element.querySelector('.countdown-display');
        if (!display) return;
        
        // Event has started or passed
        if (difference <= 0) {
            display.innerHTML = '<p style="text-align:center; color:#d12828; font-weight:bold; margin:0;">🎉 Event is live now!</p>';
            display.style.backgroundImage = 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)';
            element.classList.add('event-live');
            return;
        }
        
        // Calculate time units
        const days = Math.floor(difference / (1000 * 60 * 60 * 24));
        const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((difference % (1000 * 60)) / 1000);
        
        // Update display
        const daysEl = display.querySelector('[data-unit="days"]');
        const hoursEl = display.querySelector('[data-unit="hours"]');
        const minutesEl = display.querySelector('[data-unit="minutes"]');
        const secondsEl = display.querySelector('[data-unit="seconds"]');
        
        if (daysEl) daysEl.textContent = days.toString().padStart(2, '0');
        if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, '0');
        if (minutesEl) minutesEl.textContent = minutes.toString().padStart(2, '0');
        if (secondsEl) secondsEl.textContent = seconds.toString().padStart(2, '0');
        
        // Add urgency styling if less than 24 hours
        if (difference < 86400000) {
            display.style.backgroundImage = 'linear-gradient(135deg, #fee2e2 0%, #fecaca 100%)';
            display.classList.add('urgent');
            
            // Pulse animation for less than 1 hour
            if (difference < 3600000) {
                display.style.animation = 'pulse 1s infinite';
            }
        } else {
            display.style.backgroundImage = 'linear-gradient(135deg, #fef3c7 0%, #fcd34d 100%)';
            display.classList.remove('urgent');
            display.style.animation = 'none';
        }
    }
    
    /**
     * Add new countdown timer
     */
    addTimer(element, targetDate) {
        element.setAttribute('data-countdown', targetDate);
        this.attachToElement(element);
    }
}

// Initialize on DOMContentLoaded
const countdownManager = new CountdownTimer();

// Add pulse animation to page if not exists
if (!document.querySelector('style[data-countdown-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-countdown-styles', 'true');
    style.textContent = `
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        @media (prefers-reduced-motion: reduce) {
            .countdown-display {
                animation: none !important;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Social Sharing Buttons Component
 * Provides easy sharing to WhatsApp, Facebook, Twitter, LinkedIn, Email, and copy-to-clipboard
 */

class SocialSharing {
    constructor(options = {}) {
        this.options = {
            position: 'inline', // 'inline', 'floating', 'modal'
            includeButtons: ['whatsapp', 'facebook', 'twitter', 'email', 'copy'],
            ...options
        };
        
        // Default share messages by context
        this.messages = {
            event: '🎉 Just found this amazing event: {title}! Happening {date}. Let\'s go together!',
            pledge: '🤝 I\'m pledging £{amount} to support {campaign}. Join me!',
            booking: '✅ Booked! See you at {title} on {date}. Got your tickets?',  
            gallery: '🎬 Check out this incredible gallery from our events!',
            default: 'Check this out!'
        };
        
        this.init();
    }
    
    /**
     * Initialize social sharing on elements with data-share attribute
     */
    init() {
        document.querySelectorAll('[data-share]').forEach(element => {
            this.attachToElement(element);
        });
    }
    
    /**
     * Attach sharing buttons to an element
     */
    attachToElement(element) {
        const shareType = element.getAttribute('data-share');
        const container = this.createShareContainer(shareType);
        
        // Insert after or inside element depending on position
        if (this.options.position === 'inline') {
            element.appendChild(container);
        } else {
            element.insertAdjacentElement('afterend', container);
        }
    }
    
    /**
     * Create share button container
     */
    createShareContainer(type) {
        const container = document.createElement('div');
        container.className = 'social-share-container';
        container.style.cssText = `
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin: 1rem 0;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        `;
        
        // Get share data from element attributes or context
        const shareData = this.getShareData(type, container.parentElement);
        
        // Create buttons
        const buttons = {
            whatsapp: this.createWhatsAppButton(shareData),
            facebook: this.createFacebookButton(shareData),
            twitter: this.createTwitterButton(shareData),
            linkedin: this.createLinkedInButton(shareData),
            email: this.createEmailButton(shareData),
            copy: this.createCopyButton(shareData)
        };
        
        // Add selected buttons
        this.options.includeButtons.forEach(type => {
            if (buttons[type]) {
                container.appendChild(buttons[type]);
            }
        });
        
        // Add label
        const label = document.createElement('label');
        label.style.cssText = `
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #666;
            margin-right: auto;
        `;
        label.innerHTML = '📤 Share: ';
        container.insertBefore(label, container.firstChild);
        
        return container;
    }
    
    /**
     * Get share data from context
     */
    getShareData(type, context) {
        return {
            type: type,
            title: context?.getAttribute('data-share-title') ||  document.title,
            description: context?.getAttribute('data-share-desc') || document.querySelector('meta[name="description"]')?.content || '',
            url: context?.getAttribute('data-share-url') || window.location.href,
            image: context?.getAttribute('data-share-image') || document.querySelector('meta[property="og:image"]')?.content || ''
        };
    }
    
    /**
     * Create WhatsApp share button
     */
    createWhatsAppButton(data) {
        const message = this.sanitizeMessage(`${data.title}\n\n${data.url}`);
        const url = `https://wa.me/?text=${encodeURIComponent(message)}`;
        
        return this.createShareButton('whatsapp', '💬 WhatsApp', url, 'Share on WhatsApp');
    }
    
    /**
     * Create Facebook share button
     */
    createFacebookButton(data) {
        const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(data.url)}`;
        return this.createShareButton('facebook', '👍 Facebook', url, 'Share on Facebook');
    }
    
    /**
     * Create Twitter share button
     */
    createTwitterButton(data) {
        const text = this.sanitizeMessage(data.title);
        const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(data.url)}&hashtags=umojawetu`;
        return this.createShareButton('twitter', '🐦 Twitter', url, 'Share on Twitter');
    }
    
    /**
     * Create LinkedIn share button
     */
    createLinkedInButton(data) {
        const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(data.url)}`;
        return this.createShareButton('linkedin', '💼 LinkedIn', url, 'Share on LinkedIn');
    }
    
    /**
     * Create Email share button
     */
    createEmailButton(data) {
        const subject = this.sanitizeMessage(data.title);
        const body = this.sanitizeMessage(`${data.description}\n\n${data.url}`);
        const url = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        return this.createShareButton('email', '✉️ Email', url, 'Share via Email');
    }
    
    /**
     * Create copy-to-clipboard button
     */
    createCopyButton(data) {
        const button = document.createElement('button');
        button.className = 'social-share-btn';
        button.innerHTML = '🔗 Copy Link';
        button.title = 'Copy link to clipboard';
        button.style.cssText = `
            flex: 1;
            min-width: 120px;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            transition: all 0.2s ease;
            white-space: nowrap;
        `;
        
        button.addEventListener('click', (e) => {
            e.preventDefault();
            this.copyToClipboard(data.url);
        });
        
        button.addEventListener('hover mouseenter', () => {
            button.style.background = '#f3f4f6';
            button.style.borderColor = '#d12828';
        });
        
        return button;
    }
    
    /**
     * Create generic share button
     */
    createShareButton(platform, label, url, title) {
        const button = document.createElement('a');
        button.href = url;
        button.className = 'social-share-btn';
        button.innerHTML = label;
        button.title = title;
        button.target = '_blank';
        button.rel = 'noopener noreferrer';
        button.style.cssText = `
            flex: 1;
            min-width: 120px;
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            white-space: nowrap;
        `;
        
        button.addEventListener('mouseenter', () => {
            button.style.background = '#f3f4f6';
            button.style.borderColor = '#d12828';
            
            // Analytics
            this.trackShare(platform);
        });
        
        return button;
    }
    
    /**
     * Copy URL to clipboard
     */
    copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Show success message
            const modal = Modal.create('copy-success-modal', `
                <div style="text-align:center;">
                    <h2 style="font-size:2rem; margin-bottom:1rem;">✅</h2>
                    <p style="font-size:1.125rem; color:#374151;">Link copied to clipboard!</p>
                </div>
            `);
            Modal.show('copy-success-modal');
            
            // Auto-close after 2 seconds
            setTimeout(() => Modal.close('copy-success-modal'), 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy link. Please try again.');
        });
    }
    
    /**
     * Sanitize message for social sharing
     */
    sanitizeMessage(text) {
        return text
            .replace(/[\r\n]/g, ' ')
            .substring(0, 280) // Twitter limit
            .trim();
    }
    
    /**
     * Track share action with analytics
     */
    trackShare(platform) {
        if (typeof gtag !== 'undefined') {
            gtag('event', 'share', {
                method: platform,
                content_type: 'page',
                content_id: window.location.pathname
            });
        }
    }
    
    /**
     * Create floating action button for sharing (alternative presentation)
     */
    createFloatingShareButton() {
        const button = document.createElement('button');
        button.id = 'floating-share-btn';
        button.innerHTML = '📤';
        button.title = 'Share this';
        button.style.cssText = `
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #d12828;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(209, 40, 40, 0.4);
            transition: all 0.3s ease;
            z-index: 50;
        `;
        
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'scale(1.1)';
            button.style.boxShadow = '0 6px 20px rgba(209, 40, 40, 0.6)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.transform = 'scale(1)';
            button.style.boxShadow = '0 4px 12px rgba(209, 40, 40, 0.4)';
        });
        
        button.addEventListener('click', () => {
            this.showShareModal();
        });
        
        return button;
    }
    
    /**
     * Show share options in a modal
     */
    showShareModal() {
        const data = this.getShareData('default', document);
        
        let html = `
            <div>
                <h2 style="font-size:1.5rem; font-weight:bold; margin-bottom:1rem;">📤 Share This</h2>
                <p style="color:#666; margin-bottom:1rem;">Choose how you want to share:</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        `;
        
        const buttons = {
            whatsapp: {label: '💬 WhatsApp', icon: 'wa'},
            facebook: {label: '👍 Facebook', icon: 'fb'},
            twitter: {label: '🐦 Twitter', icon: 'tw'},
            email: {label: '✉️ Email', icon: 'email'},
            copy: {label: '🔗 Copy Link', icon: 'copy'}
        };
        
        Object.entries(buttons).forEach(([type, btn]) => {
            html += `
                <button 
                    onclick="sharingManager.${type === 'copy' ? 'copyToClipboard' : 'openShare'}('${type}', '${data.url}')"
                    style="padding:1rem; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:6px; cursor:pointer; font-size:1rem; transition:all 0.2s;"
                    onmouseover="this.style.background='#e5e7eb'; this.style.borderColor='#d12828';"
                    onmouseout="this.style.background='#f3f4f6'; this.style.borderColor='#e5e7eb';"
                >
                    ${btn.label}
                </button>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
        
        const modal = Modal.create('share-modal', html);
        Modal.show('share-modal');
    }
    
    /**
     * Open specific share platform
     */
    openShare(platform, url) {
        const data = { url: url, title: document.title };
        const shareUrls = {
            whatsapp: `https://wa.me/?text=${encodeURIComponent(data.title +'  ' + url)}`,
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
            twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}`,
            email: `mailto:?subject=Check this out&body=${encodeURIComponent(url)}`
        };
        
        if (shareUrls[platform]) {
            window.open(shareUrls[platform], '_blank', 'width=600,height=400');
        }
    }
}

// Initialize on DOMContentLoaded
const sharingManager = new SocialSharing();

// Add styles if not already added
if (!document.querySelector('style[data-social-sharing-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-social-sharing-styles', 'true');
    style.textContent = `
        .social-share-btn:hover {
            background: #f3f4f6 !important;
            border-color: #d12828 !important;
        }
        
        .social-share-btn:active {
            transform: scale(0.95);
        }
        
        @media (max-width: 640px) {
            .social-share-container {
                flex-direction: column;
            }
            
            .social-share-container > label {
                width: 100%;
            }
            
            .social-share-btn {
                width: 100% !important;
                min-width: unset !important;
            }
        }
        
        @media (prefers-reduced-motion: reduce) {
            .social-share-btn {
                transition: none !important;
            }
        }
    `;
    document.head.appendChild(style);
}

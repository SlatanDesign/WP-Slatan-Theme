/**
 * Cookie Consent JavaScript
 *
 * @package WP_Slatan_Theme
 * @since 1.0.22
 */

(function () {
    'use strict';

    // Configuration from WordPress
    const config = window.wpsltCookieConsent || {};

    // Default configuration
    const defaults = {
        cookieName: 'wpslt_cookie_consent',
        cookieExpiry: 365,
        bannerType: 'bar-bottom',
        animation: 'slide',
        showRevisit: true,
        categories: []
    };

    // Merge config with defaults
    const settings = { ...defaults, ...config };

    // Store
    const store = {
        consent: null,
        categories: new Map(),
        initialized: false
    };

    /**
     * Cookie utilities
     */
    const Cookie = {
        /**
         * Set a cookie
         */
        set: function (name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = 'expires=' + date.toUTCString();
            document.cookie = name + '=' + encodeURIComponent(value) + ';' + expires + ';path=/;SameSite=Strict';
        },

        /**
         * Get a cookie value
         */
        get: function (name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(c.substring(nameEQ.length, c.length));
                }
            }
            return null;
        },

        /**
         * Delete a cookie
         */
        erase: function (name) {
            document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
        },

        /**
         * Check if cookie exists
         */
        exists: function (name) {
            return this.get(name) !== null;
        }
    };

    /**
     * Generate random consent ID
     */
    function generateConsentId() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < 32; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    /**
     * Parse consent cookie
     */
    function parseConsent() {
        const cookieValue = Cookie.get(settings.cookieName);
        if (!cookieValue) {
            return null;
        }

        try {
            const consent = JSON.parse(cookieValue);
            return consent;
        } catch (e) {
            return null;
        }
    }

    /**
     * Save consent to cookie
     */
    function saveConsent(categories, action) {
        const consent = {
            id: store.consent?.id || generateConsentId(),
            timestamp: new Date().toISOString(),
            action: action, // 'accept-all', 'reject-all', 'save-preferences'
            categories: categories
        };

        Cookie.set(settings.cookieName, JSON.stringify(consent), settings.cookieExpiry);
        store.consent = consent;

        // Fire custom event
        const event = new CustomEvent('wpslt_cookie_consent_update', {
            detail: {
                consent: consent,
                accepted: Object.keys(categories).filter(k => categories[k] === true),
                rejected: Object.keys(categories).filter(k => categories[k] === false)
            }
        });
        document.dispatchEvent(event);
    }

    /**
     * Get initial category states
     */
    function getInitialCategoryStates() {
        const states = {};
        settings.categories.forEach(cat => {
            if (cat.isNecessary) {
                states[cat.id] = true;
            } else {
                states[cat.id] = cat.defaultState || false;
            }
        });
        return states;
    }

    /**
     * Get category states from checkboxes
     */
    function getCategoryStatesFromCheckboxes() {
        const states = {};
        settings.categories.forEach(cat => {
            const checkbox = document.querySelector(`[data-category="${cat.id}"]`);
            if (checkbox) {
                states[cat.id] = checkbox.checked;
            } else {
                states[cat.id] = cat.isNecessary ? true : cat.defaultState;
            }
        });
        return states;
    }

    /**
     * Update checkboxes based on consent
     */
    function updateCheckboxes(categories) {
        Object.entries(categories).forEach(([id, value]) => {
            const checkbox = document.querySelector(`[data-category="${id}"]`);
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = value;
            }
        });
    }

    /**
     * DOM Elements
     */
    const elements = {
        banner: null,
        modal: null,
        overlay: null,
        revisit: null
    };

    /**
     * Initialize elements
     */
    function initElements() {
        elements.banner = document.getElementById('wpslt-cookie-banner');
        elements.modal = document.getElementById('wpslt-cookie-modal');
        elements.overlay = document.getElementById('wpslt-cookie-overlay');
        elements.revisit = document.getElementById('wpslt-cookie-revisit');
    }

    /**
     * Show banner
     */
    function showBanner() {
        if (elements.banner) {
            elements.banner.classList.remove('wpslt-cookie-hidden');
            if (elements.overlay) {
                elements.overlay.classList.remove('wpslt-cookie-hidden');
            }
        }
    }

    /**
     * Hide banner
     */
    function hideBanner() {
        if (elements.banner) {
            elements.banner.classList.add('wpslt-cookie-hidden');
            if (elements.overlay) {
                elements.overlay.classList.add('wpslt-cookie-hidden');
            }
        }
    }

    /**
     * Show modal
     */
    function showModal() {
        if (elements.modal) {
            elements.modal.classList.remove('wpslt-cookie-hidden');
            document.body.style.overflow = 'hidden';

            // Focus first focusable element
            const firstFocusable = elements.modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) {
                firstFocusable.focus();
            }
        }
    }

    /**
     * Hide modal
     */
    function hideModal() {
        if (elements.modal) {
            elements.modal.classList.add('wpslt-cookie-hidden');
            document.body.style.overflow = '';
        }
    }

    /**
     * Show revisit button
     */
    function showRevisit() {
        if (settings.showRevisit && elements.revisit) {
            elements.revisit.classList.remove('wpslt-cookie-hidden');
        }
    }

    /**
     * Hide revisit button
     */
    function hideRevisit() {
        if (elements.revisit) {
            elements.revisit.classList.add('wpslt-cookie-hidden');
        }
    }

    /**
     * Handle accept all
     */
    function handleAcceptAll() {
        const categories = {};
        settings.categories.forEach(cat => {
            categories[cat.id] = true;
        });

        saveConsent(categories, 'accept-all');
        updateCheckboxes(categories);
        hideBanner();
        hideModal();
        showRevisit();
    }

    /**
     * Handle reject all
     */
    function handleRejectAll() {
        const categories = {};
        settings.categories.forEach(cat => {
            categories[cat.id] = cat.isNecessary ? true : false;
        });

        saveConsent(categories, 'reject-all');
        updateCheckboxes(categories);
        hideBanner();
        hideModal();
        showRevisit();
    }

    /**
     * Handle save preferences
     */
    function handleSavePreferences() {
        const categories = getCategoryStatesFromCheckboxes();
        saveConsent(categories, 'save-preferences');
        hideBanner();
        hideModal();
        showRevisit();
    }

    /**
     * Handle open settings
     */
    function handleOpenSettings() {
        hideBanner();
        hideRevisit();

        // Update checkboxes from current consent
        if (store.consent && store.consent.categories) {
            updateCheckboxes(store.consent.categories);
        }

        showModal();
    }

    /**
     * Handle close modal
     */
    function handleCloseModal() {
        hideModal();

        if (!store.consent) {
            showBanner();
        } else {
            showRevisit();
        }
    }

    /**
     * Bind events
     */
    function bindEvents() {
        // Click event delegation
        document.addEventListener('click', function (e) {
            const target = e.target.closest('[data-action]');
            if (!target) return;

            const action = target.getAttribute('data-action');

            switch (action) {
                case 'accept-all':
                    handleAcceptAll();
                    break;
                case 'reject-all':
                    handleRejectAll();
                    break;
                case 'save-preferences':
                    handleSavePreferences();
                    break;
                case 'open-settings':
                    handleOpenSettings();
                    break;
                case 'close-modal':
                    handleCloseModal();
                    break;
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && elements.modal && !elements.modal.classList.contains('wpslt-cookie-hidden')) {
                handleCloseModal();
            }
        });

        // Focus trap in modal
        if (elements.modal) {
            elements.modal.addEventListener('keydown', function (e) {
                if (e.key !== 'Tab') return;

                const focusableElements = elements.modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
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
        }
    }

    /**
     * Initialize
     */
    function init() {
        if (store.initialized) return;
        store.initialized = true;

        // Initialize script blocking FIRST (before consent check)
        ScriptBlocker.init();

        initElements();

        // Check existing consent
        store.consent = parseConsent();

        if (store.consent) {
            // User has already given consent
            updateCheckboxes(store.consent.categories);
            // Execute scripts for consented categories
            ScriptBlocker.checkAndExecute();
            if (elements.banner) {
                showRevisit();
            }
        } else {
            // First visit, show banner
            const initialStates = getInitialCategoryStates();
            updateCheckboxes(initialStates);
            if (elements.banner) {
                showBanner();
            }
        }

        bindEvents();

        // Listen for consent updates to execute newly consented scripts
        document.addEventListener('wpslt_cookie_consent_update', function (e) {
            if (e.detail && e.detail.consent && e.detail.consent.categories) {
                ScriptBlocker.executeBasedOnConsent(e.detail.consent.categories);
            }
        });
    }

    /**
     * Script Blocking - Block scripts until consent
     */
    const ScriptBlocker = {
        /**
         * Store blocked scripts
         */
        blockedScripts: [],

        /**
         * Initialize script blocking
         * Find all scripts with data-wpslt-category and block them
         */
        init: function () {
            // Find all scripts with data-wpslt-category attribute
            const scripts = document.querySelectorAll('script[data-wpslt-category]');

            scripts.forEach(script => {
                const category = script.getAttribute('data-wpslt-category');
                const src = script.getAttribute('data-wpslt-src') || script.getAttribute('src');
                const inlineCode = script.textContent || script.innerHTML;

                // Store script info
                this.blockedScripts.push({
                    category: category,
                    src: src,
                    inline: !src ? inlineCode : null,
                    attributes: this.getScriptAttributes(script),
                    executed: false
                });
            });
        },

        /**
         * Get script attributes (excluding data-wpslt-* and src)
         */
        getScriptAttributes: function (script) {
            const attrs = {};
            for (let i = 0; i < script.attributes.length; i++) {
                const attr = script.attributes[i];
                if (!attr.name.startsWith('data-wpslt-') && attr.name !== 'src' && attr.name !== 'type') {
                    attrs[attr.name] = attr.value;
                }
            }
            return attrs;
        },

        /**
         * Execute scripts for a given category
         */
        executeCategory: function (category) {
            this.blockedScripts.forEach(scriptInfo => {
                if (scriptInfo.category === category && !scriptInfo.executed) {
                    this.executeScript(scriptInfo);
                    scriptInfo.executed = true;
                }
            });
        },

        /**
         * Execute a single script
         */
        executeScript: function (scriptInfo) {
            const newScript = document.createElement('script');

            // Set attributes
            Object.keys(scriptInfo.attributes).forEach(key => {
                newScript.setAttribute(key, scriptInfo.attributes[key]);
            });

            if (scriptInfo.src) {
                // External script
                newScript.src = scriptInfo.src;
                newScript.async = true;
            } else if (scriptInfo.inline) {
                // Inline script
                newScript.textContent = scriptInfo.inline;
            }

            // Append to document
            document.head.appendChild(newScript);
        },

        /**
         * Execute scripts based on consent
         */
        executeBasedOnConsent: function (categories) {
            if (!categories) return;

            Object.entries(categories).forEach(([category, consented]) => {
                if (consented === true) {
                    this.executeCategory(category);
                }
            });
        },

        /**
         * Check and execute scripts on page load if consent exists
         */
        checkAndExecute: function () {
            if (store.consent && store.consent.categories) {
                this.executeBasedOnConsent(store.consent.categories);
            }
        }
    };

    /**
     * Public API
     */
    window.wpsltCookie = {
        /**
         * Check if user has consented to a category
         */
        hasConsent: function (category) {
            if (!store.consent || !store.consent.categories) {
                return false;
            }
            return store.consent.categories[category] === true;
        },

        /**
         * Get all consent data
         */
        getConsent: function () {
            return store.consent;
        },

        /**
         * Open settings modal programmatically
         */
        openSettings: function () {
            handleOpenSettings();
        },

        /**
         * Accept all cookies programmatically
         */
        acceptAll: function () {
            handleAcceptAll();
        },

        /**
         * Reject all cookies programmatically
         */
        rejectAll: function () {
            handleRejectAll();
        },

        /**
         * Reset consent (show banner again)
         */
        reset: function () {
            Cookie.erase(settings.cookieName);
            store.consent = null;
            hideRevisit();
            hideModal();
            const initialStates = getInitialCategoryStates();
            updateCheckboxes(initialStates);
            showBanner();
        },

        /**
         * Manually execute scripts for a category
         */
        executeScripts: function (category) {
            ScriptBlocker.executeCategory(category);
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

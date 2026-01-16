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
        categories: [],
        blockPatterns: [],
        autoBlock: true
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
         * Store blocked script URLs (for URL pattern blocking)
         */
        blockedUrls: new Map(),

        /**
         * Observer for new scripts
         */
        observer: null,

        /**
         * Initialize script blocking
         * Find all scripts with data-wpslt-category and block them
         */
        init: function () {
            // Initialize URL pattern blocking FIRST (before any scripts load)
            this.initUrlPatternBlocking();

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
         * Initialize URL Pattern Blocking
         * Uses MutationObserver to intercept scripts before they load
         */
        initUrlPatternBlocking: function () {
            const patterns = settings.blockPatterns || [];
            if (patterns.length === 0) return;

            // Create a MutationObserver to watch for new script elements
            this.observer = new MutationObserver((mutations) => {
                mutations.forEach(mutation => {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeName === 'SCRIPT') {
                            this.handleNewScript(node);
                        }
                    });
                });
            });

            // Start observing
            this.observer.observe(document.documentElement, {
                childList: true,
                subtree: true
            });

            // Also check existing scripts
            document.querySelectorAll('script[src]').forEach(script => {
                this.handleExistingScript(script);
            });
        },

        /**
         * Handle newly added script
         */
        handleNewScript: function (script) {
            const src = script.src || script.getAttribute('src');
            if (!src) return;

            // Skip if already processed
            if (script.hasAttribute('data-wpslt-processed')) return;
            script.setAttribute('data-wpslt-processed', 'true');

            // Check if script matches any block pattern
            const matchedPattern = this.matchesBlockPattern(src);
            if (matchedPattern) {
                // Check if we have consent for this category
                const hasConsent = store.consent &&
                    store.consent.categories &&
                    store.consent.categories[matchedPattern.category] === true;

                if (!hasConsent) {
                    // Block the script
                    this.blockScript(script, matchedPattern.category, src);
                }
            }
        },

        /**
         * Handle existing script (check and potentially block on next load)
         */
        handleExistingScript: function (script) {
            const src = script.src || script.getAttribute('src');
            if (!src) return;

            // Skip if already processed
            if (script.hasAttribute('data-wpslt-processed')) return;
            script.setAttribute('data-wpslt-processed', 'true');

            // Check if script matches any block pattern
            const matchedPattern = this.matchesBlockPattern(src);
            if (matchedPattern) {
                // Store for reference (can't block already loaded scripts)
                this.blockedUrls.set(src, {
                    category: matchedPattern.category,
                    loaded: true
                });
            }
        },

        /**
         * Block a script from loading
         */
        blockScript: function (script, category, src) {
            // Remove the script element before it loads
            if (script.parentNode) {
                script.parentNode.removeChild(script);
            }

            // Store the blocked script info for later execution
            this.blockedScripts.push({
                category: category,
                src: src,
                inline: null,
                attributes: this.getScriptAttributes(script),
                executed: false,
                blockedByPattern: true
            });

            this.blockedUrls.set(src, {
                category: category,
                loaded: false
            });

            console.log('[Cookie Consent] Blocked script:', src, '(Category:', category + ')');
        },

        /**
         * Check if URL matches any block pattern
         */
        matchesBlockPattern: function (url) {
            const patterns = settings.blockPatterns || [];

            for (const item of patterns) {
                if (url.includes(item.pattern)) {
                    return item;
                }
            }

            return null;
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

            // Mark as processed so observer doesn't block it again
            newScript.setAttribute('data-wpslt-processed', 'true');

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

            // Update blockedUrls map
            if (scriptInfo.src) {
                this.blockedUrls.set(scriptInfo.src, {
                    category: scriptInfo.category,
                    loaded: true
                });
            }
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
        },

        /**
         * Get blocked scripts info (for debugging)
         */
        getBlockedScripts: function () {
            return this.blockedScripts.map(s => ({
                category: s.category,
                src: s.src,
                executed: s.executed,
                blockedByPattern: s.blockedByPattern || false
            }));
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
        },

        /**
         * Get list of blocked scripts (for debugging)
         */
        getBlockedScripts: function () {
            return ScriptBlocker.getBlockedScripts();
        },

        /**
         * Check if a specific URL pattern is blocked
         */
        isUrlBlocked: function (url) {
            const pattern = ScriptBlocker.matchesBlockPattern(url);
            if (!pattern) return false;

            const hasConsent = store.consent &&
                store.consent.categories &&
                store.consent.categories[pattern.category] === true;

            return !hasConsent;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

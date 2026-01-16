/**
 * Floating Contact Frontend JavaScript
 *
 * @package WP_Slatan_Theme
 * @since 1.0.2
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'wpslt_fc_state';

    const FloatingContact = {
        container: null,
        button: null,
        isOpen: false,
        defaultState: 'closed',
        rememberState: false,

        init: function () {
            this.container = document.getElementById('wpslt-floating-contact');
            if (!this.container) return;

            this.button = this.container.querySelector('.wpslt-fc-button');
            // If button is missing, we continue initialization as it might remain open (Hide Toggle Button mode)

            // Get settings from data attributes
            this.defaultState = this.container.dataset.defaultState || 'closed';
            this.rememberState = this.container.dataset.rememberState === 'true';

            this.bindEvents();
            this.initState();
        },

        initState: function () {
            // Check if we should remember user preference
            if (this.rememberState) {
                const savedState = localStorage.getItem(STORAGE_KEY);

                if (savedState !== null) {
                    // User has a saved preference, use it
                    this.isOpen = savedState === 'open';
                } else {
                    // No saved preference, use default
                    this.isOpen = this.defaultState === 'open';
                }
            } else {
                // Not remembering state, always use default
                this.isOpen = this.defaultState === 'open';
            }

            // Apply initial state
            this.container.classList.toggle('is-open', this.isOpen);
            if (this.button) {
                this.button.setAttribute('aria-expanded', this.isOpen);
            }
        },

        saveState: function () {
            if (this.rememberState) {
                localStorage.setItem(STORAGE_KEY, this.isOpen ? 'open' : 'closed');
            }
        },

        bindEvents: function () {
            // Toggle on button click
            if (this.button) {
                this.button.addEventListener('click', this.toggle.bind(this));
            }

            // Close on click outside
            document.addEventListener('click', this.handleOutsideClick.bind(this));

            // Close on escape key
            document.addEventListener('keydown', this.handleEscape.bind(this));

            // Handle contact clicks
            const contacts = this.container.querySelectorAll('.wpslt-fc-contact');
            contacts.forEach(function (contact) {
                contact.addEventListener('click', function (e) {
                    // Let the link work naturally
                });
            });
        },

        toggle: function (e) {
            e.preventDefault();
            e.stopPropagation();

            this.isOpen = !this.isOpen;
            this.container.classList.toggle('is-open', this.isOpen);
            if (this.button) {
                this.button.setAttribute('aria-expanded', this.isOpen);
            }

            // Save state to localStorage if enabled
            this.saveState();
        },

        close: function () {
            // If no button, we cannot re-open, so do not allow closing
            if (!this.button) return;

            this.isOpen = false;
            this.container.classList.remove('is-open');
            if (this.button) {
                this.button.setAttribute('aria-expanded', 'false');
            }

            // Save state to localStorage if enabled
            this.saveState();
        },

        handleOutsideClick: function (e) {
            if (!this.isOpen) return;
            // If no button, do not close on outside click (Always Open mode)
            if (!this.button) return;

            if (this.container.contains(e.target)) return;
            this.close();
        },

        handleEscape: function (e) {
            // If no button, do not close on escape
            if (!this.button) return;

            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            FloatingContact.init();
        });
    } else {
        FloatingContact.init();
    }

})();

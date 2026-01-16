/**
 * WP Slatan Theme - Admin Base JavaScript
 * Core functionality for admin settings
 * 
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // WPSLT Admin namespace
    window.WPSLTAdmin = window.WPSLTAdmin || {};

    /**
     * Initialize tabs functionality
     */
    WPSLTAdmin.initTabs = function () {
        $('.wpslt-tab').on('click', function () {
            var tab = $(this).data('tab');

            // Update active tab
            $('.wpslt-tab').removeClass('active');
            $(this).addClass('active');

            // Show corresponding content
            $('.wpslt-tab-content').removeClass('active');
            $('#wpslt-tab-' + tab).addClass('active');

            // Trigger custom event for tab-specific JS
            $(document).trigger('wpslt:tab:changed', [tab]);
        });
    };

    /**
     * Initialize toggle switches
     */
    WPSLTAdmin.initToggles = function () {
        // Toggle switch click handler
        $('.wpslt-toggle-slider').on('click', function () {
            var checkbox = $(this).siblings('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        // Animate toggle on change
        $('input[type="checkbox"]').on('change', function () {
            var toggle = $(this).closest('.wpslt-toggle');
            if (toggle.length) {
                toggle.addClass('animating');
                setTimeout(function () {
                    toggle.removeClass('animating');
                }, 300);
            }
        });
    };

    /**
     * Initialize all base functionality
     */
    WPSLTAdmin.init = function () {
        this.initTabs();
        this.initToggles();
    };

    // Initialize on document ready
    $(document).ready(function () {
        WPSLTAdmin.init();
    });

})(jQuery);

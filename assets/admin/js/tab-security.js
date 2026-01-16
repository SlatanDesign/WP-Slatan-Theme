/**
 * WP Slatan Theme - Security Tab JavaScript
 * JavaScript specific to the Security settings tab
 * 
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // Extend WPSLTAdmin namespace
    WPSLTAdmin.Security = WPSLTAdmin.Security || {};

    /**
     * Initialize Security tab functionality
     */
    WPSLTAdmin.Security.init = function () {
        // Security tab specific initialization
    };

    // Initialize on document ready
    $(document).ready(function () {
        WPSLTAdmin.Security.init();
    });

    // Listen for tab changes
    $(document).on('wpslt:tab:changed', function (e, tab) {
        if (tab === 'security') {
            // Tab became active
        }
    });

})(jQuery);

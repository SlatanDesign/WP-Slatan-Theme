/**
 * WP Slatan Theme - General Tab JavaScript
 * JavaScript specific to the General settings tab
 * 
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // Extend WPSLTAdmin namespace
    WPSLTAdmin.General = WPSLTAdmin.General || {};

    /**
     * Initialize General tab functionality
     */
    WPSLTAdmin.General.init = function () {
        // General tab specific initialization
    };

    // Initialize on document ready
    $(document).ready(function () {
        WPSLTAdmin.General.init();
    });

    // Listen for tab changes
    $(document).on('wpslt:tab:changed', function (e, tab) {
        if (tab === 'general') {
            // Tab became active
        }
    });

})(jQuery);

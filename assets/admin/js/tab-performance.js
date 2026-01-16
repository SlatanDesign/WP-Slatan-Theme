/**
 * WP Slatan Theme - Performance Tab JavaScript
 * JavaScript specific to the Performance settings tab
 * 
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // Extend WPSLTAdmin namespace
    WPSLTAdmin.Performance = WPSLTAdmin.Performance || {};

    /**
     * Initialize Performance tab functionality
     */
    WPSLTAdmin.Performance.init = function () {
        // Performance tab specific initialization
    };

    // Initialize on document ready
    $(document).ready(function () {
        WPSLTAdmin.Performance.init();
    });

    // Listen for tab changes
    $(document).on('wpslt:tab:changed', function (e, tab) {
        if (tab === 'performance') {
            // Tab became active
        }
    });

})(jQuery);

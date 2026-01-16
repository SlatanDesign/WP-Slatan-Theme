/**
 * Cookie Consent Admin JavaScript
 *
 * @package WP_Slatan_Theme
 * @since 1.0.22
 */

(function ($) {
    'use strict';

    // Configuration
    const config = window.wpsltCookieAdmin || {};
    let categoryIndex = 0;

    /**
     * Initialize
     */
    function init() {
        initTabs();
        initColorPickers();
        initCategories();
        initPatterns();
        initLivePreview();

        // Initial preview update
        updatePreview();
    }

    /**
     * Initialize tabs
     */
    function initTabs() {
        const $tabButtons = $('.wpslt-cookie-admin .wpslt-tab');
        const $tabContents = $('.wpslt-cookie-admin .wpslt-tab-content');

        $tabButtons.on('click', function (e) {
            e.preventDefault();

            const tab = $(this).data('tab');

            $tabButtons.removeClass('active');
            $(this).addClass('active');

            $tabContents.removeClass('active');
            $(`#wpslt-tab-${tab}`).addClass('active');
        });
    }

    /**
     * Initialize color pickers
     */
    function initColorPickers() {
        $('.wpslt-color-picker').wpColorPicker({
            change: function (event, ui) {
                $(this).val(ui.color.toString());
                // Debounce preview update
                clearTimeout(window.colorPickerTimeout);
                window.colorPickerTimeout = setTimeout(updatePreview, 100);
            },
            clear: function () {
                updatePreview();
            }
        });
    }

    /**
     * Initialize categories
     */
    function initCategories() {
        // Set initial category index
        categoryIndex = $('#wpslt-categories-list .wpslt-category-item').length;

        // Toggle category
        $(document).on('click', '.wpslt-category-toggle, .wpslt-category-title', function () {
            $(this).closest('.wpslt-category-item').toggleClass('open');
        });

        // Delete category
        $(document).on('click', '.wpslt-category-delete', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (confirm(config.i18n?.confirmDelete || 'Are you sure you want to delete this category?')) {
                $(this).closest('.wpslt-category-item').remove();
                reindexCategories();
            }
        });

        // Add category
        $('#wpslt-add-category').on('click', function () {
            addCategory();
        });

        // Update title on name change
        $(document).on('input', '.wpslt-category-body input[id*="cat_name"]', function () {
            const $item = $(this).closest('.wpslt-category-item');
            $item.find('.wpslt-category-title').text($(this).val() || 'New Category');
        });
    }

    /**
     * Add new category
     */
    function addCategory() {
        const id = 'category_' + Date.now();
        const optionName = 'wpslt_cookie_consent_settings';

        const html = `
            <div class="wpslt-category-item open" data-index="${categoryIndex}">
                <div class="wpslt-category-header">
                    <span class="wpslt-category-title">New Category</span>
                    <button type="button" class="wpslt-category-delete button-link button-link-delete">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                    <button type="button" class="wpslt-category-toggle">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                </div>
                <div class="wpslt-category-body">
                    <input type="hidden" 
                           name="${optionName}[categories][${categoryIndex}][id]" 
                           value="${id}">
                    <input type="hidden" 
                           name="${optionName}[categories][${categoryIndex}][is_necessary]" 
                           value="0">
                    
                    <p>
                        <label for="wpslt_cat_name_${categoryIndex}">Name</label>
                        <input type="text" 
                               id="wpslt_cat_name_${categoryIndex}" 
                               name="${optionName}[categories][${categoryIndex}][name]" 
                               value="" 
                               class="widefat"
                               placeholder="Category Name">
                    </p>
                    <p>
                        <label for="wpslt_cat_desc_${categoryIndex}">Description</label>
                        <textarea id="wpslt_cat_desc_${categoryIndex}" 
                                  name="${optionName}[categories][${categoryIndex}][description]" 
                                  rows="2" 
                                  class="widefat"
                                  placeholder="Describe what cookies in this category are used for"></textarea>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" 
                                   name="${optionName}[categories][${categoryIndex}][default_state]" 
                                   value="1">
                            Enabled by default
                        </label>
                    </p>
                </div>
            </div>
        `;

        $('#wpslt-categories-list').append(html);
        categoryIndex++;

        // Focus on name input
        $(`#wpslt_cat_name_${categoryIndex - 1}`).focus();
    }

    /**
     * Reindex categories after deletion
     */
    function reindexCategories() {
        const optionName = 'wpslt_cookie_consent_settings';

        $('#wpslt-categories-list .wpslt-category-item').each(function (index) {
            $(this).attr('data-index', index);

            // Update all input names
            $(this).find('input, textarea').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[categories\]\[\d+\]/, `[categories][${index}]`);
                    $(this).attr('name', newName);
                }

                const id = $(this).attr('id');
                if (id && id.includes('wpslt_cat_')) {
                    const newId = id.replace(/_\d+$/, `_${index}`);
                    $(this).attr('id', newId);
                    $(this).closest('p').find('label').attr('for', newId);
                }
            });
        });

        categoryIndex = $('#wpslt-categories-list .wpslt-category-item').length;
    }

    /**
     * Initialize URL Pattern management
     */
    function initPatterns() {
        let patternIndex = $('#wpslt-block-patterns .wpslt-pattern-row').length;

        // Add pattern
        $('#wpslt-add-pattern').on('click', function () {
            const optionName = 'wpslt_cookie_consent_settings';
            const html = `
                <div class="wpslt-pattern-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                    <input type="text"
                        name="${optionName}[block_patterns][${patternIndex}][pattern]"
                        value=""
                        class="regular-text"
                        placeholder="e.g., hotjar.com">
                    <select name="${optionName}[block_patterns][${patternIndex}][category]">
                        <option value="analytics">Analytics</option>
                        <option value="marketing">Marketing</option>
                    </select>
                    <button type="button" class="button wpslt-remove-pattern">
                        <span class="dashicons dashicons-no-alt" style="vertical-align: middle;"></span>
                    </button>
                </div>
            `;
            $('#wpslt-block-patterns').append(html);
            patternIndex++;
        });

        // Remove pattern
        $(document).on('click', '.wpslt-remove-pattern', function () {
            $(this).closest('.wpslt-pattern-row').remove();
            reindexPatterns();
        });
    }

    /**
     * Reindex patterns after deletion
     */
    function reindexPatterns() {
        const optionName = 'wpslt_cookie_consent_settings';

        $('#wpslt-block-patterns .wpslt-pattern-row').each(function (index) {
            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    const newName = name.replace(/\[block_patterns\]\[\d+\]/, `[block_patterns][${index}]`);
                    $(this).attr('name', newName);
                }
            });
        });
    }

    /**
     * Initialize live preview with ALL settings
     */
    function initLivePreview() {
        // Banner Type
        $('select[name*="[banner_type]"]').on('change', function () {
            updatePreview();
        });

        // Animation
        $('select[name*="[animation]"]').on('change', function () {
            updatePreview();
        });

        // All color inputs - bind to the hidden input that wpColorPicker updates
        $('input[name*="_color"]').on('change', function () {
            updatePreview();
        });

        // All text inputs
        $('input[name*="[title]"], input[name*="[accept_text]"], input[name*="[reject_text]"], input[name*="[settings_text]"]').on('input', function () {
            updatePreview();
        });

        // Textarea for message
        $('textarea[name*="[message]"]').on('input', function () {
            updatePreview();
        });

        // Initial binding for already rendered color pickers
        $('.wp-picker-container').on('click', function () {
            setTimeout(updatePreview, 100);
        });
    }

    /**
     * Update preview - comprehensive update for all settings
     */
    function updatePreview() {
        const $preview = $('#wpslt-banner-preview');
        if (!$preview.length) return;

        // Get values from form (try visible input, then hidden input)
        const getValue = (selector) => {
            const $el = $(selector);
            if ($el.length) {
                // For color pickers, get the value from the hidden input
                if ($el.hasClass('wpslt-color-picker')) {
                    return $el.val();
                }
                return $el.val();
            }
            return '';
        };

        // Banner Type
        const bannerType = getValue('select[name*="[banner_type]"]') || 'bar-bottom';
        $preview.attr('data-type', bannerType);

        // Animation
        const animation = getValue('select[name*="[animation]"]') || 'slide';
        $preview.attr('data-animation', animation);

        // Banner Colors
        const bannerBgColor = getValue('input[name*="[banner_bg_color]"]') || '#1a1a2e';
        const bannerTextColor = getValue('input[name*="[banner_text_color]"]') || '#ffffff';
        $preview.css({
            'background-color': bannerBgColor,
            'color': bannerTextColor
        });

        // Accept Button
        const acceptBgColor = getValue('input[name*="[accept_bg_color]"]') || '#16a085';
        const acceptTextColor = getValue('input[name*="[accept_text_color]"]') || '#ffffff';
        const acceptText = getValue('input[name*="[accept_text]"]') || 'Accept All';
        $preview.find('.wpslt-cookie-accept').css({
            'background-color': acceptBgColor,
            'color': acceptTextColor
        }).text(acceptText);

        // Reject Button
        const rejectBgColor = getValue('input[name*="[reject_bg_color]"]') || '#e74c3c';
        const rejectTextColor = getValue('input[name*="[reject_text_color]"]') || '#ffffff';
        const rejectText = getValue('input[name*="[reject_text]"]') || 'Reject All';
        $preview.find('.wpslt-cookie-reject').css({
            'background-color': rejectBgColor,
            'color': rejectTextColor
        }).text(rejectText);

        // Settings Button
        const settingsTextColor = getValue('input[name*="[settings_text_color]"]') || '#ffffff';
        const settingsText = getValue('input[name*="[settings_text]"]') || 'Cookie Settings';
        $preview.find('.wpslt-cookie-settings').css({
            'color': settingsTextColor,
            'border-color': settingsTextColor
        }).text(settingsText);

        // Title and Message
        const title = getValue('input[name*="[title]"]') || 'We value your privacy';
        const message = getValue('textarea[name*="[message]"]') || 'We use cookies to enhance your browsing experience.';
        $preview.find('#preview-title').text(title);
        $preview.find('#preview-message').text(message);

        // Apply animation class
        $preview.removeClass('preview-anim-slide preview-anim-fade preview-anim-none');
        $preview.addClass('preview-anim-' + animation);
    }

    // Initialize when ready
    $(document).ready(init);

})(jQuery);


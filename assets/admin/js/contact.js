/**
 * Floating Contact Admin JavaScript
 *
 * @package WP_Slatan_Theme
 * @since 1.0.2
 */

(function ($) {
    'use strict';

    const WPSLTContact = {
        contactIndex: 0,
        presetTypes: {},

        init: function () {
            this.presetTypes = wpsltContact.presetTypes || {};
            this.bindEvents();
            this.initSortable();
            this.initColorPickers();
            this.initPageDisplay();
            this.initConditionalFields();
            this.updateContactIndex();
            this.initPreview();
            this.initIconPicker();
            this.initMediaUploader();
        },

        initIconPicker: function () {
            // Inject Drawer HTML
            const drawerHtml = `
				<div class="wpslt-icon-drawer-overlay">
					<div class="wpslt-icon-drawer">
						<div class="wpslt-icon-drawer-header">
							<h3>${wpsltContact.i18n.selectIcon || 'Select Icon'}</h3>
							<button type="button" class="wpslt-icon-drawer-close">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						</div>
						<div class="wpslt-icon-search-box">
							<input type="text" id="wpslt-icon-search" placeholder="Search icons...">
							<span class="dashicons dashicons-search"></span>
						</div>
						<div class="wpslt-icon-grid">
							<!-- Icons loaded here -->
						</div>
					</div>
				</div>
			`;
            $('body').append(drawerHtml);

            this.$drawer = $('.wpslt-icon-drawer-overlay');
            this.$iconGrid = $('.wpslt-icon-grid');
            this.$searchInput = $('#wpslt-icon-search');
            this.activeInput = null;

            this.loadIcons();
            this.bindIconPickerEvents();
        },

        loadIcons: function () {
            if (typeof wpsltIcons === 'undefined') return;

            let html = '';
            // Brands
            if (wpsltIcons.brands) {
                wpsltIcons.brands.forEach(icon => {
                    const name = icon.replace('fab fa-', '');
                    html += `<div class="wpslt-icon-item" data-icon="${icon}">
						<i class="${icon}"></i>
						<span class="wpslt-icon-name">${name}</span>
					</div>`;
                });
            }
            // Solid
            if (wpsltIcons.solid) {
                wpsltIcons.solid.forEach(icon => {
                    const name = icon.replace('fas fa-', '');
                    html += `<div class="wpslt-icon-item" data-icon="${icon}">
						<i class="${icon}"></i>
						<span class="wpslt-icon-name">${name}</span>
					</div>`;
                });
            }

            this.$iconGrid.html(html);
        },

        bindIconPickerEvents: function () {
            const self = this;

            // Open Drawer
            $(document).on('click', '.wpslt-icon-picker-btn', function (e) {
                e.preventDefault();
                self.activeInput = $(this).siblings('.wpslt-icon-picker-input');
                const currentIcon = self.activeInput.val();

                // Highlight active icon
                self.$iconGrid.find('.wpslt-icon-item').removeClass('active');
                if (currentIcon) {
                    self.$iconGrid.find(`[data-icon="${currentIcon}"]`).addClass('active');
                }

                // Reset search
                self.$searchInput.val('').trigger('input');
                self.$drawer.addClass('open');
                self.$searchInput.focus();
            });

            // Close Drawer
            $('.wpslt-icon-drawer-close, .wpslt-icon-drawer-overlay').on('click', function (e) {
                if (e.target === this || $(this).hasClass('wpslt-icon-drawer-close') || $(this).parent().hasClass('wpslt-icon-drawer-close')) {
                    self.$drawer.removeClass('open');
                }
            });

            // Search Filter
            this.$searchInput.on('input', function () {
                const term = $(this).val().toLowerCase();
                const $items = self.$iconGrid.find('.wpslt-icon-item');

                if (!term) {
                    $items.show();
                    return;
                }

                $items.each(function () {
                    const name = $(this).find('.wpslt-icon-name').text();
                    if (name.includes(term)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Select Icon
            $(document).on('click', '.wpslt-icon-item', function () {
                const icon = $(this).data('icon');
                if (self.activeInput) {
                    self.activeInput.val(icon).trigger('change');
                    // Update preview box
                    const $preview = self.activeInput.siblings('.wpslt-icon-preview').find('i');
                    $preview.attr('class', icon);
                }
                self.$drawer.removeClass('open');
            });

            // Manual Input Change -> Update Preview Box
            $(document).on('input change', '.wpslt-icon-picker-input', function () {
                const icon = $(this).val();
                const $preview = $(this).siblings('.wpslt-icon-preview').find('i');
                // Basic validation or fallback? For now just trusting user input or empty
                if (icon) {
                    $preview.attr('class', icon);
                } else {
                    $preview.attr('class', '');
                }
                WPSLTContact.updatePreview();
            });
        },

        initMediaUploader: function () {
            const self = this;

            // Click on box to upload (not the remove button)
            $(document).on('click', '.wpslt-custom-icon-box', function (e) {
                e.preventDefault();
                const $wrapper = $(this).closest('.wpslt-custom-icon-wrapper');
                const $input = $wrapper.find('.wpslt-custom-icon-input');
                const $box = $wrapper.find('.wpslt-custom-icon-box');

                const frame = wp.media({
                    title: wpsltContact.i18n.selectImage || 'Select Image',
                    button: { text: wpsltContact.i18n.useImage || 'Use this image' },
                    multiple: false,
                    library: { type: ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'] }
                });

                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.url).trigger('change');
                    // Add image to box, remove any existing image first
                    $box.find('img').remove();
                    $box.append('<img src="' + attachment.url + '" alt="">');
                    $wrapper.addClass('has-image');
                    self.updatePreview();
                });

                frame.open();
            });

            // Remove Button Click
            $(document).on('click', '.wpslt-remove-icon-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $wrapper = $(this).closest('.wpslt-custom-icon-wrapper');
                $wrapper.find('.wpslt-custom-icon-input').val('').trigger('change');
                $wrapper.find('.wpslt-custom-icon-box img').remove();
                $wrapper.removeClass('has-image');
                self.updatePreview();
            });
        },

        initConditionalFields: function () {
            this.toggleAnimationParams();
            this.toggleCustomSizeFields();
            this.toggleMobilePositionFields();
            this.toggleMobileSizeFields();
        },

        bindEvents: function () {
            // Tab switching
            $('.wpslt-tabs .wpslt-tab').on('click', this.switchTab.bind(this));

            // Add contact
            $('#wpslt-add-contact').on('click', this.addContact.bind(this));

            // Delete contact
            $(document).on('click', '.wpslt-contact-delete', this.deleteContact.bind(this));

            // Toggle contact
            $(document).on('click', '.wpslt-contact-toggle, .wpslt-contact-title', this.toggleContact.bind(this));

            // Update title on label change
            $(document).on('input', '.wpslt-contact-label', this.updateContactTitle.bind(this));

            // Page display change
            $('#wpslt-display-pages').on('change', this.togglePageIds.bind(this));

            // Live preview updates
            $(document).on('change input', '#wpslt-contact-form input, #wpslt-contact-form select', this.updatePreview.bind(this));

            // Toggle animation parameter visibility
            $('[name="wpslt_floating_contact[style][toggle_animation]"]').on('change', this.toggleAnimationParams.bind(this));

            // Toggle custom contact sizing fields
            $('[name="wpslt_floating_contact[style][use_custom_contact_size]"]').on('change', this.toggleCustomSizeFields.bind(this));

            // Toggle mobile position override fields
            $('[name="wpslt_floating_contact[mobile][override_position]"]').on('change', this.toggleMobilePositionFields.bind(this));

            // Toggle mobile size override fields
            $('[name="wpslt_floating_contact[mobile][override_size]"]').on('change', this.toggleMobileSizeFields.bind(this));
        },

        toggleAnimationParams: function () {
            const toggleAnim = $('[name="wpslt_floating_contact[style][toggle_animation]"]').val() || 'rotate';
            $('.wpslt-toggle-param').hide();
            if (toggleAnim === 'rotate') {
                $('.wpslt-toggle-param-rotate').show();
            } else if (toggleAnim === 'flip') {
                $('.wpslt-toggle-param-flip').show();
            } else if (toggleAnim === 'scale-rotate') {
                $('.wpslt-toggle-param-scale-rotate').show();
            }
        },

        toggleCustomSizeFields: function () {
            const isEnabled = $('[name="wpslt_floating_contact[style][use_custom_contact_size]"]').is(':checked');
            const $fields = $('.wpslt-custom-size-fields');

            if (isEnabled) {
                $fields.show();
                $fields.find('input, select').prop('disabled', false);
            } else {
                $fields.hide();
                $fields.find('input, select').prop('disabled', true);
            }
            this.updatePreview();
        },

        toggleMobilePositionFields: function () {
            const isEnabled = $('[name="wpslt_floating_contact[mobile][override_position]"]').is(':checked');
            const $fields = $('.wpslt-mobile-position-fields');

            if (isEnabled) {
                $fields.show();
                $fields.find('input, select').prop('disabled', false);
            } else {
                $fields.hide();
                $fields.find('input, select').prop('disabled', true);
            }
            this.updatePreview();
        },

        toggleMobileSizeFields: function () {
            const isEnabled = $('[name="wpslt_floating_contact[mobile][override_size]"]').is(':checked');
            const $fields = $('.wpslt-mobile-size-fields');

            if (isEnabled) {
                $fields.show();
                $fields.find('input, select').prop('disabled', false);
            } else {
                $fields.hide();
                $fields.find('input, select').prop('disabled', true);
            }
            this.updatePreview();
        },

        switchTab: function (e) {
            e.preventDefault();
            const $tab = $(e.currentTarget);
            const tabId = $tab.data('tab');

            // Update tab buttons
            $('.wpslt-tabs .wpslt-tab').removeClass('active');
            $tab.addClass('active');

            // Update tab content
            $('.wpslt-tab-content').removeClass('active');
            $('#wpslt-tab-' + tabId).addClass('active');
        },

        initSortable: function () {
            $('#wpslt-contacts-list').sortable({
                handle: '.wpslt-contact-drag',
                placeholder: 'wpslt-contact-item ui-sortable-placeholder',
                update: function (event, ui) {
                    WPSLTContact.updateOrder();
                    WPSLTContact.updatePreview();
                }
            });
        },

        initColorPickers: function () {
            $('.wpslt-color-picker').each(function () {
                const $input = $(this);
                $input.wpColorPicker({
                    change: function (event, ui) {
                        const color = ui.color.toString();
                        WPSLTContact.updateLocalPreview($input, color);
                        setTimeout(function () {
                            WPSLTContact.updatePreview();
                        }, 100);
                    },
                    clear: function () {
                        WPSLTContact.updateLocalPreview($input, '');
                        setTimeout(function () {
                            WPSLTContact.updatePreview();
                        }, 100);
                    }
                });

                // Init initial state if value exists
                if ($input.val()) {
                    WPSLTContact.updateLocalPreview($input, $input.val());
                }
            });
        },

        updateLocalPreview: function ($input, color) {
            const name = $input.attr('name');
            if (!name) return;

            // 1. Contact Items
            const $contactItem = $input.closest('.wpslt-contact-item');
            if ($contactItem.length) {
                const $previewBox = $contactItem.find('.wpslt-icon-preview');
                const $previewIcon = $previewBox.find('i');

                // Determine if it's background or icon color based on name
                // name format: ...[contacts][index][color] or ...[contacts][index][icon_color]
                if (name.match(/\[color\]$/)) {
                    $previewBox.css('background-color', color);
                } else if (name.match(/\[icon_color\]$/)) {
                    $previewIcon.css('color', color);
                }
                return;
            }

            // 2. Global Settings (Toggle Buttons)
            const $form = $('#wpslt-contact-form');

            if (name.includes('[style][primary_color]')) {
                // Open Button BG -> Open Icon Preview
                const $openIconInput = $form.find('[name*="[style][open_icon]"]');
                const $openPreview = $openIconInput.siblings('.wpslt-icon-preview');
                $openPreview.css('background-color', color);
            } else if (name.includes('[style][open_icon_color]')) {
                // Open Icon Color
                const $openIconInput = $form.find('[name*="[style][open_icon]"]');
                const $openPreview = $openIconInput.siblings('.wpslt-icon-preview').find('i');
                $openPreview.css('color', color);
            } else if (name.includes('[style][close_bg_color]')) {
                // Close Button BG
                const $closeIconInput = $form.find('[name*="[style][close_icon]"]');
                const $closePreview = $closeIconInput.siblings('.wpslt-icon-preview');
                $closePreview.css('background-color', color);
            } else if (name.includes('[style][close_icon_color]')) {
                // Close Icon Color
                const $closeIconInput = $form.find('[name*="[style][close_icon]"]');
                const $closePreview = $closeIconInput.siblings('.wpslt-icon-preview').find('i');
                $closePreview.css('color', color);
            }
        },

        initPageDisplay: function () {
            this.togglePageIds();
        },

        updateContactIndex: function () {
            const items = $('.wpslt-contact-item');
            this.contactIndex = items.length;
        },

        addContact: function (e) {
            e.preventDefault();

            const type = $('#wpslt-contact-type').val();
            const preset = this.presetTypes[type] || {};
            const index = this.contactIndex++;

            const contact = {
                id: 'contact_' + Date.now(),
                type: type,
                label: preset.label || 'Custom',
                value: '',
                icon: preset.icon || 'fas fa-link',
                color: preset.color || '#666666',
                tooltip: '',
                order: index
            };

            const template = wp.template('wpslt-contact-item');
            const html = template({ index: index, contact: contact });

            // Replace template placeholders with actual values
            let $html = $(html);
            $html.find('[name*="[type]"]').val(contact.type);
            $html.find('[name*="[id]"]').val(contact.id);
            $html.find('[name*="[label]"]').val(contact.label);
            $html.find('[name*="[icon]"]').val(contact.icon);
            $html.find('[name*="[color]"]').not('[name*="icon_color"]').val(contact.color);
            $html.find('[name*="[icon_color]"]').val('#ffffff');
            $html.find('[name*="[order]"]').val(contact.order);
            $html.find('.wpslt-contact-title').text(contact.label);
            $html.find('.wpslt-contact-icon i').attr('class', contact.icon);
            $html.find('.wpslt-contact-icon').css('color', contact.color);

            $('#wpslt-contacts-list').append($html);

            // Initialize color picker for new item
            $html.find('.wpslt-color-picker').each(function () {
                const $input = $(this);
                $input.wpColorPicker({
                    change: function (event, ui) {
                        const color = ui.color.toString();
                        WPSLTContact.updateLocalPreview($input, color);
                        setTimeout(function () {
                            WPSLTContact.updatePreview();
                        }, 100);
                    },
                    clear: function () {
                        WPSLTContact.updateLocalPreview($input, '');
                        setTimeout(function () {
                            WPSLTContact.updatePreview();
                        }, 100);
                    }
                });

                // Init initial state
                if ($input.val()) {
                    WPSLTContact.updateLocalPreview($input, $input.val());
                }
            });

            // Collapse previous items and expand new one
            $('.wpslt-contact-item').addClass('collapsed');
            $html.removeClass('collapsed');

            this.updateOrder();
            this.updatePreview();
        },

        deleteContact: function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (!confirm(wpsltContact.i18n.deleteConfirm)) {
                return;
            }

            $(e.currentTarget).closest('.wpslt-contact-item').remove();
            this.updateOrder();
            this.updatePreview();
        },

        toggleContact: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.wpslt-contact-item');
            $item.toggleClass('collapsed');
        },

        updateContactTitle: function (e) {
            const $input = $(e.currentTarget);
            const $item = $input.closest('.wpslt-contact-item');
            const label = $input.val() || 'New Contact';
            $item.find('.wpslt-contact-title').text(label);
        },

        updateOrder: function () {
            $('.wpslt-contact-item').each(function (index) {
                $(this).find('.wpslt-contact-order').val(index);

                // Update name attributes with new index
                $(this).find('[name]').each(function () {
                    const name = $(this).attr('name');
                    const newName = name.replace(/\[contacts\]\[\d+\]/, '[contacts][' + index + ']');
                    $(this).attr('name', newName);
                });
            });
        },

        togglePageIds: function () {
            const value = $('#wpslt-display-pages').val();
            if (value === 'all') {
                $('#wpslt-page-ids-row').hide();
            } else {
                $('#wpslt-page-ids-row').show();
            }
        },

        initPreview: function () {
            this.updatePreview();
        },

        updatePreview: function () {
            const $preview = $('#wpslt-preview-widget');
            const settings = this.getFormSettings();

            // Build preview HTML matching frontend structure
            const gapValue = settings.spacing.contact_gap + settings.spacing.contact_gap_unit;
            let html = '<div class="wpslt-fc-preview';

            // Add classes for new settings
            if (settings.tooltip.display_mode === 'always') {
                html += ' wpslt-tooltip-always';
            }
            if (!settings.style.show_toggle_button) {
                html += ' wpslt-hide-toggle-button';
            }
            if (settings.tooltip.show_mobile) {
                html += ' wpslt-tooltip-mobile-visible';
            }

            html += '" style="position: absolute; display: flex; flex-direction: column; align-items: center; gap: ' + gapValue + '; ';

            // Position
            if (settings.position.horizontal === 'right') {
                html += 'right: 10px; ';
            } else {
                html += 'left: 10px; ';
            }
            if (settings.position.vertical === 'bottom') {
                html += 'bottom: 10px; flex-direction: column-reverse; ';
            } else {
                html += 'top: 10px; ';
            }
            html += '">';

            // Main button with open/close icons
            const btnSize = settings.style.button_size + settings.style.button_size_unit;
            const iconSize = settings.style.icon_size + settings.style.icon_size_unit;
            const borderRadius = settings.style.border_radius + settings.style.border_radius_unit;
            const boxShadow = settings.style.box_shadow ? '0 4px 15px rgba(0,0,0,0.15)' : 'none';
            const openIcon = settings.style.open_icon || 'fas fa-comment-dots';
            const closeIcon = settings.style.close_icon || 'fas fa-times';
            const openCustomIcon = settings.style.open_custom_icon || '';
            const closeCustomIcon = settings.style.close_custom_icon || '';
            const closeBgColor = settings.style.close_bg_color || settings.style.primary_color;
            const closeIconColor = settings.style.close_icon_color || '#fff';
            const closeTooltipText = settings.style.close_tooltip_text || 'Contact Us';

            // Tooltip positioning logic
            let tooltipPosValue = settings.tooltip.position;
            if (!tooltipPosValue || tooltipPosValue === 'auto') {
                tooltipPosValue = settings.position.horizontal === 'right' ? 'left' : 'right';
            }

            // Tooltip CSS with explicit resets and transitions
            let tooltipCss = 'position: absolute; white-space: nowrap; padding: 6px 12px; font-size: 13px; opacity: 0; transition: opacity 0.2s; z-index: 10; ';
            tooltipCss += 'background: ' + (settings.tooltip.background || '#333333') + '; ';
            tooltipCss += 'color: ' + (settings.tooltip.text_color || '#ffffff') + '; ';
            tooltipCss += 'top: auto; bottom: auto; left: auto; right: auto; margin: 0; ';

            // Calculate position styles using calc() to match frontend CSS
            switch (tooltipPosValue) {
                case 'left':
                    tooltipCss += 'right: calc(100% + 10px); top: 50%; transform: translateY(-50%);';
                    break;
                case 'right':
                    tooltipCss += 'left: calc(100% + 10px); top: 50%; transform: translateY(-50%);';
                    break;
                default: // Fallback to auto logic (usually left for right-aligned widget)
                    if (settings.position.horizontal === 'right') {
                        tooltipCss += 'right: calc(100% + 10px); top: 50%; transform: translateY(-50%);';
                    } else {
                        tooltipCss += 'left: calc(100% + 10px); top: 50%; transform: translateY(-50%);';
                    }
            }

            // Animation classes
            const buttonAnimation = settings.style.animation && settings.style.animation !== 'none' ? 'wpslt-animation-' + settings.style.animation : '';
            const hoverAnimation = settings.style.hover_animation && settings.style.hover_animation !== 'none' ? 'wpslt-hover-' + settings.style.hover_animation : '';
            const toggleAnimation = settings.style.toggle_animation ? 'wpslt-fc-toggle-' + settings.style.toggle_animation : 'wpslt-fc-toggle-rotate';

            // Get default state early for button styling
            const defaultStateVal = settings.style.default_state || 'closed';
            const isOpenByDefault = defaultStateVal === 'open';
            const openIconColor = settings.style.open_icon_color || '#ffffff';
            const initialBgColor = isOpenByDefault ? closeBgColor : settings.style.primary_color;
            const initialIconColor = isOpenByDefault ? closeIconColor : openIconColor;

            if (settings.style.show_toggle_button) {
                html += '<div class="wpslt-fc-preview-btn ' + buttonAnimation + ' ' + hoverAnimation + ' ' + toggleAnimation + '" data-close-bg="' + closeBgColor + '" data-close-icon-color="' + closeIconColor + '" data-open-bg="' + settings.style.primary_color + '" data-open-icon-color="' + openIconColor + '" data-toggle-animation="' + (settings.style.toggle_animation || 'rotate') + '" style="';
                html += 'width: ' + btnSize + '; ';
                html += 'height: ' + btnSize + '; ';
                html += 'background: ' + initialBgColor + '; ';
                html += 'border-radius: ' + borderRadius + '; ';
                html += 'box-shadow: ' + boxShadow + '; ';
                html += 'display: flex; align-items: center; justify-content: center; ';
                html += 'color: ' + initialIconColor + '; cursor: pointer; flex-shrink: 0; ';
                html += 'transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, color 0.3s ease; ';
                html += 'position: relative;';
                html += '">';
                // Show correct icon based on default state (use isOpenByDefault from earlier)
                const showOpenIcon = !isOpenByDefault;
                // Render open icon (custom image or FontAwesome)
                if (openCustomIcon) {
                    html += '<img class="wpslt-icon-open wpslt-fc-custom-icon" src="' + openCustomIcon + '" alt="" style="width: ' + iconSize + '; height: ' + iconSize + '; object-fit: contain; display: ' + (showOpenIcon ? 'block' : 'none') + ';">';
                } else {
                    html += '<i class="wpslt-icon-open ' + openIcon + '" style="font-size: ' + iconSize + '; display: ' + (showOpenIcon ? 'block' : 'none') + ';"></i>';
                }
                // Render close icon (custom image or FontAwesome)
                if (closeCustomIcon) {
                    html += '<img class="wpslt-icon-close wpslt-fc-custom-icon" src="' + closeCustomIcon + '" alt="" style="width: ' + iconSize + '; height: ' + iconSize + '; object-fit: contain; display: ' + (showOpenIcon ? 'none' : 'block') + ';">';
                } else {
                    html += '<i class="wpslt-icon-close ' + closeIcon + '" style="font-size: ' + iconSize + '; display: ' + (showOpenIcon ? 'none' : 'block') + ';"></i>';
                }

                // Main Button Tooltip
                if (settings.tooltip.enabled && closeTooltipText) {
                    const tooltipFontSize = settings.tooltip.font_size + settings.tooltip.font_size_unit;
                    const tooltipBorderRadius = settings.tooltip.border_radius + settings.tooltip.border_radius_unit;
                    // Use dynamic border radius and font size
                    let mainTooltipStyle = tooltipCss + 'font-size: ' + tooltipFontSize + '; border-radius: ' + tooltipBorderRadius + ';';
                    if (settings.tooltip.display_mode === 'always') {
                        mainTooltipStyle += ' opacity: 1 !important; visibility: visible !important;';
                    }
                    html += '<span class="wpslt-preview-tooltip" style="' + mainTooltipStyle + '">' + closeTooltipText + '</span>';
                }

                html += '</div>';
            }

            // Contact items preview (show/hide based on default_state OR if toggle button is hidden)
            if (settings.contacts && settings.contacts.length > 0) {
                const showContacts = isOpenByDefault || !settings.style.show_toggle_button;
                html += '<div class="wpslt-fc-preview-contacts" style="display: ' + (showContacts ? 'flex' : 'none') + '; flex-direction: column; align-items: center; gap: ' + gapValue + ';';
                if (settings.position.vertical === 'bottom') {
                    html += ' flex-direction: column-reverse;';
                }
                html += '">';
                settings.contacts.forEach(function (contact) {
                    if (contact.label || contact.icon) {
                        // Use custom contact sizing if enabled
                        let contactSize, contactIconSize;
                        if (settings.style.use_custom_contact_size) {
                            contactSize = settings.style.contact_button_size + settings.style.contact_button_size_unit;
                            contactIconSize = settings.style.contact_icon_size + settings.style.contact_icon_size_unit;
                        } else {
                            contactSize = settings.style.button_size + settings.style.button_size_unit;
                            contactIconSize = settings.style.icon_size + settings.style.icon_size_unit;
                        }
                        const tooltipText = contact.tooltip || contact.label || '';
                        const contactIconColor = contact.icon_color || '#ffffff';
                        html += '<div class="wpslt-fc-preview-contact ' + hoverAnimation + '" data-tooltip="' + tooltipText + '" style="';
                        html += 'position: relative; ';
                        html += 'width: ' + contactSize + '; ';
                        html += 'height: ' + contactSize + '; ';
                        html += 'background: ' + contact.color + '; ';
                        html += 'border-radius: ' + borderRadius + '; ';
                        html += 'box-shadow: ' + boxShadow + '; ';
                        html += 'display: flex; align-items: center; justify-content: center; ';
                        html += 'color: ' + contactIconColor + '; cursor: pointer; ';
                        html += 'transition: transform 0.3s ease, box-shadow 0.3s ease;';
                        html += '">';
                        // Render contact icon (custom image or FontAwesome)
                        if (contact.custom_icon) {
                            html += '<img class="wpslt-fc-custom-icon" src="' + contact.custom_icon + '" alt="" style="width: ' + contactIconSize + '; height: ' + contactIconSize + '; object-fit: contain;">';
                        } else {
                            html += '<i class="' + contact.icon + '" style="font-size: ' + contactIconSize + ';"></i>';
                        }
                        // Tooltip element
                        if (settings.tooltip.enabled && tooltipText) {
                            const tooltipFontSize = settings.tooltip.font_size + settings.tooltip.font_size_unit;
                            const tooltipBorderRadius = settings.tooltip.border_radius + settings.tooltip.border_radius_unit;

                            // Update font size and border radius for individual items based on settings but keep position styles
                            let itemTooltipStyle = tooltipCss + 'font-size: ' + tooltipFontSize + '; border-radius: ' + tooltipBorderRadius + ';';
                            if (settings.tooltip.display_mode === 'always') {
                                itemTooltipStyle += ' opacity: 1 !important; visibility: visible !important;';
                            }
                            html += '<span class="wpslt-preview-tooltip" style="' + itemTooltipStyle + '">' + tooltipText + '</span>';
                        }
                        html += '</div>';
                    }
                });
                html += '</div>';
            }

            html += '</div>';

            $preview.html(html);

            // Bind toggle interaction with menu animation support
            const menuAnimation = settings.style.menu_animation || 'slide';
            const isBottom = settings.position.vertical === 'bottom';

            $preview.off('click', '.wpslt-fc-preview-btn').on('click', '.wpslt-fc-preview-btn', function () {
                const $btn = $(this);
                const $contacts = $btn.siblings('.wpslt-fc-preview-contacts');
                const $contactItems = $contacts.find('.wpslt-fc-preview-contact');
                const isOpen = $contacts.is(':visible');

                if (isOpen) {
                    // Close with animation
                    if (menuAnimation === 'slide') {
                        // Slide: Staggered close animation (reverse order)
                        const totalItems = $contactItems.length;
                        $contactItems.each(function (index) {
                            const $item = $(this);
                            const delay = (totalItems - 1 - index) * 50; // Reverse order delay
                            setTimeout(function () {
                                $item.css({
                                    'transition': 'all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                                    'opacity': '0',
                                    'transform': 'scale(0.5)'
                                });
                            }, delay);
                        });
                        setTimeout(function () {
                            $contacts.hide();
                        }, totalItems * 50 + 300);
                    } else {
                        // Other animations: container-based
                        $contacts.css('transition', 'all 0.3s ease');
                        switch (menuAnimation) {
                            case 'fade':
                                $contacts.css({ 'opacity': '0', 'transform': 'scale(1)' });
                                break;
                            case 'zoom':
                                $contacts.css({ 'opacity': '0', 'transform': 'scale(0)' });
                                break;
                            case 'flip':
                                $contacts.css({ 'opacity': '0', 'transform': 'perspective(400px) rotateX(' + (isBottom ? '90deg' : '-90deg') + ')' });
                                break;
                            case 'bounce':
                                $contacts.css({ 'opacity': '0', 'transform': 'scale(0)' });
                                break;
                        }
                        setTimeout(function () {
                            $contacts.hide();
                        }, 300);
                    }
                    // Apply reverse toggle animation before showing open icon
                    const toggleAnim = $btn.data('toggle-animation') || 'rotate';
                    const toggleRotateDeg = settings.style.toggle_rotate_deg || '180';
                    const toggleFlipAxis = settings.style.toggle_flip_axis || 'Y';
                    const toggleScaleRotateDeg = settings.style.toggle_scale_rotate_deg || '360';
                    const $closeIcon = $btn.find('.wpslt-icon-close');

                    if (toggleAnim === 'rotate') {
                        $closeIcon.css({
                            'transition': 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                            'transform': 'rotate(0deg)'
                        });
                    } else if (toggleAnim === 'flip') {
                        $closeIcon.css({
                            'transition': 'transform 0.4s ease',
                            'transform': 'rotate' + toggleFlipAxis + '(0deg)'
                        });
                    } else if (toggleAnim === 'scale-rotate') {
                        $closeIcon.css({
                            'transition': 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                            'transform': 'scale(0.8) rotate(0deg)'
                        });
                    }

                    // Switch icons after animation completes
                    setTimeout(function () {
                        $btn.find('.wpslt-icon-open').show();
                        $closeIcon.hide().css('transform', 'rotate(0deg)');
                    }, toggleAnim === 'flip' ? 400 : 300);

                    $btn.css({
                        'background': $btn.data('open-bg'),
                        'color': $btn.data('open-icon-color') || '#ffffff'
                    });
                } else {
                    // Open with animation
                    if (menuAnimation === 'slide') {
                        // Slide: Staggered open animation (like Chaty)
                        $contacts.css({
                            'display': 'flex',
                            'opacity': '1'
                        });
                        // Set initial state for each item
                        $contactItems.css({
                            'opacity': '0',
                            'transform': 'scale(0.5)',
                            'transition': 'none'
                        });
                        // Animate each item with staggered delay
                        setTimeout(function () {
                            $contactItems.each(function (index) {
                                const $item = $(this);
                                const delay = index * 50; // 50ms between each
                                setTimeout(function () {
                                    $item.css({
                                        'transition': 'all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                                        'opacity': '1',
                                        'transform': 'scale(1)'
                                    });
                                }, delay);
                            });
                        }, 10);
                    } else {
                        // Other animations: container-based
                        $contacts.css({
                            'display': 'flex',
                            'opacity': '0',
                            'transition': 'none'
                        });
                        $contactItems.css({
                            'opacity': '1',
                            'transform': 'scale(1)'
                        });

                        // Set initial state based on animation type
                        switch (menuAnimation) {
                            case 'fade':
                                $contacts.css('transform', 'scale(1)');
                                break;
                            case 'zoom':
                                $contacts.css('transform', 'scale(0)');
                                break;
                            case 'flip':
                                $contacts.css('transform', 'perspective(400px) rotateX(' + (isBottom ? '90deg' : '-90deg') + ')');
                                break;
                            case 'bounce':
                                $contacts.css('transform', 'scale(0)');
                                break;
                        }

                        // Trigger reflow and animate in
                        setTimeout(function () {
                            $contacts.css({
                                'transition': menuAnimation === 'bounce' ? 'all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)' : 'all 0.3s ease',
                                'opacity': '1',
                                'transform': menuAnimation === 'flip' ? 'perspective(400px) rotateX(0deg)' : 'scale(1)'
                            });
                        }, 10);
                    }

                    $btn.find('.wpslt-icon-open').hide();
                    $btn.find('.wpslt-icon-close').show();
                    // Apply toggle animation to close icon
                    const toggleAnim = $btn.data('toggle-animation') || 'rotate';
                    const toggleRotateDeg = settings.style.toggle_rotate_deg || '180';
                    const toggleFlipAxis = settings.style.toggle_flip_axis || 'Y';
                    const toggleScaleMin = settings.style.toggle_scale_min || '0.7';
                    const toggleScaleRotateDeg = settings.style.toggle_scale_rotate_deg || '360';

                    if (toggleAnim === 'rotate') {
                        $btn.find('.wpslt-icon-close').css({
                            'transition': 'transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                            'transform': 'rotate(' + toggleRotateDeg + 'deg)'
                        });
                    } else if (toggleAnim === 'flip') {
                        $btn.find('.wpslt-icon-close').css({
                            'transition': 'transform 0.4s ease',
                            'transform': 'rotate' + toggleFlipAxis + '(180deg)'
                        });
                    } else if (toggleAnim === 'scale-rotate') {
                        // Scale+Rotate: scale down then back up with rotation
                        const $icon = $btn.find('.wpslt-icon-close');
                        const halfDeg = parseInt(toggleScaleRotateDeg) / 2;
                        $icon.css({
                            'transition': 'none',
                            'transform': 'scale(1) rotate(0deg)'
                        });
                        setTimeout(function () {
                            $icon.css({
                                'transition': 'transform 0.15s ease-in',
                                'transform': 'scale(' + toggleScaleMin + ') rotate(' + halfDeg + 'deg)'
                            });
                            setTimeout(function () {
                                $icon.css({
                                    'transition': 'transform 0.15s ease-out',
                                    'transform': 'scale(1) rotate(' + toggleScaleRotateDeg + 'deg)'
                                });
                            }, 150);
                        }, 10);
                    }
                    $btn.css({
                        'background': $btn.data('close-bg'),
                        'color': $btn.data('close-icon-color')
                    });
                }
            });

            // Bind tooltip hover
            $preview.off('mouseenter mouseleave', '.wpslt-fc-preview-contact, .wpslt-fc-preview-btn').on('mouseenter', '.wpslt-fc-preview-contact, .wpslt-fc-preview-btn', function () {
                const $tooltip = $(this).find('.wpslt-preview-tooltip');
                if ($tooltip.length > 0) {
                    $tooltip.css('opacity', '1');
                }
            }).on('mouseleave', '.wpslt-fc-preview-contact, .wpslt-fc-preview-btn', function () {
                // Only hide if display mode is NOT 'always'
                if (settings.tooltip.display_mode !== 'always') {
                    const $tooltip = $(this).find('.wpslt-preview-tooltip');
                    if ($tooltip.length > 0) {
                        $tooltip.css('opacity', '0');
                    }
                }
            });

            // Auto expand preview container using ResizeObserver
            if (!this.previewObserver) {
                this.previewObserver = new ResizeObserver(entries => {
                    for (let entry of entries) {
                        // Use scrollHeight of the widget content
                        const height = entry.target.scrollHeight;
                        const $container = $(entry.target).closest('.wpslt-preview-content');
                        const minHeight = 350;
                        const buffer = 80; // Top + Bottom padding + Safety

                        // Ensure minimal height and expand if needed
                        const newHeight = Math.max(minHeight, height + buffer);
                        $container.css('height', newHeight + 'px');
                    }
                });
            }

            // Observe the new widget element
            const widgetEl = $preview.find('.wpslt-fc-preview')[0];
            if (widgetEl) {
                // If we had an old observer for a different element, we should probably unobserve it,
                // but simpler to just disconnect all since we only have one preview widget.
                this.previewObserver.disconnect();
                this.previewObserver.observe(widgetEl);
            }
        },

        getFormSettings: function () {
            const settings = {
                enabled: $('[name="wpslt_floating_contact[enabled]"]').is(':checked'),
                position: {
                    horizontal: $('[name="wpslt_floating_contact[position][horizontal]"]').val() || 'right',
                    vertical: $('[name="wpslt_floating_contact[position][vertical]"]').val() || 'bottom',
                    offset_x: $('[name="wpslt_floating_contact[position][offset_x]"]').val() || '20',
                    offset_x_unit: $('[name="wpslt_floating_contact[position][offset_x_unit]"]').val() || 'px',
                    offset_y: $('[name="wpslt_floating_contact[position][offset_y]"]').val() || '20',
                    offset_y_unit: $('[name="wpslt_floating_contact[position][offset_y_unit]"]').val() || 'px'
                },
                spacing: {
                    contact_gap: $('[name="wpslt_floating_contact[spacing][contact_gap]"]').val() || '10',
                    contact_gap_unit: $('[name="wpslt_floating_contact[spacing][contact_gap_unit]"]').val() || 'px',
                    z_index: $('[name="wpslt_floating_contact[spacing][z_index]"]').val() || '99999'
                },
                style: {
                    button_size: $('[name="wpslt_floating_contact[style][button_size]"]').val() || '60',
                    button_size_unit: $('[name="wpslt_floating_contact[style][button_size_unit]"]').val() || 'px',
                    icon_size: $('[name="wpslt_floating_contact[style][icon_size]"]').val() || '28',
                    icon_size_unit: $('[name="wpslt_floating_contact[style][icon_size_unit]"]').val() || 'px',
                    primary_color: $('[name="wpslt_floating_contact[style][primary_color]"]').val() || '#E41E26',
                    background: $('[name="wpslt_floating_contact[style][background]"]').val() || '#ffffff',
                    border_radius: $('[name="wpslt_floating_contact[style][border_radius]"]').val() || '50',
                    border_radius_unit: $('[name="wpslt_floating_contact[style][border_radius_unit]"]').val() || '%',
                    box_shadow: $('[name="wpslt_floating_contact[style][box_shadow]"]').is(':checked'),
                    animation: $('[name="wpslt_floating_contact[style][animation]"]').val() || 'none',
                    hover_animation: $('[name="wpslt_floating_contact[style][hover_animation]"]').val() || 'none',
                    menu_animation: $('[name="wpslt_floating_contact[style][menu_animation]"]').val() || 'slide',
                    toggle_animation: $('[name="wpslt_floating_contact[style][toggle_animation]"]').val() || 'rotate',
                    toggle_rotate_deg: $('[name="wpslt_floating_contact[style][toggle_rotate_deg]"]').val() || '180',
                    toggle_flip_axis: $('[name="wpslt_floating_contact[style][toggle_flip_axis]"]').val() || 'Y',
                    toggle_scale_min: $('[name="wpslt_floating_contact[style][toggle_scale_min]"]').val() || '0.7',
                    toggle_scale_rotate_deg: $('[name="wpslt_floating_contact[style][toggle_scale_rotate_deg]"]').val() || '360',
                    default_state: $('[name="wpslt_floating_contact[style][default_state]"]').val() || 'closed',
                    show_toggle_button: $('[name="wpslt_floating_contact[style][show_toggle_button]"]').length ? $('[name="wpslt_floating_contact[style][show_toggle_button]"]').is(':checked') : true,
                    open_icon: $('[name="wpslt_floating_contact[style][open_icon]"]').val(),
                    open_icon_color: $('[name="wpslt_floating_contact[style][open_icon_color]"]').val() || '#ffffff',
                    close_icon: $('[name="wpslt_floating_contact[style][close_icon]"]').val(),
                    close_bg_color: $('[name="wpslt_floating_contact[style][close_bg_color]"]').val(),
                    close_icon_color: $('[name="wpslt_floating_contact[style][close_icon_color]"]').val(),
                    close_tooltip_text: $('[name="wpslt_floating_contact[style][close_tooltip_text]"]').val() || 'Contact Us',
                    // Custom contact sizing
                    use_custom_contact_size: $('[name="wpslt_floating_contact[style][use_custom_contact_size]"]').is(':checked'),
                    contact_button_size: $('[name="wpslt_floating_contact[style][contact_button_size]"]').val() || '50',
                    contact_button_size_unit: $('[name="wpslt_floating_contact[style][contact_button_size_unit]"]').val() || 'px',
                    contact_icon_size: $('[name="wpslt_floating_contact[style][contact_icon_size]"]').val() || '24',
                    contact_icon_size_unit: $('[name="wpslt_floating_contact[style][contact_icon_size_unit]"]').val() || 'px',
                    // Animation timing
                    animation_speed: $('[name="wpslt_floating_contact[style][animation_speed]"]').val() || 'normal',
                    menu_item_delay: $('[name="wpslt_floating_contact[style][menu_item_delay]"]').val() || '50',
                    // Custom icons
                    open_custom_icon: $('[name="wpslt_floating_contact[style][open_custom_icon]"]').val() || '',
                    close_custom_icon: $('[name="wpslt_floating_contact[style][close_custom_icon]"]').val() || ''
                },
                mobile: {
                    override_position: $('[name="wpslt_floating_contact[mobile][override_position]"]').is(':checked'),
                    horizontal: $('[name="wpslt_floating_contact[mobile][horizontal]"]').val() || 'right',
                    vertical: $('[name="wpslt_floating_contact[mobile][vertical]"]').val() || 'bottom',
                    offset_x: $('[name="wpslt_floating_contact[mobile][offset_x]"]').val() || '20',
                    offset_x_unit: $('[name="wpslt_floating_contact[mobile][offset_x_unit]"]').val() || 'px',
                    offset_y: $('[name="wpslt_floating_contact[mobile][offset_y]"]').val() || '20',
                    offset_y_unit: $('[name="wpslt_floating_contact[mobile][offset_y_unit]"]').val() || 'px',
                    override_size: $('[name="wpslt_floating_contact[mobile][override_size]"]').is(':checked'),
                    button_size: $('[name="wpslt_floating_contact[mobile][button_size]"]').val() || '50',
                    button_size_unit: $('[name="wpslt_floating_contact[mobile][button_size_unit]"]').val() || 'px',
                    icon_size: $('[name="wpslt_floating_contact[mobile][icon_size]"]').val() || '20',
                    icon_size_unit: $('[name="wpslt_floating_contact[mobile][icon_size_unit]"]').val() || 'px'
                },
                accessibility: {
                    enable_keyboard_nav: $('[name="wpslt_floating_contact[accessibility][enable_keyboard_nav]"]').is(':checked'),
                    high_contrast_mode: $('[name="wpslt_floating_contact[accessibility][high_contrast_mode]"]').is(':checked'),
                    custom_aria_label: $('[name="wpslt_floating_contact[accessibility][custom_aria_label]"]').val() || ''
                },
                tooltip: {
                    enabled: $('[name="wpslt_floating_contact[tooltip][enabled]"]').is(':checked'),
                    background: $('[name="wpslt_floating_contact[tooltip][background]"]').val() || '#333333',
                    text_color: $('[name="wpslt_floating_contact[tooltip][text_color]"]').val() || '#ffffff',
                    position: $('[name="wpslt_floating_contact[tooltip][position]"]').val() || 'auto',
                    font_size: $('[name="wpslt_floating_contact[tooltip][font_size]"]').val() || '14',
                    font_size_unit: $('[name="wpslt_floating_contact[tooltip][font_size_unit]"]').val() || 'px',
                    border_radius: $('[name="wpslt_floating_contact[tooltip][border_radius]"]').val() || '4',
                    border_radius_unit: $('[name="wpslt_floating_contact[tooltip][border_radius_unit]"]').val() || 'px',
                    display_mode: $('[name="wpslt_floating_contact[tooltip][display_mode]"]').val() || 'hover',
                    show_mobile: $('[name="wpslt_floating_contact[tooltip][show_mobile]"]').is(':checked')
                },
                contacts: []
            };

            $('.wpslt-contact-item').each(function () {
                const $item = $(this);
                settings.contacts.push({
                    type: $item.find('.wpslt-contact-type').val(),
                    label: $item.find('.wpslt-contact-label').val(),
                    icon: $item.find('.wpslt-contact-icon-field').val() || $item.find('[name*="[icon]"]').val() || 'fas fa-link',
                    color: $item.find('[name*="[color]"]').not('[name*="icon_color"]').val() || '#666666',
                    icon_color: $item.find('[name*="[icon_color]"]').val() || '#ffffff',
                    tooltip: $item.find('[name*="[tooltip]"]').val() || '',
                    custom_icon: $item.find('[name*="[custom_icon]"]').val() || ''
                });
            });

            return settings;
        }
    };

    $(document).ready(function () {
        if ($('.wpslt-contact-wrap').length) {
            WPSLTContact.init();
        }
    });

})(jQuery);

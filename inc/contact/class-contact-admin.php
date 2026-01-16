<?php
/**
 * Floating Contact Admin Handler
 * 
 * Handles the admin interface for managing floating contact widget.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Contact_Admin
 */
class WPSLT_Contact_Admin
{
    /**
     * Option name for settings
     */
    const OPTION_NAME = 'wpslt_floating_contact';

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Default settings
     */
    private $defaults = array(
        'enabled' => false,
        'position' => array(
            'horizontal' => 'right',
            'vertical' => 'bottom',
            'offset_x' => '20',
            'offset_x_unit' => 'px',
            'offset_y' => '20',
            'offset_y_unit' => 'px',
        ),
        'spacing' => array(
            'contact_gap' => '10',
            'contact_gap_unit' => 'px',
            'z_index' => '99999',
        ),
        'style' => array(
            'button_size' => '60',
            'button_size_unit' => 'px',
            'icon_size' => '28',
            'icon_size_unit' => 'px',
            'primary_color' => '#E41E26',
            'open_icon_color' => '#ffffff',
            'border_radius' => '50',
            'border_radius_unit' => '%',
            'box_shadow' => true,
            'open_icon' => 'fas fa-comment-dots',
            'close_icon' => 'fas fa-times',
            'close_bg_color' => '',
            'close_icon_color' => '',
            'close_tooltip_text' => 'Contact Us', // Default tooltip text
            'toggle_animation' => 'rotate',
            'show_toggle_button' => true, // New: Show/Hide toggle button
            'toggle_rotate_deg' => '180',           // Rotate degrees: 90, 180, 360
            'toggle_flip_axis' => 'Y',              // Flip axis: X, Y
            'toggle_scale_min' => '0.7',            // Scale minimum: 0.5, 0.7, 0.9
            'toggle_scale_rotate_deg' => '360',     // Scale+Rotate degrees: 180, 360
            'animation' => 'none',
            'hover_animation' => 'none',
            'menu_animation' => 'slide',
            'default_state' => 'closed',
            'remember_state' => true,
            // Contact item sizing override
            'use_custom_contact_size' => false,
            'contact_button_size' => '50',
            'contact_button_size_unit' => 'px',
            'contact_icon_size' => '24',
            'contact_icon_size_unit' => 'px',
            // Animation timing
            'animation_speed' => 'normal',          // fast, normal, slow
            'menu_item_delay' => '50',              // milliseconds
        ),
        'mobile' => array(
            'override_position' => false,
            'horizontal' => 'right',
            'vertical' => 'bottom',
            'offset_x' => '10',
            'offset_x_unit' => 'px',
            'offset_y' => '10',
            'offset_y_unit' => 'px',
            'override_size' => false,
            'button_size' => '50',
            'button_size_unit' => 'px',
            'icon_size' => '22',
            'icon_size_unit' => 'px',
        ),
        'tooltip' => array(
            'enabled' => true,
            'background' => '#333333',
            'text_color' => '#ffffff',
            'font_size' => '14',
            'font_size_unit' => 'px',
            'border_radius' => '4',
            'border_radius_unit' => 'px',
            'border_radius_unit' => 'px',
            'position' => 'auto',
            'display_mode' => 'hover', // New: hover or always
            'show_mobile' => false,    // New: Show on mobile
        ),
        'accessibility' => array(
            'enable_keyboard_nav' => true,
            'high_contrast_mode' => false,
            'custom_aria_label' => '',
        ),
        'display' => array(
            'mobile' => true,
            'desktop' => true,
            'pages' => 'all',
            'page_ids' => array(),
            'load_fontawesome' => true,
        ),
        'contacts' => array(),
    );

    /**
     * Preset contact types
     */
    private $preset_types = array(
        'line' => array(
            'label' => 'LINE',
            'icon' => 'fab fa-line',
            'color' => '#00C300',
            'url_prefix' => 'https://line.me/R/ti/p/',
        ),
        'facebook' => array(
            'label' => 'Facebook',
            'icon' => 'fab fa-facebook-f',
            'color' => '#1877F2',
            'url_prefix' => 'https://facebook.com/',
        ),
        'messenger' => array(
            'label' => 'Messenger',
            'icon' => 'fab fa-facebook-messenger',
            'color' => '#0084FF',
            'url_prefix' => 'https://m.me/',
        ),
        'whatsapp' => array(
            'label' => 'WhatsApp',
            'icon' => 'fab fa-whatsapp',
            'color' => '#25D366',
            'url_prefix' => 'https://wa.me/',
        ),
        'phone' => array(
            'label' => 'Phone',
            'icon' => 'fas fa-phone',
            'color' => '#E41E26',
            'url_prefix' => 'tel:',
        ),
        'email' => array(
            'label' => 'Email',
            'icon' => 'fas fa-envelope',
            'color' => '#EA4335',
            'url_prefix' => 'mailto:',
        ),
        'instagram' => array(
            'label' => 'Instagram',
            'icon' => 'fab fa-instagram',
            'color' => '#E4405F',
            'url_prefix' => 'https://instagram.com/',
        ),
        'telegram' => array(
            'label' => 'Telegram',
            'icon' => 'fab fa-telegram',
            'color' => '#0088CC',
            'url_prefix' => 'https://t.me/',
        ),
        'tiktok' => array(
            'label' => 'TikTok',
            'icon' => 'fab fa-tiktok',
            'color' => '#000000',
            'url_prefix' => 'https://tiktok.com/@',
        ),
        'custom' => array(
            'label' => 'Custom',
            'icon' => 'fas fa-link',
            'color' => '#666666',
            'url_prefix' => '',
        ),
    );

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu'), 25);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_wpslt_save_contacts', array($this, 'ajax_save_contacts'));
    }

    /**
     * Add submenu page
     */
    public function add_menu()
    {
        add_submenu_page(
            'wpslt-settings',
            __('Floating Contact', 'wp-slatan-theme'),
            __('Floating Contact', 'wp-slatan-theme'),
            'manage_options',
            'wpslt-floating-contact',
            array($this, 'render_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting(
            'wpslt_floating_contact_group',
            self::OPTION_NAME,
            array($this, 'sanitize_settings')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook)
    {
        if ('slatan-theme_page_wpslt-floating-contact' !== $hook) {
            return;
        }

        // jQuery UI Sortable
        wp_enqueue_script('jquery-ui-sortable');

        // WordPress Media Uploader
        wp_enqueue_media();

        // WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Base admin styles
        wp_enqueue_style(
            'wpslt-admin-base',
            WPSLT_URI . '/assets/admin/css/base.css',
            array(),
            WPSLT_VERSION
        );

        // Contact admin styles
        wp_enqueue_style(
            'wpslt-contact-admin',
            WPSLT_URI . '/assets/admin/css/contact.css',
            array('wpslt-admin-base', 'wp-color-picker'),
            WPSLT_VERSION
        );


        // Icon Data
        wp_enqueue_script(
            'wpslt-icons',
            WPSLT_URI . '/assets/admin/js/wpslt-icons.js',
            array(),
            WPSLT_VERSION,
            true
        );

        // Contact admin script
        wp_enqueue_script(
            'wpslt-contact-admin',
            WPSLT_URI . '/assets/admin/js/contact.js',
            array('jquery', 'jquery-ui-sortable', 'wp-color-picker', 'wp-util', 'wpslt-icons'),
            WPSLT_VERSION,
            true
        );

        // Font Awesome - always load for admin preview
        wp_enqueue_style(
            'wpslt-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            array(),
            '6.5.1'
        );

        // Localize script
        wp_localize_script('wpslt-contact-admin', 'wpsltContact', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpslt_contact_nonce'),
            'presetTypes' => $this->preset_types,
            'settings' => $this->get_settings(),
            'i18n' => array(
                'deleteConfirm' => __('Are you sure you want to delete this contact?', 'wp-slatan-theme'),
                'selectIcon' => __('Select Icon', 'wp-slatan-theme'),
                'selectImage' => __('Select Custom Icon Image', 'wp-slatan-theme'),
                'useImage' => __('Use this image', 'wp-slatan-theme'),
                'saved' => __('Settings saved successfully!', 'wp-slatan-theme'),
                'error' => __('An error occurred.', 'wp-slatan-theme'),
            ),
        ));
    }

    /**
     * Get settings with defaults
     */
    public function get_settings()
    {
        $settings = get_option(self::OPTION_NAME, array());
        return wp_parse_args($settings, $this->defaults);
    }

    /**
     * Get preset types
     */
    public function get_preset_types()
    {
        return $this->preset_types;
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Enabled
        $sanitized['enabled'] = !empty($input['enabled']);

        // Position
        $sanitized['position'] = array(
            'horizontal' => in_array($input['position']['horizontal'] ?? '', array('left', 'right')) ? $input['position']['horizontal'] : 'right',
            'vertical' => in_array($input['position']['vertical'] ?? '', array('top', 'bottom')) ? $input['position']['vertical'] : 'bottom',
            'offset_x' => absint($input['position']['offset_x'] ?? 20),
            'offset_x_unit' => in_array($input['position']['offset_x_unit'] ?? '', array('px', 'rem', '%')) ? $input['position']['offset_x_unit'] : 'px',
            'offset_y' => absint($input['position']['offset_y'] ?? 20),
            'offset_y_unit' => in_array($input['position']['offset_y_unit'] ?? '', array('px', 'rem', '%')) ? $input['position']['offset_y_unit'] : 'px',
        );

        // Spacing
        $sanitized['spacing'] = array(
            'contact_gap' => absint($input['spacing']['contact_gap'] ?? 10),
            'contact_gap_unit' => in_array($input['spacing']['contact_gap_unit'] ?? '', array('px', 'rem')) ? $input['spacing']['contact_gap_unit'] : 'px',
            'z_index' => max(1, min(999999, absint($input['spacing']['z_index'] ?? 99999))), // Clamp between 1-999999
        );

        // Style
        $sanitized['style'] = array(
            'button_size' => absint($input['style']['button_size'] ?? 60),
            'button_size_unit' => in_array($input['style']['button_size_unit'] ?? '', array('px', 'rem')) ? $input['style']['button_size_unit'] : 'px',
            'icon_size' => absint($input['style']['icon_size'] ?? 28),
            'icon_size_unit' => in_array($input['style']['icon_size_unit'] ?? '', array('px', 'rem')) ? $input['style']['icon_size_unit'] : 'px',
            'primary_color' => sanitize_hex_color($input['style']['primary_color'] ?? '#E41E26'),
            'open_icon_color' => sanitize_hex_color($input['style']['open_icon_color'] ?? '#ffffff'),
            'border_radius' => absint($input['style']['border_radius'] ?? 50),
            'border_radius_unit' => in_array($input['style']['border_radius_unit'] ?? '', array('px', '%')) ? $input['style']['border_radius_unit'] : '%',
            'box_shadow' => !empty($input['style']['box_shadow']),
            'animation' => in_array($input['style']['animation'] ?? '', array('none', 'pulse', 'bounce', 'shake', 'wobble')) ? $input['style']['animation'] : 'pulse',
            'hover_animation' => in_array($input['style']['hover_animation'] ?? '', array('none', 'scale', 'rotate', 'glow', 'swing', 'pop')) ? $input['style']['hover_animation'] : 'none',
            'menu_animation' => in_array($input['style']['menu_animation'] ?? '', array('slide', 'fade', 'zoom', 'flip', 'bounce')) ? $input['style']['menu_animation'] : 'slide',
            'toggle_animation' => in_array($input['style']['toggle_animation'] ?? '', array('none', 'rotate', 'flip', 'scale-rotate')) ? $input['style']['toggle_animation'] : 'rotate',
            'show_toggle_button' => !empty($input['style']['show_toggle_button']),
            'toggle_rotate_deg' => in_array($input['style']['toggle_rotate_deg'] ?? '', array('90', '180', '360')) ? $input['style']['toggle_rotate_deg'] : '180',
            'toggle_flip_axis' => in_array($input['style']['toggle_flip_axis'] ?? '', array('X', 'Y')) ? $input['style']['toggle_flip_axis'] : 'Y',
            'toggle_scale_min' => in_array($input['style']['toggle_scale_min'] ?? '', array('0.5', '0.7', '0.9')) ? $input['style']['toggle_scale_min'] : '0.7',
            'toggle_scale_rotate_deg' => in_array($input['style']['toggle_scale_rotate_deg'] ?? '', array('180', '360')) ? $input['style']['toggle_scale_rotate_deg'] : '360',
            'open_icon' => sanitize_text_field($input['style']['open_icon'] ?? 'fas fa-comment-dots'),
            'open_custom_icon' => esc_url_raw($input['style']['open_custom_icon'] ?? ''),
            'close_icon' => sanitize_text_field($input['style']['close_icon'] ?? 'fas fa-times'),
            'close_custom_icon' => esc_url_raw($input['style']['close_custom_icon'] ?? ''),
            'close_bg_color' => !empty($input['style']['close_bg_color']) ? sanitize_hex_color($input['style']['close_bg_color']) : '',
            'close_icon_color' => !empty($input['style']['close_icon_color']) ? sanitize_hex_color($input['style']['close_icon_color']) : '',
            'close_tooltip_text' => sanitize_text_field($input['style']['close_tooltip_text'] ?? 'Contact Us'),
            'default_state' => in_array($input['style']['default_state'] ?? '', array('open', 'closed')) ? $input['style']['default_state'] : 'closed',
            'remember_state' => !empty($input['style']['remember_state']),
            // Custom contact sizing
            'use_custom_contact_size' => !empty($input['style']['use_custom_contact_size']),
            'contact_button_size' => absint($input['style']['contact_button_size'] ?? 50),
            'contact_button_size_unit' => in_array($input['style']['contact_button_size_unit'] ?? '', array('px', 'rem')) ? $input['style']['contact_button_size_unit'] : 'px',
            'contact_icon_size' => absint($input['style']['contact_icon_size'] ?? 24),
            'contact_icon_size_unit' => in_array($input['style']['contact_icon_size_unit'] ?? '', array('px', 'rem')) ? $input['style']['contact_icon_size_unit'] : 'px',
            // Animation timing
            'animation_speed' => in_array($input['style']['animation_speed'] ?? '', array('fast', 'normal', 'slow')) ? $input['style']['animation_speed'] : 'normal',
            'menu_item_delay' => max(0, min(500, absint($input['style']['menu_item_delay'] ?? 50))), // Clamp 0-500ms
        );

        // Mobile overrides
        $sanitized['mobile'] = array(
            'override_position' => !empty($input['mobile']['override_position']),
            'horizontal' => in_array($input['mobile']['horizontal'] ?? '', array('left', 'right')) ? $input['mobile']['horizontal'] : 'right',
            'vertical' => in_array($input['mobile']['vertical'] ?? '', array('top', 'bottom')) ? $input['mobile']['vertical'] : 'bottom',
            'offset_x' => absint($input['mobile']['offset_x'] ?? 10),
            'offset_x_unit' => in_array($input['mobile']['offset_x_unit'] ?? '', array('px', 'rem', '%')) ? $input['mobile']['offset_x_unit'] : 'px',
            'offset_y' => absint($input['mobile']['offset_y'] ?? 10),
            'offset_y_unit' => in_array($input['mobile']['offset_y_unit'] ?? '', array('px', 'rem', '%')) ? $input['mobile']['offset_y_unit'] : 'px',
            'override_size' => !empty($input['mobile']['override_size']),
            'button_size' => absint($input['mobile']['button_size'] ?? 50),
            'button_size_unit' => in_array($input['mobile']['button_size_unit'] ?? '', array('px', 'rem')) ? $input['mobile']['button_size_unit'] : 'px',
            'icon_size' => absint($input['mobile']['icon_size'] ?? 22),
            'icon_size_unit' => in_array($input['mobile']['icon_size_unit'] ?? '', array('px', 'rem')) ? $input['mobile']['icon_size_unit'] : 'px',
        );

        // Accessibility
        $sanitized['accessibility'] = array(
            'enable_keyboard_nav' => !empty($input['accessibility']['enable_keyboard_nav']),
            'high_contrast_mode' => !empty($input['accessibility']['high_contrast_mode']),
            'custom_aria_label' => sanitize_text_field($input['accessibility']['custom_aria_label'] ?? ''),
        );

        // Tooltip
        $sanitized['tooltip'] = array(
            'enabled' => !empty($input['tooltip']['enabled']),
            'background' => sanitize_hex_color($input['tooltip']['background'] ?? '#333333'),
            'text_color' => sanitize_hex_color($input['tooltip']['text_color'] ?? '#ffffff'),
            'font_size' => absint($input['tooltip']['font_size'] ?? 14),
            'font_size_unit' => in_array($input['tooltip']['font_size_unit'] ?? '', array('px', 'rem')) ? $input['tooltip']['font_size_unit'] : 'px',
            'border_radius' => absint($input['tooltip']['border_radius'] ?? 4),
            'border_radius_unit' => in_array($input['tooltip']['border_radius_unit'] ?? '', array('px', '%')) ? $input['tooltip']['border_radius_unit'] : 'px',
            'border_radius_unit' => in_array($input['tooltip']['border_radius_unit'] ?? '', array('px', '%')) ? $input['tooltip']['border_radius_unit'] : 'px',
            'position' => in_array($input['tooltip']['position'] ?? '', array('auto', 'left', 'right')) ? $input['tooltip']['position'] : 'auto',
            'display_mode' => in_array($input['tooltip']['display_mode'] ?? '', array('hover', 'always')) ? $input['tooltip']['display_mode'] : 'hover',
            'show_mobile' => !empty($input['tooltip']['show_mobile']),
        );

        // Display
        $sanitized['display'] = array(
            'mobile' => !empty($input['display']['mobile']),
            'desktop' => !empty($input['display']['desktop']),
            'pages' => in_array($input['display']['pages'] ?? '', array('all', 'include', 'exclude')) ? $input['display']['pages'] : 'all',
            'page_ids' => isset($input['display']['page_ids']) ? array_map('absint', (array) $input['display']['page_ids']) : array(),
            'load_fontawesome' => !empty($input['display']['load_fontawesome']),
        );

        // Contacts
        $sanitized['contacts'] = array();
        if (!empty($input['contacts']) && is_array($input['contacts'])) {
            foreach ($input['contacts'] as $index => $contact) {
                $sanitized['contacts'][] = array(
                    'id' => sanitize_key($contact['id'] ?? 'contact_' . $index),
                    'type' => sanitize_key($contact['type'] ?? 'custom'),
                    'label' => sanitize_text_field($contact['label'] ?? ''),
                    'value' => sanitize_text_field($contact['value'] ?? ''),
                    'icon' => sanitize_text_field($contact['icon'] ?? 'fas fa-link'),
                    'custom_icon' => esc_url_raw($contact['custom_icon'] ?? ''),
                    'color' => sanitize_hex_color($contact['color'] ?? '#666666'),
                    'icon_color' => sanitize_hex_color($contact['icon_color'] ?? '#ffffff'),
                    'tooltip' => sanitize_text_field($contact['tooltip'] ?? ''),
                    'order' => absint($contact['order'] ?? $index),
                );
            }
        }

        return $sanitized;
    }

    /**
     * AJAX save contacts
     */
    public function ajax_save_contacts()
    {
        check_ajax_referer('wpslt_contact_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-slatan-theme')));
        }

        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $sanitized = $this->sanitize_settings($settings);

        update_option(self::OPTION_NAME, $sanitized);

        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'wp-slatan-theme'),
            'settings' => $sanitized,
        ));
    }

    /**
     * Render admin page
     */
    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        $option_name = self::OPTION_NAME;
        ?>
        <div class="wrap wpslt-contact-wrap">
            <h1 class="wp-heading-inline wpslt-page-heading">
                <span class="dashicons dashicons-format-chat"></span>
                <?php esc_html_e('Floating Contact', 'wp-slatan-theme'); ?>
            </h1>
            <p class="wpslt-page-description">
                <?php esc_html_e('Add a floating contact button to your website', 'wp-slatan-theme'); ?>
            </p>
            <hr class="wp-header-end">

            <?php settings_errors(); ?>

            <div class="wpslt-contact-layout">
                <!-- Settings Panel -->
                <div class="wpslt-contact-settings">
                    <form method="post" action="options.php" id="wpslt-contact-form">
                        <?php settings_fields('wpslt_floating_contact_group'); ?>

                        <!-- Enable Toggle -->
                        <div class="wpslt-card">
                            <h3>
                                <?php esc_html_e('⚡ Enable Widget', 'wp-slatan-theme'); ?>
                            </h3>
                            <table class="form-table">
                                <tr>
                                    <th>
                                        <?php esc_html_e('Enable Floating Contact', 'wp-slatan-theme'); ?>
                                    </th>
                                    <td>
                                        <label class="wpslt-toggle">
                                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[enabled]"
                                                value="1" <?php checked(!empty($settings['enabled'])); ?>>
                                            <span class="wpslt-toggle-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Tabs -->
                        <div class="wpslt-tabs">
                            <button type="button" class="wpslt-tab active" data-tab="contacts">
                                <?php esc_html_e('Contacts', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="button-styles">
                                <?php esc_html_e('Button Styles', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="animations">
                                <?php esc_html_e('Animations', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="toggle-icons">
                                <?php esc_html_e('Toggle Icons', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="tooltip">
                                <?php esc_html_e('Tooltip', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="position">
                                <?php esc_html_e('Position', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="advanced">
                                <?php esc_html_e('Advanced', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="display">
                                <?php esc_html_e('Display', 'wp-slatan-theme'); ?>
                            </button>
                        </div>

                        <!-- Contacts Tab -->
                        <div id="wpslt-tab-contacts" class="wpslt-tab-content active">
                            <?php $this->render_contacts_tab($settings, $option_name); ?>
                        </div>

                        <!-- Button Styles Tab -->
                        <div id="wpslt-tab-button-styles" class="wpslt-tab-content">
                            <?php $this->render_button_styles_tab($settings, $option_name); ?>
                        </div>

                        <!-- Animations Tab -->
                        <div id="wpslt-tab-animations" class="wpslt-tab-content">
                            <?php $this->render_animations_tab($settings, $option_name); ?>
                        </div>

                        <!-- Toggle Icons Tab -->
                        <div id="wpslt-tab-toggle-icons" class="wpslt-tab-content">
                            <?php $this->render_toggle_icons_tab($settings, $option_name); ?>
                        </div>

                        <!-- Tooltip Tab -->
                        <div id="wpslt-tab-tooltip" class="wpslt-tab-content">
                            <?php $this->render_tooltip_tab($settings, $option_name); ?>
                        </div>

                        <!-- Position Tab -->
                        <div id="wpslt-tab-position" class="wpslt-tab-content">
                            <?php $this->render_position_tab($settings, $option_name); ?>
                        </div>

                        <!-- Advanced Tab -->
                        <div id="wpslt-tab-advanced" class="wpslt-tab-content">
                            <?php $this->render_advanced_tab($settings, $option_name); ?>
                        </div>

                        <!-- Display Tab -->
                        <div id="wpslt-tab-display" class="wpslt-tab-content">
                            <?php $this->render_display_tab($settings, $option_name); ?>
                        </div>

                        <div class="wpslt-submit-wrap">
                            <?php submit_button(__('Save Settings', 'wp-slatan-theme'), 'primary', 'submit', false); ?>
                        </div>
                    </form>
                </div>

                <!-- Preview Panel -->
                <div class="wpslt-contact-preview">
                    <div class="wpslt-preview-panel">
                        <h3 class="wpslt-preview-title">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Live Preview', 'wp-slatan-theme'); ?>
                        </h3>
                        <div class="wpslt-preview-container">
                            <div class="wpslt-preview-wrapper">
                                <div class="wpslt-preview-browser">
                                    <div class="wpslt-preview-browser-bar">
                                        <span class="wpslt-preview-dot"></span>
                                        <span class="wpslt-preview-dot"></span>
                                        <span class="wpslt-preview-dot"></span>
                                    </div>
                                    <div class="wpslt-preview-content">
                                        <div class="wpslt-preview-page">
                                            <div class="wpslt-preview-placeholder"></div>
                                            <div class="wpslt-preview-placeholder short"></div>
                                            <div class="wpslt-preview-placeholder"></div>
                                        </div>
                                        <!-- Preview widget will be rendered here by JS -->
                                        <div id="wpslt-preview-widget"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render contacts tab
     */
    private function render_contacts_tab($settings, $option_name)
    {
        ?>
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('📱 Contact Channels', 'wp-slatan-theme'); ?>
            </h3>

            <div class="wpslt-contacts-list" id="wpslt-contacts-list">
                <?php
                if (!empty($settings['contacts'])) {
                    foreach ($settings['contacts'] as $index => $contact) {
                        $this->render_contact_item($contact, $index, $option_name);
                    }
                }
                ?>
            </div>

            <div class="wpslt-add-contact">
                <select id="wpslt-contact-type">
                    <?php foreach ($this->preset_types as $type => $data): ?>
                        <option value="<?php echo esc_attr($type); ?>" data-icon="<?php echo esc_attr($data['icon']); ?>"
                            data-color="<?php echo esc_attr($data['color']); ?>">
                            <?php echo esc_html($data['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-secondary" id="wpslt-add-contact">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Add Contact', 'wp-slatan-theme'); ?>
                </button>
            </div>
        </div>

        <!-- Contact Item Template -->
        <script type="text/html" id="tmpl-wpslt-contact-item">
                                                                                                                                                                                            <div class="wpslt-contact-item" data-index="{{ data.index }}">
                                                                                                                                                                                                <div class="wpslt-contact-header">
                                                                                                                                                                                                    <span class="wpslt-contact-drag dashicons dashicons-menu"></span>
                                                                                                                                                                                                    <span class="wpslt-contact-icon" style="color: #666666">
                                                                                                                                                                                                        <i class="fas fa-link"></i>
                                                                                                                                                                                                    </span>
                                                                                                                                                                                                    <span class="wpslt-contact-title"><?php esc_html_e('New Contact', 'wp-slatan-theme'); ?></span>
                                                                                                                                                                                                    <button type="button" class="wpslt-contact-toggle dashicons dashicons-arrow-down-alt2"></button>
                                                                                                                                                                                                    <button type="button" class="wpslt-contact-delete dashicons dashicons-trash"></button>
                                                                                                                                                                                                </div>
                                                                                                                                                                                                <div class="wpslt-contact-body">
                                                                                                                                                                                                    <input type="hidden" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][id]" value="">
                                                                                                                                                                                                    <input type="hidden" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][type]" value="custom" class="wpslt-contact-type">
                                                                                                                                                                                                    <input type="hidden" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][order]" value="{{ data.index }}" class="wpslt-contact-order">

                                                                                                                                                                                                    <table class="form-table">
                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                            <th><?php esc_html_e('Label', 'wp-slatan-theme'); ?></th>
                                                                                                                                                                                                            <td>
                                                                                                                                                                                                                <input type="text" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][label]" value="" class="regular-text wpslt-contact-label">
                                                                                                                                                                                                            </td>
                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                            <th><?php esc_html_e('Value', 'wp-slatan-theme'); ?></th>
                                                                                                                                                                                                            <td>
                                                                                                                                                                                                                <input type="text" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][value]" value="" class="regular-text" placeholder="<?php esc_attr_e('ID, URL, or phone number', 'wp-slatan-theme'); ?>">
                                                                                                                                                                                                            </td>
                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                            <th><?php esc_html_e('Icon', 'wp-slatan-theme'); ?></th>
                                                                                                                                                                                                            <td>
                                                                                                                                                                                                                <div class="wpslt-icon-input-wrapper">
                                                                                                                                                                                                                    <div class="wpslt-icon-preview">
                                                                                                                                                                                                                        <i class="{{ data.contact.icon }}"></i>
                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                    <input type="text" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][icon]" value="fas fa-link" class="regular-text wpslt-icon-picker-input" placeholder="fas fa-link">
                                                                                                                                                                                                                    <button type="button" class="button wpslt-icon-picker-btn" title="<?php esc_attr_e('Select Icon', 'wp-slatan-theme'); ?>">
                                                                                                                                                                                                                        <span class="dashicons dashicons-search"></span>
                                                                                                                                                                                                                    </button>
                                                                                                                                                                                                                </div>
                                                                                                                                                                                                            </td>
                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                            <th><?php esc_html_e('Custom Icon Image', 'wp-slatan-theme'); ?></th>
                                                                                                                                                                                                            <td>
                                                                                                                                                                                                                <div class="wpslt-custom-icon-wrapper">
                                                                                                                                                                                                                    <div class="wpslt-custom-icon-box">
                                                                                                                                                                                                                        <div class="wpslt-icon-placeholder">
                                                                                                                                                                                                                            <span class="dashicons dashicons-plus-alt2"></span>
                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                    <button type="button" class="wpslt-remove-icon-btn" title="<?php esc_attr_e('Remove', 'wp-slatan-theme'); ?>">
                                                                                                                                                                                                                        <span class="dashicons dashicons-no-alt"></span>
                                                                                                                                                                                                                    </button>
                                                                                                                                                                                                                    <input type="hidden" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][custom_icon]" value="" class="wpslt-custom-icon-input">
                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                <p class="description"><?php esc_html_e('Click to upload (PNG, JPG, SVG)', 'wp-slatan-theme'); ?></p>
                                                                                                                                                                                                            </td>
                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                            <th><?php esc_html_e('Background Color', 'wp-slatan-theme'); ?></th>
                                                                                                                                                                                                            <td>
                                                                                                                                                                                                                <input type="text" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][color]" value="#666666" class="wpslt-color-picker">
                                                                                                                                                                                                            </td>
                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                            <th><?php esc_html_e('Icon Color', 'wp-slatan-theme'); ?></th>
                                                                                                                                                                                                            <td>
                                                                                                                                                                                                                <input type="text" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][icon_color]" value="#ffffff" class="wpslt-color-picker">
                                                                                                                                                                                                            </td>
                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                        <tr>
                                                                                                                                                                                                            <th><?php esc_html_e('Tooltip Text', 'wp-slatan-theme'); ?></th>
                                                                                                                                                                                                            <td>
                                                                                                                                                                                                                <input type="text" name="<?php echo esc_attr($option_name); ?>[contacts][{{ data.index }}][tooltip]" value="" class="regular-text" placeholder="<?php esc_attr_e('Leave empty to use label', 'wp-slatan-theme'); ?>">
                                                                                                                                                                                                            </td>
                                                                                                                                                                                                        </tr>
                                                                                                                                                                                                    </table>
                                                                                                                                                                                                </div>
                                                                                                                                                                                            </div>
                                                                                                                                                                                        </script>
        <?php
    }

    /**
     * Render single contact item
     */
    private function render_contact_item($contact, $index, $option_name, $is_template = false)
    {
        $defaults = array(
            'id' => 'contact_' . $index,
            'type' => 'custom',
            'label' => '',
            'value' => '',
            'icon' => 'fas fa-link',
            'color' => '#666666',
            'icon_color' => '#ffffff',
            'tooltip' => '',
            'order' => $index,
        );
        $contact = wp_parse_args($contact, $defaults);
        $name_prefix = $option_name . '[contacts][' . $index . ']';
        ?>
        <div class="wpslt-contact-item collapsed" data-index="<?php echo esc_attr($index); ?>">
            <div class="wpslt-contact-header">
                <span class="wpslt-contact-drag dashicons dashicons-menu"></span>
                <span class="wpslt-contact-icon" style="color: <?php echo esc_attr($contact['color']); ?>">
                    <i class="<?php echo esc_attr($contact['icon']); ?>"></i>
                </span>
                <span class="wpslt-contact-title">
                    <?php echo esc_html($contact['label'] ?: __('New Contact', 'wp-slatan-theme')); ?>
                </span>
                <button type="button" class="wpslt-contact-toggle dashicons dashicons-arrow-down-alt2"></button>
                <button type="button" class="wpslt-contact-delete dashicons dashicons-trash"></button>
            </div>
            <div class="wpslt-contact-body">
                <input type="hidden" name="<?php echo esc_attr($name_prefix); ?>[id]"
                    value="<?php echo esc_attr($contact['id']); ?>">
                <input type="hidden" name="<?php echo esc_attr($name_prefix); ?>[type]"
                    value="<?php echo esc_attr($contact['type']); ?>" class="wpslt-contact-type">
                <input type="hidden" name="<?php echo esc_attr($name_prefix); ?>[order]"
                    value="<?php echo esc_attr($contact['order']); ?>" class="wpslt-contact-order">

                <table class="form-table">
                    <tr>
                        <th>
                            <?php esc_html_e('Label', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($name_prefix); ?>[label]"
                                value="<?php echo esc_attr($contact['label']); ?>" class="regular-text wpslt-contact-label">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Value', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($name_prefix); ?>[value]"
                                value="<?php echo esc_attr($contact['value']); ?>" class="regular-text"
                                placeholder="<?php esc_attr_e('ID, URL, or phone number', 'wp-slatan-theme'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Icon', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div class="wpslt-icon-input-wrapper">
                                <div class="wpslt-icon-preview">
                                    <i class="<?php echo esc_attr($contact['icon']); ?>"></i>
                                </div>
                                <input type="text" name="<?php echo esc_attr($name_prefix); ?>[icon]"
                                    value="<?php echo esc_attr($contact['icon']); ?>"
                                    class="regular-text wpslt-icon-picker-input" placeholder="fas fa-link">
                                <button type="button" class="button wpslt-icon-picker-btn"
                                    title="<?php esc_attr_e('Select Icon', 'wp-slatan-theme'); ?>">
                                    <span class="dashicons dashicons-search"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Custom Icon Image', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div
                                class="wpslt-custom-icon-wrapper <?php echo !empty($contact['custom_icon']) ? 'has-image' : ''; ?>">
                                <div class="wpslt-custom-icon-box">
                                    <div class="wpslt-icon-placeholder">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                    </div>
                                    <?php if (!empty($contact['custom_icon'])): ?>
                                        <img src="<?php echo esc_url($contact['custom_icon']); ?>" alt="">
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="wpslt-remove-icon-btn"
                                    title="<?php esc_attr_e('Remove', 'wp-slatan-theme'); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                                <input type="hidden" name="<?php echo esc_attr($name_prefix); ?>[custom_icon]"
                                    value="<?php echo esc_url($contact['custom_icon'] ?? ''); ?>"
                                    class="wpslt-custom-icon-input">
                            </div>
                            <p class="description">
                                <?php esc_html_e('Click to upload a custom image (PNG, JPG, SVG).', 'wp-slatan-theme'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Background Color', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($name_prefix); ?>[color]"
                                value="<?php echo esc_attr($contact['color']); ?>" class="wpslt-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Icon Color', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($name_prefix); ?>[icon_color]"
                                value="<?php echo esc_attr($contact['icon_color'] ?? '#ffffff'); ?>" class="wpslt-color-picker">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Tooltip Text', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($name_prefix); ?>[tooltip]"
                                value="<?php echo esc_attr($contact['tooltip']); ?>" class="regular-text"
                                placeholder="<?php esc_attr_e('Leave empty to use label', 'wp-slatan-theme'); ?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render button styles tab
     */
    private function render_button_styles_tab($settings, $option_name)
    {
        ?>
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('🎨 Button Styles', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Show Toggle Button', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[style][show_toggle_button]"
                                value="1" <?php checked(isset($settings['style']['show_toggle_button']) ? $settings['style']['show_toggle_button'] : true); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Show or hide the main toggle button.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Button Size', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[style][button_size]"
                                value="<?php echo esc_attr($settings['style']['button_size']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[style][button_size_unit]">
                                <option value="px" <?php selected($settings['style']['button_size_unit'], 'px'); ?>>px</option>
                                <option value="rem" <?php selected($settings['style']['button_size_unit'], 'rem'); ?>>rem
                                </option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Icon Size', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[style][icon_size]"
                                value="<?php echo esc_attr($settings['style']['icon_size']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[style][icon_size_unit]">
                                <option value="px" <?php selected($settings['style']['icon_size_unit'], 'px'); ?>>px</option>
                                <option value="rem" <?php selected($settings['style']['icon_size_unit'], 'rem'); ?>>rem
                                </option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Open State Button Color', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[style][primary_color]"
                            value="<?php echo esc_attr($settings['style']['primary_color']); ?>" class="wpslt-color-picker">
                        <p class="description">
                            <?php esc_html_e('Button background color when menu is closed', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Open State Icon Color', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[style][open_icon_color]"
                            value="<?php echo esc_attr($settings['style']['open_icon_color']); ?>" class="wpslt-color-picker">
                        <p class="description">
                            <?php esc_html_e('Icon color when menu is closed', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Border Radius', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[style][border_radius]"
                                value="<?php echo esc_attr($settings['style']['border_radius']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[style][border_radius_unit]">
                                <option value="px" <?php selected($settings['style']['border_radius_unit'], 'px'); ?>>px
                                </option>
                                <option value="%" <?php selected($settings['style']['border_radius_unit'], '%'); ?>>%</option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Box Shadow', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[style][box_shadow]" value="1"
                                <?php checked(!empty($settings['style']['box_shadow'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Use Custom Contact Item Sizing', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[style][use_custom_contact_size]"
                                value="1" <?php checked(!empty($settings['style']['use_custom_contact_size'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Enable to use different sizes for contact items vs main button', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Custom Contact Sizing Fields -->
            <div class="wpslt-custom-size-fields"
                style="<?php echo empty($settings['style']['use_custom_contact_size']) ? 'display:none;' : ''; ?>">
                <h4 class="wpslt-section-heading"><?php esc_html_e('Contact Item Custom Sizing', 'wp-slatan-theme'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th>
                            <?php esc_html_e('Contact Button Size', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div class="wpslt-unit-input">
                                <input type="number" name="<?php echo esc_attr($option_name); ?>[style][contact_button_size]"
                                    value="<?php echo esc_attr($settings['style']['contact_button_size']); ?>"
                                    class="small-text">
                                <select name="<?php echo esc_attr($option_name); ?>[style][contact_button_size_unit]">
                                    <option value="px" <?php selected($settings['style']['contact_button_size_unit'], 'px'); ?>>
                                        px</option>
                                    <option value="rem" <?php selected($settings['style']['contact_button_size_unit'], 'rem'); ?>>rem</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Contact Icon Size', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div class="wpslt-unit-input">
                                <input type="number" name="<?php echo esc_attr($option_name); ?>[style][contact_icon_size]"
                                    value="<?php echo esc_attr($settings['style']['contact_icon_size']); ?>" class="small-text">
                                <select name="<?php echo esc_attr($option_name); ?>[style][contact_icon_size_unit]">
                                    <option value="px" <?php selected($settings['style']['contact_icon_size_unit'], 'px'); ?>>px
                                    </option>
                                    <option value="rem" <?php selected($settings['style']['contact_icon_size_unit'], 'rem'); ?>>
                                        rem</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Default State & Remember State -->
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Default State', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][default_state]">
                            <option value="closed" <?php selected($settings['style']['default_state'] ?? 'closed', 'closed'); ?>>
                                <?php esc_html_e('Closed', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="open" <?php selected($settings['style']['default_state'] ?? 'closed', 'open'); ?>>
                                <?php esc_html_e('Open', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Default state of the contact menu on page load.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Remember User State', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[style][remember_state]" value="1"
                                <?php checked(!empty($settings['style']['remember_state'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Remember user preference in browser. If enabled and user closes the menu, it will stay closed on their next visit.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render animations tab
     */
    private function render_animations_tab($settings, $option_name)
    {
        ?>
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('✨ Animation Settings', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Button Animation', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][animation]">
                            <option value="none" <?php selected($settings['style']['animation'], 'none'); ?>>
                                <?php esc_html_e('None', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="pulse" <?php selected($settings['style']['animation'], 'pulse'); ?>>
                                <?php esc_html_e('Pulse', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="bounce" <?php selected($settings['style']['animation'], 'bounce'); ?>>
                                <?php esc_html_e('Bounce', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="shake" <?php selected($settings['style']['animation'], 'shake'); ?>>
                                <?php esc_html_e('Shake', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="wobble" <?php selected($settings['style']['animation'], 'wobble'); ?>>
                                <?php esc_html_e('Wobble', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Continuous animation for the main button.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Hover Animation', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][hover_animation]">
                            <option value="none" <?php selected($settings['style']['hover_animation'] ?? 'none', 'none'); ?>>
                                <?php esc_html_e('None', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="scale" <?php selected($settings['style']['hover_animation'] ?? 'none', 'scale'); ?>>
                                <?php esc_html_e('Scale', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="rotate" <?php selected($settings['style']['hover_animation'] ?? 'scale', 'rotate'); ?>>
                                <?php esc_html_e('Rotate', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="glow" <?php selected($settings['style']['hover_animation'] ?? 'scale', 'glow'); ?>>
                                <?php esc_html_e('Glow', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="swing" <?php selected($settings['style']['hover_animation'] ?? 'scale', 'swing'); ?>>
                                <?php esc_html_e('Swing', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="pop" <?php selected($settings['style']['hover_animation'] ?? 'scale', 'pop'); ?>>
                                <?php esc_html_e('Pop', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Animation when hovering over buttons.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Menu Animation', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][menu_animation]">
                            <option value="slide" <?php selected($settings['style']['menu_animation'] ?? 'slide', 'slide'); ?>>
                                <?php esc_html_e('Slide', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="fade" <?php selected($settings['style']['menu_animation'] ?? 'slide', 'fade'); ?>>
                                <?php esc_html_e('Fade', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="zoom" <?php selected($settings['style']['menu_animation'] ?? 'slide', 'zoom'); ?>>
                                <?php esc_html_e('Zoom', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="flip" <?php selected($settings['style']['menu_animation'] ?? 'slide', 'flip'); ?>>
                                <?php esc_html_e('Flip', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="bounce" <?php selected($settings['style']['menu_animation'] ?? 'slide', 'bounce'); ?>>
                                <?php esc_html_e('Bounce', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Animation for opening/closing the contact menu.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Animation Speed', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][animation_speed]">
                            <option value="fast" <?php selected($settings['style']['animation_speed'] ?? 'normal', 'fast'); ?>>
                                <?php esc_html_e('Fast (0.2s)', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="normal" <?php selected($settings['style']['animation_speed'] ?? 'normal', 'normal'); ?>>
                                <?php esc_html_e('Normal (0.3s)', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="slow" <?php selected($settings['style']['animation_speed'] ?? 'normal', 'slow'); ?>>
                                <?php esc_html_e('Slow (0.4s)', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Duration of all animations', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Menu Item Delay', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[style][menu_item_delay]"
                                value="<?php echo esc_attr($settings['style']['menu_item_delay'] ?? '50'); ?>" step="10"
                                class="small-text">
                            <span>ms</span>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Delay between each contact item appearing (0-500ms)', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render toggle icons tab
     */
    private function render_toggle_icons_tab($settings, $option_name)
    {
        ?>
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('🔄 Toggle Button Settings', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Open Icon', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-icon-input-wrapper">
                            <div class="wpslt-icon-preview">
                                <i
                                    class="<?php echo esc_attr($settings['style']['open_icon'] ?? 'fas fa-comment-dots'); ?>"></i>
                            </div>
                            <input type="text" name="<?php echo esc_attr($option_name); ?>[style][open_icon]"
                                value="<?php echo esc_attr($settings['style']['open_icon'] ?? 'fas fa-comment-dots'); ?>"
                                class="regular-text wpslt-icon-picker-input" placeholder="fas fa-comment-dots">
                            <button type="button" class="button wpslt-icon-picker-btn"
                                title="<?php esc_attr_e('Select Icon', 'wp-slatan-theme'); ?>">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Icon shown when menu is closed.', 'wp-slatan-theme'); ?>
                        </p>
                        <div class="wpslt-custom-icon-wrapper <?php echo !empty($settings['style']['open_custom_icon']) ? 'has-image' : ''; ?>"
                            style="margin-top: 10px;">
                            <div class="wpslt-custom-icon-box">
                                <div class="wpslt-icon-placeholder">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                </div>
                                <?php if (!empty($settings['style']['open_custom_icon'])): ?>
                                    <img src="<?php echo esc_url($settings['style']['open_custom_icon']); ?>" alt="">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="wpslt-remove-icon-btn"
                                title="<?php esc_attr_e('Remove', 'wp-slatan-theme'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                            <input type="hidden" name="<?php echo esc_attr($option_name); ?>[style][open_custom_icon]"
                                value="<?php echo esc_url($settings['style']['open_custom_icon'] ?? ''); ?>"
                                class="wpslt-custom-icon-input">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Close Icon', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-icon-input-wrapper">
                            <div class="wpslt-icon-preview">
                                <i class="<?php echo esc_attr($settings['style']['close_icon'] ?? 'fas fa-times'); ?>"></i>
                            </div>
                            <input type="text" name="<?php echo esc_attr($option_name); ?>[style][close_icon]"
                                value="<?php echo esc_attr($settings['style']['close_icon'] ?? 'fas fa-times'); ?>"
                                class="regular-text wpslt-icon-picker-input" placeholder="fas fa-times">
                            <button type="button" class="button wpslt-icon-picker-btn"
                                title="<?php esc_attr_e('Select Icon', 'wp-slatan-theme'); ?>">
                                <span class="dashicons dashicons-search"></span>
                            </button>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Icon shown when menu is open.', 'wp-slatan-theme'); ?>
                        </p>
                        <div class="wpslt-custom-icon-wrapper <?php echo !empty($settings['style']['close_custom_icon']) ? 'has-image' : ''; ?>"
                            style="margin-top: 10px;">
                            <div class="wpslt-custom-icon-box">
                                <div class="wpslt-icon-placeholder">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                </div>
                                <?php if (!empty($settings['style']['close_custom_icon'])): ?>
                                    <img src="<?php echo esc_url($settings['style']['close_custom_icon']); ?>" alt="">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="wpslt-remove-icon-btn"
                                title="<?php esc_attr_e('Remove', 'wp-slatan-theme'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                            <input type="hidden" name="<?php echo esc_attr($option_name); ?>[style][close_custom_icon]"
                                value="<?php echo esc_url($settings['style']['close_custom_icon'] ?? ''); ?>"
                                class="wpslt-custom-icon-input">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Toggle Animation', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][toggle_animation]">
                            <option value="none" <?php selected($settings['style']['toggle_animation'] ?? 'rotate', 'none'); ?>>
                                <?php esc_html_e('None', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="rotate" <?php selected($settings['style']['toggle_animation'] ?? 'rotate', 'rotate'); ?>>
                                <?php esc_html_e('Rotate', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="flip" <?php selected($settings['style']['toggle_animation'] ?? 'rotate', 'flip'); ?>>
                                <?php esc_html_e('Flip', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="scale-rotate" <?php selected($settings['style']['toggle_animation'] ?? 'rotate', 'scale-rotate'); ?>>
                                <?php esc_html_e('Scale + Rotate', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Animation when toggling between open/close icons.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <!-- Toggle Animation Parameters: Rotate Degrees -->
                <tr class="wpslt-toggle-param wpslt-toggle-param-rotate"
                    style="<?php echo ($settings['style']['toggle_animation'] ?? 'rotate') === 'rotate' ? '' : 'display:none;'; ?>">
                    <th>
                        <?php esc_html_e('Rotate Degrees', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][toggle_rotate_deg]">
                            <option value="90" <?php selected($settings['style']['toggle_rotate_deg'] ?? '180', '90'); ?>>
                                90°
                            </option>
                            <option value="180" <?php selected($settings['style']['toggle_rotate_deg'] ?? '180', '180'); ?>>
                                180°
                            </option>
                            <option value="360" <?php selected($settings['style']['toggle_rotate_deg'] ?? '180', '360'); ?>>
                                360°
                            </option>
                        </select>
                    </td>
                </tr>
                <!-- Toggle Animation Parameters: Flip Axis -->
                <tr class="wpslt-toggle-param wpslt-toggle-param-flip"
                    style="<?php echo ($settings['style']['toggle_animation'] ?? 'rotate') === 'flip' ? '' : 'display:none;'; ?>">
                    <th>
                        <?php esc_html_e('Flip Axis', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][toggle_flip_axis]">
                            <option value="X" <?php selected($settings['style']['toggle_flip_axis'] ?? 'Y', 'X'); ?>>
                                <?php esc_html_e('X-axis (Horizontal)', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="Y" <?php selected($settings['style']['toggle_flip_axis'] ?? 'Y', 'Y'); ?>>
                                <?php esc_html_e('Y-axis (Vertical)', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <!-- Toggle Animation Parameters: Scale + Rotate -->
                <tr class="wpslt-toggle-param wpslt-toggle-param-scale-rotate"
                    style="<?php echo ($settings['style']['toggle_animation'] ?? 'rotate') === 'scale-rotate' ? '' : 'display:none;'; ?>">
                    <th>
                        <?php esc_html_e('Scale Factor', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][toggle_scale_min]">
                            <option value="0.5" <?php selected($settings['style']['toggle_scale_min'] ?? '0.7', '0.5'); ?>>
                                0.5 (<?php esc_html_e('Small', 'wp-slatan-theme'); ?>)
                            </option>
                            <option value="0.7" <?php selected($settings['style']['toggle_scale_min'] ?? '0.7', '0.7'); ?>>
                                0.7 (<?php esc_html_e('Medium', 'wp-slatan-theme'); ?>)
                            </option>
                            <option value="0.9" <?php selected($settings['style']['toggle_scale_min'] ?? '0.7', '0.9'); ?>>
                                0.9 (<?php esc_html_e('Large', 'wp-slatan-theme'); ?>)
                            </option>
                        </select>
                    </td>
                </tr>
                <tr class="wpslt-toggle-param wpslt-toggle-param-scale-rotate"
                    style="<?php echo ($settings['style']['toggle_animation'] ?? 'rotate') === 'scale-rotate' ? '' : 'display:none;'; ?>">
                    <th>
                        <?php esc_html_e('Rotation Degrees', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[style][toggle_scale_rotate_deg]">
                            <option value="180" <?php selected($settings['style']['toggle_scale_rotate_deg'] ?? '360', '180'); ?>>
                                180°
                            </option>
                            <option value="360" <?php selected($settings['style']['toggle_scale_rotate_deg'] ?? '360', '360'); ?>>
                                360°
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Close State Button Color', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[style][close_bg_color]"
                            value="<?php echo esc_attr($settings['style']['close_bg_color'] ?? ''); ?>"
                            class="wpslt-color-picker">
                        <p class="description">
                            <?php esc_html_e('Button background color when menu is open (leave empty to use Open State Button Color)', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Close State Icon Color', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[style][close_icon_color]"
                            value="<?php echo esc_attr($settings['style']['close_icon_color'] ?? ''); ?>"
                            class="wpslt-color-picker">
                        <p class="description">
                            <?php esc_html_e('Icon color when menu is open (leave empty to use white)', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Main Button Tooltip Text', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[style][close_tooltip_text]"
                            value="<?php echo esc_attr($settings['style']['close_tooltip_text'] ?? 'Contact Us'); ?>"
                            class="regular-text" placeholder="Contact Us">
                        <p class="description">
                            <?php esc_html_e('Tooltip text shown when hovering the main button.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render advanced tab
     */
    private function render_advanced_tab($settings, $option_name)
    {
        ?>
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('⚙️ Advanced Settings', 'wp-slatan-theme'); ?>
            </h3>

            <h4 class="wpslt-section-heading"><?php esc_html_e('Spacing & Layout', 'wp-slatan-theme'); ?></h4>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Contact Items Gap', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[spacing][contact_gap]"
                                value="<?php echo esc_attr($settings['spacing']['contact_gap']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[spacing][contact_gap_unit]">
                                <option value="px" <?php selected($settings['spacing']['contact_gap_unit'], 'px'); ?>>px
                                </option>
                                <option value="rem" <?php selected($settings['spacing']['contact_gap_unit'], 'rem'); ?>>rem
                                </option>
                            </select>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Space between contact items', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Z-Index', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="number" name="<?php echo esc_attr($option_name); ?>[spacing][z_index]"
                            value="<?php echo esc_attr($settings['spacing']['z_index']); ?>" class="small-text">
                        <p class="description">
                            <?php esc_html_e('Stack order of the widget (higher appears on top). Default: 99999', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h4 class="wpslt-section-heading"><?php esc_html_e('Accessibility', 'wp-slatan-theme'); ?></h4>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Enable Keyboard Navigation', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox"
                                name="<?php echo esc_attr($option_name); ?>[accessibility][enable_keyboard_nav]" value="1" <?php checked(!empty($settings['accessibility']['enable_keyboard_nav'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Allow users to navigate the widget with keyboard (Tab, Enter, Space)', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('High Contrast Mode', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox"
                                name="<?php echo esc_attr($option_name); ?>[accessibility][high_contrast_mode]" value="1" <?php checked(!empty($settings['accessibility']['high_contrast_mode'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Enhance visibility with borders and higher contrast', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Custom ARIA Label', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[accessibility][custom_aria_label]"
                            value="<?php echo esc_attr($settings['accessibility']['custom_aria_label']); ?>"
                            class="regular-text" placeholder="Contact Us">
                        <p class="description">
                            <?php esc_html_e('Custom label for screen readers (leave empty for default)', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render tooltip tab
     */
    private function render_tooltip_tab($settings, $option_name)
    {
        $tooltip = $settings['tooltip'];
        ?>
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('💬 Tooltip Settings', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Enable Tooltip', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[tooltip][enabled]" value="1"
                                <?php checked(!empty($tooltip['enabled'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Show on Mobile', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[tooltip][show_mobile]" value="1"
                                <?php checked(!empty($tooltip['show_mobile'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('If enabled, tooltips will be visible on mobile devices (tap to see). Default is hidden.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Background Color', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[tooltip][background]"
                            value="<?php echo esc_attr($tooltip['background']); ?>" class="wpslt-color-picker">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Text Color', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($option_name); ?>[tooltip][text_color]"
                            value="<?php echo esc_attr($tooltip['text_color']); ?>" class="wpslt-color-picker">
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Font Size', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[tooltip][font_size]"
                                value="<?php echo esc_attr($tooltip['font_size']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[tooltip][font_size_unit]">
                                <option value="px" <?php selected($tooltip['font_size_unit'], 'px'); ?>>px
                                </option>
                                <option value="rem" <?php selected($tooltip['font_size_unit'], 'rem'); ?>>
                                    rem
                                </option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Border Radius', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[tooltip][border_radius]"
                                value="<?php echo esc_attr($tooltip['border_radius']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[tooltip][border_radius_unit]">
                                <option value="px" <?php selected($tooltip['border_radius_unit'], 'px'); ?>>
                                    px
                                </option>
                                <option value="%" <?php selected($tooltip['border_radius_unit'], '%'); ?>>%
                                </option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Position', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[tooltip][position]">
                            <option value="auto" <?php selected($tooltip['position'], 'auto'); ?>>
                                <?php esc_html_e('Auto', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="left" <?php selected($tooltip['position'], 'left'); ?>>
                                <?php esc_html_e('Left', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="right" <?php selected($tooltip['position'], 'right'); ?>>
                                <?php esc_html_e('Right', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Display Mode', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[tooltip][display_mode]">
                            <option value="hover" <?php selected($tooltip['display_mode'] ?? 'hover', 'hover'); ?>>
                                <?php esc_html_e('Show on Hover', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="always" <?php selected($tooltip['display_mode'] ?? 'hover', 'always'); ?>>
                                <?php esc_html_e('Always Show', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Choose whether tooltips appear on hover or are always visible.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    /**
     * Render position tab
     */
    private function render_position_tab($settings, $option_name)
    {
        ?>
        <!-- Desktop Position -->
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('🖥️ Desktop Position', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Horizontal Position', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[position][horizontal]">
                            <option value="left" <?php selected($settings['position']['horizontal'], 'left'); ?>>
                                <?php esc_html_e('Left', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="right" <?php selected($settings['position']['horizontal'], 'right'); ?>>
                                <?php esc_html_e('Right', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Vertical Position', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[position][vertical]">
                            <option value="top" <?php selected($settings['position']['vertical'], 'top'); ?>>
                                <?php esc_html_e('Top', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="bottom" <?php selected($settings['position']['vertical'], 'bottom'); ?>>
                                <?php esc_html_e('Bottom', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Horizontal Offset', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[position][offset_x]"
                                value="<?php echo esc_attr($settings['position']['offset_x']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[position][offset_x_unit]">
                                <option value="px" <?php selected($settings['position']['offset_x_unit'], 'px'); ?>>px
                                </option>
                                <option value="rem" <?php selected($settings['position']['offset_x_unit'], 'rem'); ?>>
                                    rem
                                </option>
                                <option value="%" <?php selected($settings['position']['offset_x_unit'], '%'); ?>>%
                                </option>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Vertical Offset', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <div class="wpslt-unit-input">
                            <input type="number" name="<?php echo esc_attr($option_name); ?>[position][offset_y]"
                                value="<?php echo esc_attr($settings['position']['offset_y']); ?>" class="small-text">
                            <select name="<?php echo esc_attr($option_name); ?>[position][offset_y_unit]">
                                <option value="px" <?php selected($settings['position']['offset_y_unit'], 'px'); ?>>px
                                </option>
                                <option value="rem" <?php selected($settings['position']['offset_y_unit'], 'rem'); ?>>
                                    rem
                                </option>
                                <option value="%" <?php selected($settings['position']['offset_y_unit'], '%'); ?>>%
                                </option>
                            </select>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Mobile Position Override -->
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('📱 Mobile Position Override', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Override Position on Mobile', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[mobile][override_position]"
                                value="1" <?php checked(!empty($settings['mobile']['override_position'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Use different position settings for mobile devices (< 768px)', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Mobile Position Fields -->
            <div class="wpslt-mobile-position-fields"
                style="<?php echo empty($settings['mobile']['override_position']) ? 'display:none;' : ''; ?>">
                <h4 class="wpslt-section-heading"><?php esc_html_e('Mobile Position Settings', 'wp-slatan-theme'); ?>
                </h4>
                <table class="form-table">
                    <tr>
                        <th>
                            <?php esc_html_e('Horizontal Position', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr($option_name); ?>[mobile][horizontal]">
                                <option value="left" <?php selected($settings['mobile']['horizontal'] ?? 'right', 'left'); ?>>
                                    <?php esc_html_e('Left', 'wp-slatan-theme'); ?>
                                </option>
                                <option value="right" <?php selected($settings['mobile']['horizontal'] ?? 'right', 'right'); ?>>
                                    <?php esc_html_e('Right', 'wp-slatan-theme'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Vertical Position', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <select name="<?php echo esc_attr($option_name); ?>[mobile][vertical]">
                                <option value="top" <?php selected($settings['mobile']['vertical'] ?? 'bottom', 'top'); ?>>
                                    <?php esc_html_e('Top', 'wp-slatan-theme'); ?>
                                </option>
                                <option value="bottom" <?php selected($settings['mobile']['vertical'] ?? 'bottom', 'bottom'); ?>>
                                    <?php esc_html_e('Bottom', 'wp-slatan-theme'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Horizontal Offset', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div class="wpslt-unit-input">
                                <input type="number" name="<?php echo esc_attr($option_name); ?>[mobile][offset_x]"
                                    value="<?php echo esc_attr($settings['mobile']['offset_x'] ?? '20'); ?>" class="small-text">
                                <select name="<?php echo esc_attr($option_name); ?>[mobile][offset_x_unit]">
                                    <option value="px" <?php selected($settings['mobile']['offset_x_unit'] ?? 'px', 'px'); ?>>px
                                    </option>
                                    <option value="rem" <?php selected($settings['mobile']['offset_x_unit'] ?? 'px', 'rem'); ?>>
                                        rem</option>
                                    <option value="%" <?php selected($settings['mobile']['offset_x_unit'] ?? 'px', '%'); ?>>%
                                    </option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Vertical Offset', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div class="wpslt-unit-input">
                                <input type="number" name="<?php echo esc_attr($option_name); ?>[mobile][offset_y]"
                                    value="<?php echo esc_attr($settings['mobile']['offset_y'] ?? '20'); ?>" class="small-text">
                                <select name="<?php echo esc_attr($option_name); ?>[mobile][offset_y_unit]">
                                    <option value="px" <?php selected($settings['mobile']['offset_y_unit'] ?? 'px', 'px'); ?>>px
                                    </option>
                                    <option value="rem" <?php selected($settings['mobile']['offset_y_unit'] ?? 'px', 'rem'); ?>>
                                        rem</option>
                                    <option value="%" <?php selected($settings['mobile']['offset_y_unit'] ?? 'px', '%'); ?>>%
                                    </option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Mobile Size Override -->
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('📱 Mobile Size Override', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Override Button Size on Mobile', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[mobile][override_size]" value="1"
                                <?php checked(!empty($settings['mobile']['override_size'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Use different button sizes for mobile devices (< 768px)', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <!-- Mobile Size Fields -->
            <div class="wpslt-mobile-size-fields"
                style="<?php echo empty($settings['mobile']['override_size']) ? 'display:none;' : ''; ?>">
                <h4 class="wpslt-section-heading"><?php esc_html_e('Mobile Button Size Settings', 'wp-slatan-theme'); ?>
                </h4>
                <table class="form-table">
                    <tr>
                        <th>
                            <?php esc_html_e('Button Size', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div class="wpslt-unit-input">
                                <input type="number" name="<?php echo esc_attr($option_name); ?>[mobile][button_size]"
                                    value="<?php echo esc_attr($settings['mobile']['button_size'] ?? '50'); ?>"
                                    class="small-text">
                                <select name="<?php echo esc_attr($option_name); ?>[mobile][button_size_unit]">
                                    <option value="px" <?php selected($settings['mobile']['button_size_unit'] ?? 'px', 'px'); ?>>px</option>
                                    <option value="rem" <?php selected($settings['mobile']['button_size_unit'] ?? 'px', 'rem'); ?>>rem</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <?php esc_html_e('Icon Size', 'wp-slatan-theme'); ?>
                        </th>
                        <td>
                            <div class="wpslt-unit-input">
                                <input type="number" name="<?php echo esc_attr($option_name); ?>[mobile][icon_size]"
                                    value="<?php echo esc_attr($settings['mobile']['icon_size'] ?? '20'); ?>"
                                    class="small-text">
                                <select name="<?php echo esc_attr($option_name); ?>[mobile][icon_size_unit]">
                                    <option value="px" <?php selected($settings['mobile']['icon_size_unit'] ?? 'px', 'px'); ?>>
                                        px</option>
                                    <option value="rem" <?php selected($settings['mobile']['icon_size_unit'] ?? 'px', 'rem'); ?>>rem</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render display tab
     */
    private function render_display_tab($settings, $option_name)
    {
        ?>
        <div class="wpslt-card">
            <h3>
                <?php esc_html_e('🔧 Display Settings', 'wp-slatan-theme'); ?>
            </h3>
            <table class="form-table">
                <tr>
                    <th>
                        <?php esc_html_e('Show on Desktop', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[display][desktop]" value="1"
                                <?php checked(!empty($settings['display']['desktop'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Show on Mobile', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[display][mobile]" value="1" <?php checked(!empty($settings['display']['mobile'])); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Page Display', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <select name="<?php echo esc_attr($option_name); ?>[display][pages]" id="wpslt-display-pages">
                            <option value="all" <?php selected($settings['display']['pages'], 'all'); ?>>
                                <?php esc_html_e('All Pages', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="include" <?php selected($settings['display']['pages'], 'include'); ?>>
                                <?php esc_html_e('Only Selected Pages', 'wp-slatan-theme'); ?>
                            </option>
                            <option value="exclude" <?php selected($settings['display']['pages'], 'exclude'); ?>>
                                <?php esc_html_e('Exclude Selected Pages', 'wp-slatan-theme'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr id="wpslt-page-ids-row"
                    style="<?php echo $settings['display']['pages'] === 'all' ? 'display:none;' : ''; ?>">
                    <th>
                        <?php esc_html_e('Select Pages', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <input type="hidden" name="<?php echo esc_attr($option_name); ?>[display][page_ids]" value="">
                        <div class="wpslt-checkbox-list"
                            style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background: #fff;">
                            <?php
                            $pages = get_pages();
                            if (!empty($pages)) {
                                foreach ($pages as $page) {
                                    $checked = in_array($page->ID, $settings['display']['page_ids']) ? 'checked' : '';
                                    ?>
                                    <label style="display: block; margin-bottom: 8px; cursor: pointer;">
                                        <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[display][page_ids][]"
                                            value="<?php echo esc_attr($page->ID); ?>" <?php echo $checked; ?>
                                            style="margin-right: 8px;">
                                        <?php echo esc_html($page->post_title); ?>
                                    </label>
                                    <?php
                                }
                            } else {
                                echo '<p style="color: #666; margin: 0;">' . esc_html__('No pages found', 'wp-slatan-theme') . '</p>';
                            }
                            ?>
                        </div>
                        <p class="description">
                            <?php esc_html_e('Select the pages where you want to show/hide the floating contact button.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Load Font Awesome', 'wp-slatan-theme'); ?>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="<?php echo esc_attr($option_name); ?>[display][load_fontawesome]"
                                value="1" <?php checked($settings['display']['load_fontawesome']); ?>>
                            <span class="wpslt-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Load Font Awesome from CDN. Disable if your theme already includes Font Awesome.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}

// Initialize
WPSLT_Contact_Admin::get_instance();

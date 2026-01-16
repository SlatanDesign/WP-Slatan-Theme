<?php
/**
 * Theme Settings / Control Panel for WP Slatan Theme
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Theme_Settings
 *
 * Handles theme settings page and options
 */
class WPSLT_Theme_Settings
{

    /**
     * Option name for settings
     *
     * @var string
     */
    const OPTION_NAME = 'wpslt_settings';

    /**
     * Settings page slug
     *
     * @var string
     */
    const PAGE_SLUG = 'wpslt-settings';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add theme settings page to admin menu
     */
    public function add_menu_page()
    {
        // Custom SVG icon for admin menu (Base64 encoded)
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">' .
            '<path d="M15.268 4.71199C16.6536 4.25524 17 2.71368 17 2H7.90722C5.02062 2.57095 3 4.14105 3 8.56588C4.15464 13.2762 7.61856 12.9907 10.0722 12.9907C11.3711 12.1343 11.067 12.4282 11.5 12L10.5 11C10.3563 10.8563 8.67698 10.3739 7.90722 10.2787C4.01031 8.42314 6.31959 4.71199 7.90722 4.71199C9.92784 4.28378 13.8825 4.71199 15.268 4.71199Z" fill="black"/>' .
            '<path d="M13.3918 11.8488L9.20619 7.70946H13.1031L17 11.8488C15.0756 13.8471 10.9381 18.0436 9.78351 18.8429C8.5134 19.2997 7.3299 18.6526 6.89691 18.272L13.3918 11.8488Z" fill="black"/>' .
            '</svg>'
        );

        add_menu_page(
            __('WP Slatan Theme', 'wp-slatan-theme'),
            __('Slatan Theme', 'wp-slatan-theme'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page'),
            $icon_svg,
            59
        );

        // Rename the first submenu item from "Slatan Theme" to "Theme Settings"
        add_submenu_page(
            self::PAGE_SLUG,
            __('Theme Settings', 'wp-slatan-theme'),
            __('Theme Settings', 'wp-slatan-theme'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting(
            'wpslt_settings_group',
            self::OPTION_NAME,
            array($this, 'sanitize_settings')
        );

        // General Settings Section
        add_settings_section(
            'wpslt_general_section',
            __('General Settings', 'wp-slatan-theme'),
            array($this, 'render_general_section'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'container_width',
            __('Container Width (px)', 'wp-slatan-theme'),
            array($this, 'render_number_field'),
            self::PAGE_SLUG,
            'wpslt_general_section',
            array(
                'id' => 'container_width',
                'default' => 1140,
                'description' => __('Maximum width for content container.', 'wp-slatan-theme'),
            )
        );

        // Layout Section
        add_settings_section(
            'wpslt_layout_section',
            __('Layout', 'wp-slatan-theme'),
            array($this, 'render_layout_section'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'disable_default_header',
            __('Disable Default Header', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_layout_section',
            array(
                'id' => 'disable_default_header',
                'description' => __('Hide the default theme header (use when building header with Elementor).', 'wp-slatan-theme'),
            )
        );

        add_settings_field(
            'disable_default_footer',
            __('Disable Default Footer', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_layout_section',
            array(
                'id' => 'disable_default_footer',
                'description' => __('Hide the default theme footer (use when building footer with Elementor).', 'wp-slatan-theme'),
            )
        );

        // Performance Section
        add_settings_section(
            'wpslt_performance_section',
            __('Performance', 'wp-slatan-theme'),
            array($this, 'render_performance_section'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'disable_emojis',
            __('Disable Emojis', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_performance_section',
            array(
                'id' => 'disable_emojis',
                'description' => __('Remove WordPress emoji scripts (enabled by default).', 'wp-slatan-theme'),
                'default' => true,
            )
        );

        add_settings_field(
            'remove_jquery_migrate',
            __('Remove jQuery Migrate', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_performance_section',
            array(
                'id' => 'remove_jquery_migrate',
                'description' => __('Remove jQuery Migrate (~10KB, enabled by default).', 'wp-slatan-theme'),
                'default' => true,
            )
        );

        add_settings_field(
            'defer_scripts',
            __('Defer Scripts', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_performance_section',
            array(
                'id' => 'defer_scripts',
                'description' => __('Defer non-critical scripts (enabled by default).', 'wp-slatan-theme'),
                'default' => true,
            )
        );

        add_settings_field(
            'clean_header',
            __('Clean Header', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_performance_section',
            array(
                'id' => 'clean_header',
                'description' => __('Remove unnecessary meta tags (enabled by default).', 'wp-slatan-theme'),
                'default' => true,
            )
        );

        add_settings_field(
            'disable_self_pingbacks',
            __('Disable Self-Pingbacks', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_performance_section',
            array(
                'id' => 'disable_self_pingbacks',
                'description' => __('Prevent pingbacks to your own site (enabled by default).', 'wp-slatan-theme'),
                'default' => true,
            )
        );

        // Security Section
        add_settings_section(
            'wpslt_security_section',
            __('Security', 'wp-slatan-theme'),
            array($this, 'render_security_section'),
            self::PAGE_SLUG
        );

        add_settings_field(
            'disable_xmlrpc',
            __('Disable XML-RPC', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_security_section',
            array(
                'id' => 'disable_xmlrpc',
                'description' => __('Disable XML-RPC functionality (recommended if not using remote publishing).', 'wp-slatan-theme'),
            )
        );

        add_settings_field(
            'disable_file_editor',
            __('Disable File Editor', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_security_section',
            array(
                'id' => 'disable_file_editor',
                'description' => __('Disable theme/plugin file editor in admin.', 'wp-slatan-theme'),
            )
        );

        add_settings_field(
            'hide_login_errors',
            __('Hide Login Errors', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_security_section',
            array(
                'id' => 'hide_login_errors',
                'description' => __('Show generic error message on failed login (prevents username enumeration).', 'wp-slatan-theme'),
            )
        );

        add_settings_field(
            'limit_login_attempts',
            __('Limit Login Attempts', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_security_section',
            array(
                'id' => 'limit_login_attempts',
                'description' => __('Limit to 5 failed login attempts per 15 minutes.', 'wp-slatan-theme'),
            )
        );

        add_settings_field(
            'disable_author_archives',
            __('Disable Author Archives', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_security_section',
            array(
                'id' => 'disable_author_archives',
                'description' => __('Redirect author archive pages to homepage (prevents username enumeration).', 'wp-slatan-theme'),
            )
        );

        add_settings_field(
            'disable_rest_users',
            __('Disable REST API User Enumeration', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_security_section',
            array(
                'id' => 'disable_rest_users',
                'description' => __('Block /wp/v2/users endpoint for non-logged-in users.', 'wp-slatan-theme'),
            )
        );

        add_settings_field(
            'remove_version_strings',
            __('Remove Version Strings', 'wp-slatan-theme'),
            array($this, 'render_checkbox_field'),
            self::PAGE_SLUG,
            'wpslt_security_section',
            array(
                'id' => 'remove_version_strings',
                'description' => __('Remove version query strings from CSS/JS files.', 'wp-slatan-theme'),
            )
        );
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input Input values.
     * @return array
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        // Container width
        if (isset($input['container_width'])) {
            $sanitized['container_width'] = absint($input['container_width']);
            if ($sanitized['container_width'] < 320) {
                $sanitized['container_width'] = 320;
            }
            if ($sanitized['container_width'] > 2560) {
                $sanitized['container_width'] = 2560;
            }
        }

        // Revisions limit
        if (isset($input['revisions_limit'])) {
            $sanitized['revisions_limit'] = absint($input['revisions_limit']);
            if ($sanitized['revisions_limit'] > 100) {
                $sanitized['revisions_limit'] = 100;
            }
        }

        // Checkbox fields
        $checkbox_fields = array(
            // General
            'disable_default_header',
            'disable_default_footer',
            // Security
            'disable_xmlrpc',
            'disable_file_editor',
            'hide_login_errors',
            'limit_login_attempts',
            'disable_author_archives',
            'disable_rest_users',
            'remove_version_strings',
            // Performance
            'disable_emojis',
            'disable_dashicons',
            'disable_oembed',
            'disable_heartbeat_frontend',
            'reduce_heartbeat',
            'remove_jquery_migrate',
            'defer_scripts',
            'clean_header',
            'disable_self_pingbacks',
        );

        foreach ($checkbox_fields as $field) {
            $sanitized[$field] = !empty($input[$field]) ? 1 : 0;
        }

        return $sanitized;
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
            return;
        }

        $css_path = WPSLT_URI . '/assets/admin/css/';
        $js_path = WPSLT_URI . '/assets/admin/js/';

        // Base CSS (shared styles)
        wp_enqueue_style(
            'wpslt-admin-base',
            $css_path . 'base.css',
            array(),
            WPSLT_VERSION
        );

        // Tab-specific CSS
        wp_enqueue_style('wpslt-admin-tab-general', $css_path . 'tab-general.css', array('wpslt-admin-base'), WPSLT_VERSION);
        wp_enqueue_style('wpslt-admin-tab-security', $css_path . 'tab-security.css', array('wpslt-admin-base'), WPSLT_VERSION);
        wp_enqueue_style('wpslt-admin-tab-performance', $css_path . 'tab-performance.css', array('wpslt-admin-base'), WPSLT_VERSION);

        // Base JS (core functionality)
        wp_enqueue_script(
            'wpslt-admin-base',
            $js_path . 'base.js',
            array('jquery'),
            WPSLT_VERSION,
            true
        );

        // Tab-specific JS
        wp_enqueue_script('wpslt-admin-tab-general', $js_path . 'tab-general.js', array('wpslt-admin-base'), WPSLT_VERSION, true);
        wp_enqueue_script('wpslt-admin-tab-security', $js_path . 'tab-security.js', array('wpslt-admin-base'), WPSLT_VERSION, true);
        wp_enqueue_script('wpslt-admin-tab-performance', $js_path . 'tab-performance.js', array('wpslt-admin-base'), WPSLT_VERSION, true);
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Load tab partials
        require_once WPSLT_DIR . '/inc/admin/tab-general.php';
        require_once WPSLT_DIR . '/inc/admin/tab-security.php';
        require_once WPSLT_DIR . '/inc/admin/tab-performance.php';

        $settings = get_option(self::OPTION_NAME, array());
        ?>
        <div class="wrap wpslt-settings-wrap">
            <div class="wpslt-settings-header">
                <?php
                $logo_path = WPSLT_URI . '/assets/images/logo.png';
                if (file_exists(WPSLT_DIR . '/assets/images/logo.png')):
                    ?>
                    <img src="<?php echo esc_url($logo_path); ?>" alt="WP Slatan Theme">
                <?php endif; ?>
                <div>
                    <h1>
                        <?php esc_html_e('WP Slatan Theme', 'wp-slatan-theme'); ?>
                        <span class="wpslt-version">v<?php echo esc_html(WPSLT_VERSION); ?></span>
                    </h1>
                    <p class="wpslt-tagline"><?php esc_html_e('Fast • Secure • Elementor Ready', 'wp-slatan-theme'); ?></p>
                </div>
            </div>

            <?php settings_errors(); ?>

            <div class="wpslt-tabs">
                <button type="button" class="wpslt-tab active"
                    data-tab="general"><?php esc_html_e('General', 'wp-slatan-theme'); ?></button>
                <button type="button" class="wpslt-tab"
                    data-tab="security"><?php esc_html_e('Security', 'wp-slatan-theme'); ?></button>
                <button type="button" class="wpslt-tab"
                    data-tab="performance"><?php esc_html_e('Performance', 'wp-slatan-theme'); ?></button>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('wpslt_settings_group'); ?>

                <!-- General Tab -->
                <div id="wpslt-tab-general" class="wpslt-tab-content active">
                    <?php wpslt_render_tab_general($settings); ?>
                </div>

                <!-- Security Tab -->
                <div id="wpslt-tab-security" class="wpslt-tab-content">
                    <?php wpslt_render_tab_security($settings); ?>
                </div>

                <!-- Performance Tab -->
                <div id="wpslt-tab-performance" class="wpslt-tab-content">
                    <?php wpslt_render_tab_performance($settings); ?>
                </div>

                <div class="wpslt-submit-wrap">
                    <?php submit_button(__('Save Settings', 'wp-slatan-theme'), 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render general section description
     */
    public function render_general_section()
    {
        echo '<p class="wpslt-section-description">' .
            esc_html__('Configure general theme settings.', 'wp-slatan-theme') .
            '</p>';
    }

    /**
     * Render layout section description
     */
    public function render_layout_section()
    {
        echo '<p class="wpslt-section-description">' .
            esc_html__('Control header and footer display. Use these options when building with Elementor Theme Builder.', 'wp-slatan-theme') .
            '</p>';
    }

    /**
     * Render performance section description
     */
    public function render_performance_section()
    {
        echo '<p class="wpslt-section-description">' .
            esc_html__('Optimize theme performance by disabling unnecessary features.', 'wp-slatan-theme') .
            '</p>';
    }

    /**
     * Render security section description
     */
    public function render_security_section()
    {
        echo '<p class="wpslt-section-description">' .
            esc_html__('Harden your WordPress installation with these security options.', 'wp-slatan-theme') .
            '</p>';
    }

    /**
     * Render checkbox field
     *
     * @param array $args Field arguments.
     */
    public function render_checkbox_field($args)
    {
        $settings = get_option(self::OPTION_NAME, array());
        $id = $args['id'];
        $default = isset($args['default']) ? $args['default'] : false;
        $checked = isset($settings[$id]) ? $settings[$id] : $default;
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME . '[' . $id . ']'); ?>" value="1" <?php checked($checked, 1); ?>>
            <?php
            if (!empty($args['description'])) {
                echo esc_html($args['description']);
            }
            ?>
        </label>
        <?php
    }

    /**
     * Render number field
     *
     * @param array $args Field arguments.
     */
    public function render_number_field($args)
    {
        $settings = get_option(self::OPTION_NAME, array());
        $id = $args['id'];
        $default = isset($args['default']) ? $args['default'] : 0;
        $value = isset($settings[$id]) ? $settings[$id] : $default;
        ?>
        <input type="number" name="<?php echo esc_attr(self::OPTION_NAME . '[' . $id . ']'); ?>"
            value="<?php echo esc_attr($value); ?>" class="small-text" min="320" max="2560">
        <?php
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
}

// Initialize settings
new WPSLT_Theme_Settings();

/**
 * Output custom CSS variables based on settings
 */
function wpslt_output_custom_css()
{
    $settings = get_option(WPSLT_Theme_Settings::OPTION_NAME, array());
    $container_width = isset($settings['container_width']) ? absint($settings['container_width']) : 1140;

    $css = sprintf(
        ':root { --wpslt-container-width: %dpx; }',
        $container_width
    );

    wp_add_inline_style('wp-slatan-theme-style', $css);
}
add_action('wp_enqueue_scripts', 'wpslt_output_custom_css', 20);

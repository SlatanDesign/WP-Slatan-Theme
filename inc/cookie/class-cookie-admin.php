<?php
/**
 * Cookie Consent Admin Settings
 *
 * @package WP_Slatan_Theme
 * @since 1.0.22
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cookie Consent Admin Class
 */
class WPSLT_Cookie_Admin
{

    /**
     * Option name for settings
     */
    const OPTION_NAME = 'wpslt_cookie_consent_settings';

    /**
     * Settings page slug
     */
    const PAGE_SLUG = 'wpslt-cookie-consent';

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Settings
     */
    private $settings = array();

    /**
     * Get instance
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
        $this->settings = $this->get_settings();

        add_action('admin_menu', array($this, 'add_admin_menu'), 99);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Get default settings
     */
    public function get_defaults()
    {
        return array(
            // General
            'enabled' => false,
            'cookie_name' => 'wpslt_cookie_consent',
            'cookie_expiry' => 365,
            'show_revisit' => true,
            'revisit_position' => 'bottom-left',

            // Banner
            'banner_type' => 'bar-bottom',
            'banner_bg_color' => '#1a1a2e',
            'banner_text_color' => '#ffffff',
            'accept_bg_color' => '#16a085',
            'accept_text_color' => '#ffffff',
            'reject_bg_color' => '#e74c3c',
            'reject_text_color' => '#ffffff',
            'settings_bg_color' => 'transparent',
            'settings_text_color' => '#ffffff',
            'animation' => 'slide',

            // Content
            'title' => __('We value your privacy', 'wp-slatan-theme'),
            'message' => __('We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.', 'wp-slatan-theme'),
            'accept_text' => __('Accept All', 'wp-slatan-theme'),
            'reject_text' => __('Reject All', 'wp-slatan-theme'),
            'settings_text' => __('Cookie Settings', 'wp-slatan-theme'),
            'save_text' => __('Save Preferences', 'wp-slatan-theme'),
            'close_text' => __('Close', 'wp-slatan-theme'),
            'read_more_text' => __('Read More', 'wp-slatan-theme'),
            'read_more_url' => '',

            // Categories
            'categories' => array(
                array(
                    'id' => 'necessary',
                    'name' => __('Necessary', 'wp-slatan-theme'),
                    'description' => __('These cookies are essential for the website to function properly. They enable basic functions like page navigation and access to secure areas.', 'wp-slatan-theme'),
                    'is_necessary' => true,
                    'default_state' => true,
                ),
                array(
                    'id' => 'analytics',
                    'name' => __('Analytics', 'wp-slatan-theme'),
                    'description' => __('These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.', 'wp-slatan-theme'),
                    'is_necessary' => false,
                    'default_state' => false,
                ),
                array(
                    'id' => 'marketing',
                    'name' => __('Marketing', 'wp-slatan-theme'),
                    'description' => __('These cookies are used to track visitors across websites to display relevant advertisements.', 'wp-slatan-theme'),
                    'is_necessary' => false,
                    'default_state' => false,
                ),
            ),

            // Scripts - Built-in Integrations
            'google_analytics_id' => '',
            'google_tag_manager_id' => '',
            'facebook_pixel_id' => '',
            'tiktok_pixel_id' => '',

            // Scripts - Custom Scripts per Category
            'head_scripts_necessary' => '',
            'head_scripts_analytics' => '',
            'head_scripts_marketing' => '',
            'body_scripts_necessary' => '',
            'body_scripts_analytics' => '',
            'body_scripts_marketing' => '',

            // Scripts - URL Pattern Blocking
            'block_patterns' => array(),

            // Auto-blocking
            'auto_block_known_scripts' => true,
        );
    }

    /**
     * Get settings
     */
    public function get_settings()
    {
        $saved = get_option(self::OPTION_NAME, array());
        return wp_parse_args($saved, $this->get_defaults());
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'wpslt-settings',
            __('Cookie Consent', 'wp-slatan-theme'),
            __('Cookie Consent', 'wp-slatan-theme'),
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
            self::OPTION_NAME . '_group',
            self::OPTION_NAME,
            array($this, 'sanitize_settings')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, self::PAGE_SLUG) === false) {
            return;
        }

        // Base admin styles
        wp_enqueue_style(
            'wpslt-admin-base',
            WPSLT_URI . '/assets/admin/css/base.css',
            array(),
            WPSLT_VERSION
        );

        // Cookie admin styles
        wp_enqueue_style(
            'wpslt-cookie-admin',
            WPSLT_URI . '/assets/admin/css/cookie.css',
            array('wp-color-picker'),
            WPSLT_VERSION
        );

        // Cookie admin script
        wp_enqueue_script(
            'wpslt-cookie-admin',
            WPSLT_URI . '/assets/admin/js/cookie.js',
            array('jquery', 'wp-color-picker'),
            WPSLT_VERSION,
            true
        );

        wp_localize_script('wpslt-cookie-admin', 'wpsltCookieAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpslt_cookie_admin'),
            'settings' => $this->settings,
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this category?', 'wp-slatan-theme'),
                'saved' => __('Settings saved successfully!', 'wp-slatan-theme'),
                'error' => __('Error saving settings.', 'wp-slatan-theme'),
            ),
        ));
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();
        $defaults = $this->get_defaults();

        // General
        $sanitized['enabled'] = isset($input['enabled']) ? (bool) $input['enabled'] : false;
        $sanitized['cookie_name'] = isset($input['cookie_name']) ? sanitize_key($input['cookie_name']) : $defaults['cookie_name'];
        $sanitized['cookie_expiry'] = isset($input['cookie_expiry']) ? absint($input['cookie_expiry']) : $defaults['cookie_expiry'];
        $sanitized['show_revisit'] = isset($input['show_revisit']) ? (bool) $input['show_revisit'] : false;
        $sanitized['revisit_position'] = isset($input['revisit_position']) ? sanitize_text_field($input['revisit_position']) : $defaults['revisit_position'];

        // Banner
        $allowed_banner_types = array('bar-top', 'bar-bottom', 'popup', 'widget-left', 'widget-right');
        $sanitized['banner_type'] = isset($input['banner_type']) && in_array($input['banner_type'], $allowed_banner_types)
            ? $input['banner_type']
            : $defaults['banner_type'];

        $sanitized['banner_bg_color'] = isset($input['banner_bg_color']) ? sanitize_hex_color($input['banner_bg_color']) : $defaults['banner_bg_color'];
        $sanitized['banner_text_color'] = isset($input['banner_text_color']) ? sanitize_hex_color($input['banner_text_color']) : $defaults['banner_text_color'];
        $sanitized['accept_bg_color'] = isset($input['accept_bg_color']) ? sanitize_hex_color($input['accept_bg_color']) : $defaults['accept_bg_color'];
        $sanitized['accept_text_color'] = isset($input['accept_text_color']) ? sanitize_hex_color($input['accept_text_color']) : $defaults['accept_text_color'];
        $sanitized['reject_bg_color'] = isset($input['reject_bg_color']) ? sanitize_hex_color($input['reject_bg_color']) : $defaults['reject_bg_color'];
        $sanitized['reject_text_color'] = isset($input['reject_text_color']) ? sanitize_hex_color($input['reject_text_color']) : $defaults['reject_text_color'];
        $sanitized['settings_bg_color'] = isset($input['settings_bg_color']) ? $this->sanitize_color($input['settings_bg_color']) : $defaults['settings_bg_color'];
        $sanitized['settings_text_color'] = isset($input['settings_text_color']) ? sanitize_hex_color($input['settings_text_color']) : $defaults['settings_text_color'];

        $allowed_animations = array('none', 'slide', 'fade');
        $sanitized['animation'] = isset($input['animation']) && in_array($input['animation'], $allowed_animations)
            ? $input['animation']
            : $defaults['animation'];

        // Content
        $sanitized['title'] = isset($input['title']) ? sanitize_text_field($input['title']) : $defaults['title'];
        $sanitized['message'] = isset($input['message']) ? wp_kses_post($input['message']) : $defaults['message'];
        $sanitized['accept_text'] = isset($input['accept_text']) ? sanitize_text_field($input['accept_text']) : $defaults['accept_text'];
        $sanitized['reject_text'] = isset($input['reject_text']) ? sanitize_text_field($input['reject_text']) : $defaults['reject_text'];
        $sanitized['settings_text'] = isset($input['settings_text']) ? sanitize_text_field($input['settings_text']) : $defaults['settings_text'];
        $sanitized['save_text'] = isset($input['save_text']) ? sanitize_text_field($input['save_text']) : $defaults['save_text'];
        $sanitized['close_text'] = isset($input['close_text']) ? sanitize_text_field($input['close_text']) : $defaults['close_text'];
        $sanitized['read_more_text'] = isset($input['read_more_text']) ? sanitize_text_field($input['read_more_text']) : $defaults['read_more_text'];
        $sanitized['read_more_url'] = isset($input['read_more_url']) ? esc_url_raw($input['read_more_url']) : '';

        // Categories
        $sanitized['categories'] = array();
        if (isset($input['categories']) && is_array($input['categories'])) {
            foreach ($input['categories'] as $category) {
                $sanitized['categories'][] = array(
                    'id' => isset($category['id']) ? sanitize_key($category['id']) : '',
                    'name' => isset($category['name']) ? sanitize_text_field($category['name']) : '',
                    'description' => isset($category['description']) ? sanitize_textarea_field($category['description']) : '',
                    'is_necessary' => isset($category['is_necessary']) ? (bool) $category['is_necessary'] : false,
                    'default_state' => isset($category['default_state']) ? (bool) $category['default_state'] : false,
                );
            }
        }

        // Ensure at least the necessary category exists
        if (empty($sanitized['categories'])) {
            $sanitized['categories'] = $defaults['categories'];
        }

        // Scripts - Built-in Integrations
        $sanitized['google_analytics_id'] = isset($input['google_analytics_id'])
            ? sanitize_text_field($input['google_analytics_id'])
            : '';
        $sanitized['google_tag_manager_id'] = isset($input['google_tag_manager_id'])
            ? sanitize_text_field($input['google_tag_manager_id'])
            : '';
        $sanitized['facebook_pixel_id'] = isset($input['facebook_pixel_id'])
            ? sanitize_text_field($input['facebook_pixel_id'])
            : '';
        $sanitized['tiktok_pixel_id'] = isset($input['tiktok_pixel_id'])
            ? sanitize_text_field($input['tiktok_pixel_id'])
            : '';

        // Scripts - Custom Scripts (allow script tags)
        $sanitized['head_scripts_necessary'] = isset($input['head_scripts_necessary'])
            ? $this->sanitize_script_content($input['head_scripts_necessary'])
            : '';
        $sanitized['head_scripts_analytics'] = isset($input['head_scripts_analytics'])
            ? $this->sanitize_script_content($input['head_scripts_analytics'])
            : '';
        $sanitized['head_scripts_marketing'] = isset($input['head_scripts_marketing'])
            ? $this->sanitize_script_content($input['head_scripts_marketing'])
            : '';
        $sanitized['body_scripts_necessary'] = isset($input['body_scripts_necessary'])
            ? $this->sanitize_script_content($input['body_scripts_necessary'])
            : '';
        $sanitized['body_scripts_analytics'] = isset($input['body_scripts_analytics'])
            ? $this->sanitize_script_content($input['body_scripts_analytics'])
            : '';
        $sanitized['body_scripts_marketing'] = isset($input['body_scripts_marketing'])
            ? $this->sanitize_script_content($input['body_scripts_marketing'])
            : '';

        // Scripts - URL Pattern Blocking
        $sanitized['block_patterns'] = array();
        if (isset($input['block_patterns']) && is_array($input['block_patterns'])) {
            foreach ($input['block_patterns'] as $item) {
                $pattern = isset($item['pattern']) ? sanitize_text_field($item['pattern']) : '';
                if (!empty($pattern)) {
                    $category = isset($item['category']) && in_array($item['category'], array('analytics', 'marketing'))
                        ? $item['category']
                        : 'analytics';
                    $sanitized['block_patterns'][] = array(
                        'pattern' => $pattern,
                        'category' => $category,
                    );
                }
            }
        }

        // Auto-blocking
        $sanitized['auto_block_known_scripts'] = isset($input['auto_block_known_scripts'])
            ? (bool) $input['auto_block_known_scripts']
            : false;

        return $sanitized;
    }

    /**
     * Sanitize color (hex or transparent)
     */
    private function sanitize_color($color)
    {
        if ($color === 'transparent') {
            return 'transparent';
        }
        return sanitize_hex_color($color);
    }

    /**
     * Sanitize script content
     * Allow script tags but remove potentially dangerous content
     */
    private function sanitize_script_content($content)
    {
        // Allow script tags and their content
        // Remove any PHP tags for security
        $content = preg_replace('/<\?php.*?\?>/is', '', $content);
        $content = preg_replace('/<\?.*?\?>/is', '', $content);

        // Trim whitespace
        return trim($content);
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        ?>
        <div class="wrap wpslt-cookie-admin">
            <h1 class="wpslt-page-heading">
                <span class="dashicons dashicons-shield"></span>
                <?php esc_html_e('Cookie Consent', 'wp-slatan-theme'); ?>
            </h1>
            <p class="wpslt-page-description">
                <?php esc_html_e('Configure the cookie consent banner to comply with GDPR and other privacy regulations.', 'wp-slatan-theme'); ?>
            </p>
            <hr class="wp-header-end">

            <?php settings_errors(); ?>

            <div class="wpslt-cookie-layout">
                <!-- Settings Panel -->
                <div class="wpslt-cookie-settings">
                    <form method="post" action="options.php" id="wpslt-cookie-form">
                        <?php settings_fields(self::OPTION_NAME . '_group'); ?>

                        <!-- Enable Toggle -->
                        <div class="wpslt-card">
                            <h3><?php esc_html_e('⚡ Enable Cookie Consent', 'wp-slatan-theme'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e('Enable Cookie Banner', 'wp-slatan-theme'); ?></th>
                                    <td>
                                        <label class="wpslt-toggle">
                                            <input type="checkbox" name="<?php echo self::OPTION_NAME; ?>[enabled]" value="1"
                                                <?php checked($this->settings['enabled'], true); ?>>
                                            <span class="wpslt-toggle-slider"></span>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Tabs -->
                        <div class="wpslt-tabs">
                            <button type="button" class="wpslt-tab active" data-tab="general">
                                <?php esc_html_e('General', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="banner">
                                <?php esc_html_e('Banner', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="content">
                                <?php esc_html_e('Content', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="categories">
                                <?php esc_html_e('Categories', 'wp-slatan-theme'); ?>
                            </button>
                            <button type="button" class="wpslt-tab" data-tab="scripts">
                                <?php esc_html_e('Scripts', 'wp-slatan-theme'); ?>
                            </button>
                        </div>

                        <!-- General Tab -->
                        <div id="wpslt-tab-general" class="wpslt-tab-content active">
                            <?php $this->render_general_tab(); ?>
                        </div>

                        <!-- Banner Tab -->
                        <div id="wpslt-tab-banner" class="wpslt-tab-content">
                            <?php $this->render_banner_tab(); ?>
                        </div>

                        <!-- Content Tab -->
                        <div id="wpslt-tab-content" class="wpslt-tab-content">
                            <?php $this->render_content_tab(); ?>
                        </div>

                        <!-- Categories Tab -->
                        <div id="wpslt-tab-categories" class="wpslt-tab-content">
                            <?php $this->render_categories_tab(); ?>
                        </div>

                        <!-- Scripts Tab -->
                        <div id="wpslt-tab-scripts" class="wpslt-tab-content">
                            <?php $this->render_scripts_tab(); ?>
                        </div>

                        <div class="wpslt-submit-wrap">
                            <?php submit_button(__('Save Settings', 'wp-slatan-theme'), 'primary', 'submit', false); ?>
                        </div>
                    </form>
                </div>

                <!-- Preview Panel -->
                <div class="wpslt-cookie-preview">
                    <h3><?php esc_html_e('Live Preview', 'wp-slatan-theme'); ?></h3>
                    <div class="wpslt-preview-frame">
                        <div class="wpslt-preview-content" id="wpslt-cookie-preview">
                            <?php $this->render_preview(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render General tab
     */
    private function render_general_tab()
    {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpslt_cookie_expiry">
                        <?php esc_html_e('Cookie Expiry (Days)', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" id="wpslt_cookie_expiry" name="<?php echo self::OPTION_NAME; ?>[cookie_expiry]"
                        value="<?php echo esc_attr($this->settings['cookie_expiry']); ?>" min="1" max="730" class="small-text">
                    <p class="description">
                        <?php esc_html_e('How long to remember the user\'s consent preference.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_show_revisit">
                        <?php esc_html_e('Show Revisit Button', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" id="wpslt_show_revisit" name="<?php echo self::OPTION_NAME; ?>[show_revisit]"
                            value="1" <?php checked($this->settings['show_revisit'], true); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Show a button to allow users to change their consent.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_revisit_position">
                        <?php esc_html_e('Revisit Button Position', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <select id="wpslt_revisit_position" name="<?php echo self::OPTION_NAME; ?>[revisit_position]">
                        <option value="bottom-left" <?php selected($this->settings['revisit_position'], 'bottom-left'); ?>>
                            <?php esc_html_e('Bottom Left', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="bottom-right" <?php selected($this->settings['revisit_position'], 'bottom-right'); ?>>
                            <?php esc_html_e('Bottom Right', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="top-left" <?php selected($this->settings['revisit_position'], 'top-left'); ?>>
                            <?php esc_html_e('Top Left', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="top-right" <?php selected($this->settings['revisit_position'], 'top-right'); ?>>
                            <?php esc_html_e('Top Right', 'wp-slatan-theme'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Banner tab
     */
    private function render_banner_tab()
    {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpslt_banner_type">
                        <?php esc_html_e('Banner Type', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <select id="wpslt_banner_type" name="<?php echo self::OPTION_NAME; ?>[banner_type]">
                        <option value="bar-bottom" <?php selected($this->settings['banner_type'], 'bar-bottom'); ?>>
                            <?php esc_html_e('Bar - Bottom', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="bar-top" <?php selected($this->settings['banner_type'], 'bar-top'); ?>>
                            <?php esc_html_e('Bar - Top', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="popup" <?php selected($this->settings['banner_type'], 'popup'); ?>>
                            <?php esc_html_e('Popup - Center', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="widget-left" <?php selected($this->settings['banner_type'], 'widget-left'); ?>>
                            <?php esc_html_e('Widget - Bottom Left', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="widget-right" <?php selected($this->settings['banner_type'], 'widget-right'); ?>>
                            <?php esc_html_e('Widget - Bottom Right', 'wp-slatan-theme'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_animation">
                        <?php esc_html_e('Animation', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <select id="wpslt_animation" name="<?php echo self::OPTION_NAME; ?>[animation]">
                        <option value="none" <?php selected($this->settings['animation'], 'none'); ?>>
                            <?php esc_html_e('None', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="slide" <?php selected($this->settings['animation'], 'slide'); ?>>
                            <?php esc_html_e('Slide', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="fade" <?php selected($this->settings['animation'], 'fade'); ?>>
                            <?php esc_html_e('Fade', 'wp-slatan-theme'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>

        <h3>
            <?php esc_html_e('Banner Colors', 'wp-slatan-theme'); ?>
        </h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpslt_banner_bg_color">
                        <?php esc_html_e('Background Color', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_banner_bg_color" name="<?php echo self::OPTION_NAME; ?>[banner_bg_color]"
                        value="<?php echo esc_attr($this->settings['banner_bg_color']); ?>" class="wpslt-color-picker"
                        data-default-color="#1a1a2e">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_banner_text_color">
                        <?php esc_html_e('Text Color', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_banner_text_color" name="<?php echo self::OPTION_NAME; ?>[banner_text_color]"
                        value="<?php echo esc_attr($this->settings['banner_text_color']); ?>" class="wpslt-color-picker"
                        data-default-color="#ffffff">
                </td>
            </tr>
        </table>

        <h3>
            <?php esc_html_e('Button Colors', 'wp-slatan-theme'); ?>
        </h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpslt_accept_bg_color">
                        <?php esc_html_e('Accept Button Background', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_accept_bg_color" name="<?php echo self::OPTION_NAME; ?>[accept_bg_color]"
                        value="<?php echo esc_attr($this->settings['accept_bg_color']); ?>" class="wpslt-color-picker"
                        data-default-color="#16a085">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_accept_text_color">
                        <?php esc_html_e('Accept Button Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_accept_text_color" name="<?php echo self::OPTION_NAME; ?>[accept_text_color]"
                        value="<?php echo esc_attr($this->settings['accept_text_color']); ?>" class="wpslt-color-picker"
                        data-default-color="#ffffff">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_reject_bg_color">
                        <?php esc_html_e('Reject Button Background', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_reject_bg_color" name="<?php echo self::OPTION_NAME; ?>[reject_bg_color]"
                        value="<?php echo esc_attr($this->settings['reject_bg_color']); ?>" class="wpslt-color-picker"
                        data-default-color="#e74c3c">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_reject_text_color">
                        <?php esc_html_e('Reject Button Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_reject_text_color" name="<?php echo self::OPTION_NAME; ?>[reject_text_color]"
                        value="<?php echo esc_attr($this->settings['reject_text_color']); ?>" class="wpslt-color-picker"
                        data-default-color="#ffffff">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_settings_text_color">
                        <?php esc_html_e('Settings Button Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_settings_text_color"
                        name="<?php echo self::OPTION_NAME; ?>[settings_text_color]"
                        value="<?php echo esc_attr($this->settings['settings_text_color']); ?>" class="wpslt-color-picker"
                        data-default-color="#ffffff">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Content tab
     */
    private function render_content_tab()
    {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpslt_title">
                        <?php esc_html_e('Banner Title', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_title" name="<?php echo self::OPTION_NAME; ?>[title]"
                        value="<?php echo esc_attr($this->settings['title']); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_message">
                        <?php esc_html_e('Banner Message', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <textarea id="wpslt_message" name="<?php echo self::OPTION_NAME; ?>[message]" rows="4"
                        class="large-text"><?php echo esc_textarea($this->settings['message']); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_accept_text">
                        <?php esc_html_e('Accept Button Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_accept_text" name="<?php echo self::OPTION_NAME; ?>[accept_text]"
                        value="<?php echo esc_attr($this->settings['accept_text']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_reject_text">
                        <?php esc_html_e('Reject Button Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_reject_text" name="<?php echo self::OPTION_NAME; ?>[reject_text]"
                        value="<?php echo esc_attr($this->settings['reject_text']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_settings_text">
                        <?php esc_html_e('Settings Button Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_settings_text" name="<?php echo self::OPTION_NAME; ?>[settings_text]"
                        value="<?php echo esc_attr($this->settings['settings_text']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_save_text">
                        <?php esc_html_e('Save Preferences Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_save_text" name="<?php echo self::OPTION_NAME; ?>[save_text]"
                        value="<?php echo esc_attr($this->settings['save_text']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_read_more_text">
                        <?php esc_html_e('Read More Text', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_read_more_text" name="<?php echo self::OPTION_NAME; ?>[read_more_text]"
                        value="<?php echo esc_attr($this->settings['read_more_text']); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_read_more_url">
                        <?php esc_html_e('Read More URL', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="url" id="wpslt_read_more_url" name="<?php echo self::OPTION_NAME; ?>[read_more_url]"
                        value="<?php echo esc_url($this->settings['read_more_url']); ?>" class="large-text"
                        placeholder="<?php esc_attr_e('https://example.com/privacy-policy', 'wp-slatan-theme'); ?>">
                    <p class="description">
                        <?php esc_html_e('Link to your privacy policy or cookie policy page. Leave empty to hide the link.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Categories tab
     */
    private function render_categories_tab()
    {
        ?>
        <p class="description" style="margin-bottom: 20px;">
            <?php esc_html_e('Configure cookie categories for granular consent. The "Necessary" category cannot be disabled by users.', 'wp-slatan-theme'); ?>
        </p>

        <div id="wpslt-categories-list">
            <?php foreach ($this->settings['categories'] as $index => $category): ?>
                <div class="wpslt-category-item" data-index="<?php echo $index; ?>">
                    <div class="wpslt-category-header">
                        <span class="wpslt-category-title">
                            <?php echo esc_html($category['name']); ?>
                        </span>
                        <?php if (!$category['is_necessary']): ?>
                            <button type="button" class="wpslt-category-delete button-link button-link-delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="wpslt-category-toggle">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </div>
                    <div class="wpslt-category-body">
                        <input type="hidden" name="<?php echo self::OPTION_NAME; ?>[categories][<?php echo $index; ?>][id]"
                            value="<?php echo esc_attr($category['id']); ?>">
                        <input type="hidden"
                            name="<?php echo self::OPTION_NAME; ?>[categories][<?php echo $index; ?>][is_necessary]"
                            value="<?php echo $category['is_necessary'] ? '1' : '0'; ?>">

                        <p>
                            <label for="wpslt_cat_name_<?php echo $index; ?>">
                                <?php esc_html_e('Name', 'wp-slatan-theme'); ?>
                            </label>
                            <input type="text" id="wpslt_cat_name_<?php echo $index; ?>"
                                name="<?php echo self::OPTION_NAME; ?>[categories][<?php echo $index; ?>][name]"
                                value="<?php echo esc_attr($category['name']); ?>" class="widefat">
                        </p>
                        <p>
                            <label for="wpslt_cat_desc_<?php echo $index; ?>">
                                <?php esc_html_e('Description', 'wp-slatan-theme'); ?>
                            </label>
                            <textarea id="wpslt_cat_desc_<?php echo $index; ?>"
                                name="<?php echo self::OPTION_NAME; ?>[categories][<?php echo $index; ?>][description]" rows="2"
                                class="widefat"><?php echo esc_textarea($category['description']); ?></textarea>
                        </p>
                        <?php if (!$category['is_necessary']): ?>
                            <p>
                                <label>
                                    <input type="checkbox"
                                        name="<?php echo self::OPTION_NAME; ?>[categories][<?php echo $index; ?>][default_state]"
                                        value="1" <?php checked($category['default_state'], true); ?>>
                                    <?php esc_html_e('Enabled by default', 'wp-slatan-theme'); ?>
                                </label>
                            </p>
                        <?php else: ?>
                            <input type="hidden"
                                name="<?php echo self::OPTION_NAME; ?>[categories][<?php echo $index; ?>][default_state]" value="1">
                            <p class="description">
                                <em>
                                    <?php esc_html_e('This category is required and cannot be disabled by users.', 'wp-slatan-theme'); ?>
                                </em>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="margin-top: 20px;">
            <button type="button" id="wpslt-add-category" class="button">
                <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e('Add Category', 'wp-slatan-theme'); ?>
            </button>
        </p>
        <?php
    }

    /**
     * Render Scripts tab
     */
    private function render_scripts_tab()
    {
        ?>
        <p class="description" style="margin-bottom: 20px;">
            <?php esc_html_e('Manage tracking scripts and control which scripts are blocked until user consent. Scripts will only load after the user accepts the corresponding cookie category.', 'wp-slatan-theme'); ?>
        </p>

        <!-- Built-in Integrations -->
        <h3 style="margin-top: 0;">
            <?php esc_html_e('🔌 Built-in Integrations', 'wp-slatan-theme'); ?>
        </h3>
        <p class="description">
            <?php esc_html_e('Enter your tracking IDs below. These scripts will be automatically blocked until user consent (Analytics category).', 'wp-slatan-theme'); ?>
        </p>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpslt_google_analytics_id">
                        <?php esc_html_e('Google Analytics ID', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_google_analytics_id"
                        name="<?php echo self::OPTION_NAME; ?>[google_analytics_id]"
                        value="<?php echo esc_attr($this->settings['google_analytics_id']); ?>"
                        class="regular-text"
                        placeholder="G-XXXXXXXXXX">
                    <p class="description">
                        <?php esc_html_e('Google Analytics 4 Measurement ID (e.g., G-XXXXXXXXXX)', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_google_tag_manager_id">
                        <?php esc_html_e('Google Tag Manager ID', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_google_tag_manager_id"
                        name="<?php echo self::OPTION_NAME; ?>[google_tag_manager_id]"
                        value="<?php echo esc_attr($this->settings['google_tag_manager_id']); ?>"
                        class="regular-text"
                        placeholder="GTM-XXXXXXX">
                    <p class="description">
                        <?php esc_html_e('Google Tag Manager Container ID (e.g., GTM-XXXXXXX)', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_facebook_pixel_id">
                        <?php esc_html_e('Facebook Pixel ID', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_facebook_pixel_id"
                        name="<?php echo self::OPTION_NAME; ?>[facebook_pixel_id]"
                        value="<?php echo esc_attr($this->settings['facebook_pixel_id']); ?>"
                        class="regular-text"
                        placeholder="123456789012345">
                    <p class="description">
                        <?php esc_html_e('Meta (Facebook) Pixel ID - Marketing category', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpslt_tiktok_pixel_id">
                        <?php esc_html_e('TikTok Pixel ID', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" id="wpslt_tiktok_pixel_id"
                        name="<?php echo self::OPTION_NAME; ?>[tiktok_pixel_id]"
                        value="<?php echo esc_attr($this->settings['tiktok_pixel_id']); ?>"
                        class="regular-text"
                        placeholder="XXXXXXXXXXXXXXXXXX">
                    <p class="description">
                        <?php esc_html_e('TikTok Pixel ID - Marketing category', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <!-- Auto-blocking -->
        <h3>
            <?php esc_html_e('🛡️ Auto-blocking', 'wp-slatan-theme'); ?>
        </h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpslt_auto_block">
                        <?php esc_html_e('Auto-block Known Scripts', 'wp-slatan-theme'); ?>
                    </label>
                </th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" id="wpslt_auto_block"
                            name="<?php echo self::OPTION_NAME; ?>[auto_block_known_scripts]"
                            value="1" <?php checked($this->settings['auto_block_known_scripts'], true); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Automatically block known third-party scripts (Google Analytics, Facebook, etc.) until consent. This works by intercepting script loading based on URL patterns.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <!-- URL Pattern Blocking -->
        <h3>
            <?php esc_html_e('🔗 URL Pattern Blocking', 'wp-slatan-theme'); ?>
        </h3>
        <p class="description">
            <?php esc_html_e('Add URL patterns to block third-party scripts until consent. Enter partial URLs to match (e.g., "google-analytics.com" will block any script containing that URL).', 'wp-slatan-theme'); ?>
        </p>
        <div id="wpslt-block-patterns">
            <?php
            $patterns = $this->settings['block_patterns'];
            if (empty($patterns)) {
                $patterns = array(array('pattern' => '', 'category' => 'analytics'));
            }
            foreach ($patterns as $index => $item):
            ?>
                <div class="wpslt-pattern-row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                    <input type="text"
                        name="<?php echo self::OPTION_NAME; ?>[block_patterns][<?php echo $index; ?>][pattern]"
                        value="<?php echo esc_attr($item['pattern']); ?>"
                        class="regular-text"
                        placeholder="e.g., hotjar.com">
                    <select name="<?php echo self::OPTION_NAME; ?>[block_patterns][<?php echo $index; ?>][category]">
                        <option value="analytics" <?php selected($item['category'], 'analytics'); ?>>
                            <?php esc_html_e('Analytics', 'wp-slatan-theme'); ?>
                        </option>
                        <option value="marketing" <?php selected($item['category'], 'marketing'); ?>>
                            <?php esc_html_e('Marketing', 'wp-slatan-theme'); ?>
                        </option>
                    </select>
                    <button type="button" class="button wpslt-remove-pattern">
                        <span class="dashicons dashicons-no-alt" style="vertical-align: middle;"></span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <p>
            <button type="button" id="wpslt-add-pattern" class="button">
                <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e('Add Pattern', 'wp-slatan-theme'); ?>
            </button>
        </p>

        <!-- Custom Scripts -->
        <h3>
            <?php esc_html_e('📝 Custom Scripts', 'wp-slatan-theme'); ?>
        </h3>
        <p class="description">
            <?php esc_html_e('Add custom scripts that will be loaded based on user consent. Scripts in Necessary will load immediately. Analytics and Marketing scripts will only load after consent.', 'wp-slatan-theme'); ?>
        </p>

        <div class="wpslt-scripts-accordion">
            <!-- Head Scripts - Analytics -->
            <div class="wpslt-accordion-item" style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 8px;">
                    <?php esc_html_e('Head Scripts - Analytics', 'wp-slatan-theme'); ?>
                </h4>
                <textarea id="wpslt_head_scripts_analytics"
                    name="<?php echo self::OPTION_NAME; ?>[head_scripts_analytics]"
                    rows="5" class="large-text code"
                    placeholder="<!-- Your analytics scripts here -->"><?php echo esc_textarea($this->settings['head_scripts_analytics']); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Scripts loaded in <head> after Analytics consent.', 'wp-slatan-theme'); ?>
                </p>
            </div>

            <!-- Head Scripts - Marketing -->
            <div class="wpslt-accordion-item" style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 8px;">
                    <?php esc_html_e('Head Scripts - Marketing', 'wp-slatan-theme'); ?>
                </h4>
                <textarea id="wpslt_head_scripts_marketing"
                    name="<?php echo self::OPTION_NAME; ?>[head_scripts_marketing]"
                    rows="5" class="large-text code"
                    placeholder="<!-- Your marketing scripts here -->"><?php echo esc_textarea($this->settings['head_scripts_marketing']); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Scripts loaded in <head> after Marketing consent.', 'wp-slatan-theme'); ?>
                </p>
            </div>

            <!-- Body Scripts - Analytics -->
            <div class="wpslt-accordion-item" style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 8px;">
                    <?php esc_html_e('Body Scripts - Analytics', 'wp-slatan-theme'); ?>
                </h4>
                <textarea id="wpslt_body_scripts_analytics"
                    name="<?php echo self::OPTION_NAME; ?>[body_scripts_analytics]"
                    rows="5" class="large-text code"
                    placeholder="<!-- Your analytics scripts here -->"><?php echo esc_textarea($this->settings['body_scripts_analytics']); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Scripts loaded before </body> after Analytics consent.', 'wp-slatan-theme'); ?>
                </p>
            </div>

            <!-- Body Scripts - Marketing -->
            <div class="wpslt-accordion-item" style="margin-bottom: 15px;">
                <h4 style="margin: 0 0 8px;">
                    <?php esc_html_e('Body Scripts - Marketing', 'wp-slatan-theme'); ?>
                </h4>
                <textarea id="wpslt_body_scripts_marketing"
                    name="<?php echo self::OPTION_NAME; ?>[body_scripts_marketing]"
                    rows="5" class="large-text code"
                    placeholder="<!-- Your marketing scripts here -->"><?php echo esc_textarea($this->settings['body_scripts_marketing']); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Scripts loaded before </body> after Marketing consent.', 'wp-slatan-theme'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render preview
     */
    private function render_preview()
    {
        ?>
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

                    <!-- Cookie Consent Banner Preview -->
                    <div class="wpslt-cookie-banner-preview" id="wpslt-banner-preview"
                        data-type="<?php echo esc_attr($this->settings['banner_type']); ?>"
                        style="background-color: <?php echo esc_attr($this->settings['banner_bg_color']); ?>; color: <?php echo esc_attr($this->settings['banner_text_color']); ?>;">
                        <div class="wpslt-cookie-banner-inner">
                            <div class="wpslt-cookie-banner-text">
                                <h4 id="preview-title">
                                    <?php echo esc_html($this->settings['title']); ?>
                                </h4>
                                <p id="preview-message">
                                    <?php echo esc_html($this->settings['message']); ?>
                                </p>
                            </div>
                            <div class="wpslt-cookie-banner-buttons">
                                <button type="button" class="wpslt-cookie-btn wpslt-cookie-accept"
                                    style="background-color: <?php echo esc_attr($this->settings['accept_bg_color']); ?>; color: <?php echo esc_attr($this->settings['accept_text_color']); ?>;">
                                    <?php echo esc_html($this->settings['accept_text']); ?>
                                </button>
                                <button type="button" class="wpslt-cookie-btn wpslt-cookie-reject"
                                    style="background-color: <?php echo esc_attr($this->settings['reject_bg_color']); ?>; color: <?php echo esc_attr($this->settings['reject_text_color']); ?>;">
                                    <?php echo esc_html($this->settings['reject_text']); ?>
                                </button>
                                <button type="button" class="wpslt-cookie-btn wpslt-cookie-settings"
                                    style="color: <?php echo esc_attr($this->settings['settings_text_color']); ?>;">
                                    <?php echo esc_html($this->settings['settings_text']); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize
WPSLT_Cookie_Admin::get_instance();

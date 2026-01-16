<?php
/**
 * Cookie Consent Frontend Output
 *
 * @package WP_Slatan_Theme
 * @since 1.0.22
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cookie Consent Output Class
 */
class WPSLT_Cookie_Output {

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
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $admin = WPSLT_Cookie_Admin::get_instance();
        $this->settings = $admin->get_settings();

        if (!$this->settings['enabled']) {
            return;
        }

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_footer', array($this, 'render_banner'));
        add_action('wp_footer', array($this, 'render_preferences_modal'));
        add_action('wp_footer', array($this, 'render_revisit_button'));

        // Shortcodes
        add_shortcode('wpslt_cookie_settings', array($this, 'shortcode_settings_button'));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // CSS
        wp_enqueue_style(
            'wpslt-cookie-consent',
            WPSLT_URI . '/assets/css/cookie-consent.css',
            array(),
            WPSLT_VERSION
        );

        // Inline styles
        wp_add_inline_style('wpslt-cookie-consent', $this->get_inline_styles());

        // JavaScript
        wp_enqueue_script(
            'wpslt-cookie-consent',
            WPSLT_URI . '/assets/js/cookie-consent.js',
            array(),
            WPSLT_VERSION,
            true
        );

        // Localize script
        wp_localize_script('wpslt-cookie-consent', 'wpsltCookieConsent', $this->get_script_config());
    }

    /**
     * Get inline styles
     */
    private function get_inline_styles() {
        $s = $this->settings;
        
        $css = "
            .wpslt-cookie-banner {
                --wpslt-cookie-bg: {$s['banner_bg_color']};
                --wpslt-cookie-text: {$s['banner_text_color']};
                --wpslt-cookie-accept-bg: {$s['accept_bg_color']};
                --wpslt-cookie-accept-text: {$s['accept_text_color']};
                --wpslt-cookie-reject-bg: {$s['reject_bg_color']};
                --wpslt-cookie-reject-text: {$s['reject_text_color']};
                --wpslt-cookie-settings-text: {$s['settings_text_color']};
            }
        ";

        return $css;
    }

    /**
     * Get script configuration
     */
    private function get_script_config() {
        return array(
            'cookieName' => 'wpslt_cookie_consent',
            'cookieExpiry' => absint($this->settings['cookie_expiry']),
            'bannerType' => $this->settings['banner_type'],
            'animation' => $this->settings['animation'],
            'showRevisit' => $this->settings['show_revisit'],
            'revisitPosition' => $this->settings['revisit_position'],
            'categories' => array_map(function($cat) {
                return array(
                    'id' => $cat['id'],
                    'isNecessary' => $cat['is_necessary'],
                    'defaultState' => $cat['default_state'],
                );
            }, $this->settings['categories']),
        );
    }

    /**
     * Render cookie consent banner
     */
    public function render_banner() {
        $s = $this->settings;
        $type_class = 'wpslt-cookie-' . str_replace('-', ' wpslt-cookie-', $s['banner_type']);
        $animation_class = 'wpslt-cookie-anim-' . $s['animation'];
        ?>
        <div id="wpslt-cookie-banner" 
             class="wpslt-cookie-banner <?php echo esc_attr($type_class); ?> <?php echo esc_attr($animation_class); ?> wpslt-cookie-hidden"
             role="dialog"
             aria-modal="true"
             aria-label="<?php esc_attr_e('Cookie Consent', 'wp-slatan-theme'); ?>">
            <div class="wpslt-cookie-banner-inner">
                <div class="wpslt-cookie-content">
                    <?php if (!empty($s['title'])) : ?>
                        <h4 class="wpslt-cookie-title"><?php echo esc_html($s['title']); ?></h4>
                    <?php endif; ?>
                    <p class="wpslt-cookie-message">
                        <?php echo wp_kses_post($s['message']); ?>
                        <?php if (!empty($s['read_more_url'])) : ?>
                            <a href="<?php echo esc_url($s['read_more_url']); ?>" 
                               class="wpslt-cookie-read-more"
                               target="_blank"
                               rel="noopener noreferrer">
                                <?php echo esc_html($s['read_more_text']); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="wpslt-cookie-buttons">
                    <button type="button" 
                            class="wpslt-cookie-btn wpslt-cookie-accept-btn"
                            data-action="accept-all">
                        <?php echo esc_html($s['accept_text']); ?>
                    </button>
                    <button type="button" 
                            class="wpslt-cookie-btn wpslt-cookie-reject-btn"
                            data-action="reject-all">
                        <?php echo esc_html($s['reject_text']); ?>
                    </button>
                    <button type="button" 
                            class="wpslt-cookie-btn wpslt-cookie-settings-btn"
                            data-action="open-settings">
                        <?php echo esc_html($s['settings_text']); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (in_array($s['banner_type'], array('popup', 'widget-left', 'widget-right'))) : ?>
            <div id="wpslt-cookie-overlay" class="wpslt-cookie-overlay wpslt-cookie-hidden"></div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render preferences modal
     */
    public function render_preferences_modal() {
        $s = $this->settings;
        ?>
        <div id="wpslt-cookie-modal" 
             class="wpslt-cookie-modal wpslt-cookie-hidden"
             role="dialog"
             aria-modal="true"
             aria-label="<?php esc_attr_e('Cookie Preferences', 'wp-slatan-theme'); ?>">
            <div class="wpslt-cookie-modal-overlay" data-action="close-modal"></div>
            <div class="wpslt-cookie-modal-dialog">
                <div class="wpslt-cookie-modal-header">
                    <h3 class="wpslt-cookie-modal-title"><?php echo esc_html($s['title']); ?></h3>
                    <button type="button" 
                            class="wpslt-cookie-modal-close"
                            data-action="close-modal"
                            aria-label="<?php esc_attr_e('Close', 'wp-slatan-theme'); ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="wpslt-cookie-modal-body">
                    <p class="wpslt-cookie-modal-description">
                        <?php echo wp_kses_post($s['message']); ?>
                    </p>
                    
                    <div class="wpslt-cookie-categories">
                        <?php foreach ($s['categories'] as $category) : ?>
                            <div class="wpslt-cookie-category" data-category="<?php echo esc_attr($category['id']); ?>">
                                <div class="wpslt-cookie-category-header">
                                    <div class="wpslt-cookie-category-info">
                                        <h4 class="wpslt-cookie-category-name"><?php echo esc_html($category['name']); ?></h4>
                                    </div>
                                    <div class="wpslt-cookie-category-toggle">
                                        <?php if ($category['is_necessary']) : ?>
                                            <span class="wpslt-cookie-always-active">
                                                <?php esc_html_e('Always Active', 'wp-slatan-theme'); ?>
                                            </span>
                                            <input type="checkbox" 
                                                   id="wpslt-cat-<?php echo esc_attr($category['id']); ?>"
                                                   class="wpslt-cookie-checkbox"
                                                   data-category="<?php echo esc_attr($category['id']); ?>"
                                                   checked
                                                   disabled>
                                        <?php else : ?>
                                            <label class="wpslt-cookie-switch">
                                                <input type="checkbox" 
                                                       id="wpslt-cat-<?php echo esc_attr($category['id']); ?>"
                                                       class="wpslt-cookie-checkbox"
                                                       data-category="<?php echo esc_attr($category['id']); ?>"
                                                       <?php checked($category['default_state'], true); ?>>
                                                <span class="wpslt-cookie-slider"></span>
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="wpslt-cookie-category-description">
                                    <p><?php echo esc_html($category['description']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="wpslt-cookie-modal-footer">
                    <button type="button" 
                            class="wpslt-cookie-btn wpslt-cookie-save-btn"
                            data-action="save-preferences">
                        <?php echo esc_html($s['save_text']); ?>
                    </button>
                    <button type="button" 
                            class="wpslt-cookie-btn wpslt-cookie-accept-all-btn"
                            data-action="accept-all">
                        <?php echo esc_html($s['accept_text']); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render revisit button
     */
    public function render_revisit_button() {
        if (!$this->settings['show_revisit']) {
            return;
        }
        
        $position_class = 'wpslt-cookie-revisit-' . $this->settings['revisit_position'];
        ?>
        <button type="button" 
                id="wpslt-cookie-revisit" 
                class="wpslt-cookie-revisit <?php echo esc_attr($position_class); ?> wpslt-cookie-hidden"
                data-action="open-settings"
                aria-label="<?php esc_attr_e('Manage Cookie Preferences', 'wp-slatan-theme'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
            </svg>
            <span class="wpslt-cookie-revisit-text"><?php echo esc_html($this->settings['settings_text']); ?></span>
        </button>
        <?php
    }

    /**
     * Shortcode: Settings button
     */
    public function shortcode_settings_button($atts) {
        $atts = shortcode_atts(array(
            'text' => $this->settings['settings_text'],
            'class' => '',
        ), $atts);

        return sprintf(
            '<button type="button" class="wpslt-cookie-settings-trigger %s" data-action="open-settings">%s</button>',
            esc_attr($atts['class']),
            esc_html($atts['text'])
        );
    }
}

// Initialize
WPSLT_Cookie_Output::get_instance();

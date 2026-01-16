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
        add_action('wp_head', array($this, 'render_head_scripts'), 1);
        add_action('wp_footer', array($this, 'render_banner'));
        add_action('wp_footer', array($this, 'render_preferences_modal'));
        add_action('wp_footer', array($this, 'render_revisit_button'));
        add_action('wp_footer', array($this, 'render_body_scripts'), 99);

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
        // Known scripts patterns for auto-blocking
        $known_scripts = array(
            // Google
            array('pattern' => 'google-analytics.com', 'category' => 'analytics'),
            array('pattern' => 'googletagmanager.com', 'category' => 'analytics'),
            array('pattern' => 'gtag/js', 'category' => 'analytics'),
            // Facebook/Meta
            array('pattern' => 'connect.facebook.net', 'category' => 'marketing'),
            array('pattern' => 'facebook.com/tr', 'category' => 'marketing'),
            array('pattern' => 'fbevents.js', 'category' => 'marketing'),
            // TikTok
            array('pattern' => 'analytics.tiktok.com', 'category' => 'marketing'),
            array('pattern' => 'tiktok.com/i18n', 'category' => 'marketing'),
            // Hotjar
            array('pattern' => 'hotjar.com', 'category' => 'analytics'),
            array('pattern' => 'static.hotjar.com', 'category' => 'analytics'),
            // Microsoft/Bing
            array('pattern' => 'clarity.ms', 'category' => 'analytics'),
            array('pattern' => 'bat.bing.com', 'category' => 'marketing'),
            // LinkedIn
            array('pattern' => 'snap.licdn.com', 'category' => 'marketing'),
            array('pattern' => 'linkedin.com/px', 'category' => 'marketing'),
            // Twitter/X
            array('pattern' => 'static.ads-twitter.com', 'category' => 'marketing'),
            array('pattern' => 'analytics.twitter.com', 'category' => 'marketing'),
            // Pinterest
            array('pattern' => 'pintrk', 'category' => 'marketing'),
            array('pattern' => 'ct.pinterest.com', 'category' => 'marketing'),
            // Snapchat
            array('pattern' => 'sc-static.net/scevent', 'category' => 'marketing'),
        );

        // Merge with custom patterns
        $block_patterns = $this->settings['auto_block_known_scripts']
            ? array_merge($known_scripts, $this->settings['block_patterns'])
            : $this->settings['block_patterns'];

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
            'blockPatterns' => $block_patterns,
            'autoBlock' => $this->settings['auto_block_known_scripts'],
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
     * Render head scripts (built-in integrations + custom head scripts)
     */
    public function render_head_scripts() {
        $s = $this->settings;

        // Google Analytics
        if (!empty($s['google_analytics_id'])) {
            $ga_id = esc_attr($s['google_analytics_id']);
            ?>
            <script type="text/plain" data-wpslt-category="analytics" data-wpslt-src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_id; ?>"></script>
            <script type="text/plain" data-wpslt-category="analytics">
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '<?php echo $ga_id; ?>');
            </script>
            <?php
        }

        // Google Tag Manager
        if (!empty($s['google_tag_manager_id'])) {
            $gtm_id = esc_attr($s['google_tag_manager_id']);
            ?>
            <script type="text/plain" data-wpslt-category="analytics">
                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','<?php echo $gtm_id; ?>');
            </script>
            <?php
        }

        // Facebook Pixel
        if (!empty($s['facebook_pixel_id'])) {
            $fb_id = esc_attr($s['facebook_pixel_id']);
            ?>
            <script type="text/plain" data-wpslt-category="marketing">
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '<?php echo $fb_id; ?>');
                fbq('track', 'PageView');
            </script>
            <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo $fb_id; ?>&ev=PageView&noscript=1"/></noscript>
            <?php
        }

        // TikTok Pixel
        if (!empty($s['tiktok_pixel_id'])) {
            $tt_id = esc_attr($s['tiktok_pixel_id']);
            ?>
            <script type="text/plain" data-wpslt-category="marketing">
                !function (w, d, t) {
                w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
                ttq.load('<?php echo $tt_id; ?>');
                ttq.page();
                }(window, document, 'ttq');
            </script>
            <?php
        }

        // Custom Head Scripts - Analytics
        if (!empty($s['head_scripts_analytics'])) {
            echo $this->wrap_scripts_with_category($s['head_scripts_analytics'], 'analytics');
        }

        // Custom Head Scripts - Marketing
        if (!empty($s['head_scripts_marketing'])) {
            echo $this->wrap_scripts_with_category($s['head_scripts_marketing'], 'marketing');
        }
    }

    /**
     * Render body scripts (custom body scripts)
     */
    public function render_body_scripts() {
        $s = $this->settings;

        // GTM noscript (if GTM is configured)
        if (!empty($s['google_tag_manager_id'])) {
            $gtm_id = esc_attr($s['google_tag_manager_id']);
            ?>
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $gtm_id; ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <?php
        }

        // Custom Body Scripts - Analytics
        if (!empty($s['body_scripts_analytics'])) {
            echo $this->wrap_scripts_with_category($s['body_scripts_analytics'], 'analytics');
        }

        // Custom Body Scripts - Marketing
        if (!empty($s['body_scripts_marketing'])) {
            echo $this->wrap_scripts_with_category($s['body_scripts_marketing'], 'marketing');
        }
    }

    /**
     * Wrap script tags with consent category
     */
    private function wrap_scripts_with_category($content, $category) {
        // Find all <script> tags and modify them
        $content = preg_replace_callback(
            '/<script([^>]*)>(.*?)<\/script>/is',
            function($matches) use ($category) {
                $attrs = $matches[1];
                $inner = $matches[2];

                // Check if it already has data-wpslt-category
                if (strpos($attrs, 'data-wpslt-category') !== false) {
                    return $matches[0];
                }

                // Check if it has src attribute
                if (preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $attrs, $src_match)) {
                    // External script - change src to data-wpslt-src
                    $new_attrs = preg_replace('/\bsrc\s*=/', 'data-wpslt-src=', $attrs);
                    return '<script type="text/plain" data-wpslt-category="' . esc_attr($category) . '"' . $new_attrs . '>' . $inner . '</script>';
                } else {
                    // Inline script
                    return '<script type="text/plain" data-wpslt-category="' . esc_attr($category) . '"' . $attrs . '>' . $inner . '</script>';
                }
            },
            $content
        );

        return $content;
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

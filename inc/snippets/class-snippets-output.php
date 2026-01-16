<?php
/**
 * Snippets Output Handler
 * 
 * Handles outputting code snippets on frontend and admin.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Snippets_Output
 */
class WPSLT_Snippets_Output
{

    /**
     * Database instance
     *
     * @var WPSLT_Snippets_DB
     */
    private $db;

    /**
     * Singleton instance
     *
     * @var WPSLT_Snippets_Output
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return WPSLT_Snippets_Output
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
        $this->db = WPSLT_Snippets_DB::get_instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Frontend CSS and Head JS
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'), 999);

        // Admin CSS
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_css'), 999);

        // Footer JS
        add_action('wp_footer', array($this, 'output_footer_js'), 999);
    }

    /**
     * Enqueue frontend CSS and Head JS
     */
    public function enqueue_frontend_assets()
    {
        // Don't run if table doesn't exist
        if (!$this->db->table_exists()) {
            return;
        }

        // Frontend CSS
        $css_snippets = $this->db->get_active_snippets('site-css');
        if (!empty($css_snippets)) {
            $css = $this->combine_snippets($css_snippets);
            if (!empty($css)) {
                // Register a dummy style to attach inline CSS
                wp_register_style('wpslt-snippets-css', false);
                wp_enqueue_style('wpslt-snippets-css');
                wp_add_inline_style('wpslt-snippets-css', $css);
            }
        }

        // Head JS
        $head_js_snippets = $this->db->get_active_snippets('site-head-js');
        if (!empty($head_js_snippets)) {
            $js = $this->combine_snippets($head_js_snippets);
            if (!empty($js)) {
                // Register a dummy script to attach inline JS
                wp_register_script('wpslt-snippets-head-js', false, array(), false, false);
                wp_enqueue_script('wpslt-snippets-head-js');
                wp_add_inline_script('wpslt-snippets-head-js', $js);
            }
        }
    }

    /**
     * Enqueue admin CSS
     */
    public function enqueue_admin_css()
    {
        // Don't run if table doesn't exist
        if (!$this->db->table_exists()) {
            return;
        }

        $css_snippets = $this->db->get_active_snippets('admin-css');
        if (!empty($css_snippets)) {
            $css = $this->combine_snippets($css_snippets);
            if (!empty($css)) {
                // Register a dummy style to attach inline CSS
                wp_register_style('wpslt-snippets-admin-css', false);
                wp_enqueue_style('wpslt-snippets-admin-css');
                wp_add_inline_style('wpslt-snippets-admin-css', $css);
            }
        }
    }

    /**
     * Output footer JS
     */
    public function output_footer_js()
    {
        // Don't run if table doesn't exist
        if (!$this->db->table_exists()) {
            return;
        }

        $js_snippets = $this->db->get_active_snippets('site-footer-js');
        if (empty($js_snippets)) {
            return;
        }

        $js = $this->combine_snippets($js_snippets);
        if (!empty($js)) {
            echo "\n<!-- WP Slatan Theme: Footer Scripts -->\n";
            echo "<script id=\"wpslt-snippets-footer-js\">\n";
            // JS output - we don't escape to preserve functionality
            // but it's safe because only admins can create snippets
            echo $js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo "\n</script>\n";
        }
    }

    /**
     * Combine multiple snippets into one string
     *
     * @param array $snippets Array of snippet data.
     * @return string Combined code
     */
    private function combine_snippets($snippets)
    {
        $combined = '';

        foreach ($snippets as $snippet) {
            $code = trim($snippet['code']);
            if (!empty($code)) {
                $combined .= "\n/* Snippet: " . esc_html($snippet['name']) . " (ID: " . intval($snippet['id']) . ") */\n";
                $combined .= $code . "\n";
            }
        }

        return trim($combined);
    }

    /**
     * Clear any cached snippets (for future caching implementation)
     */
    public function clear_cache()
    {
        // Reserved for future caching implementation
        // Could use transients or object cache
    }
}

<?php
/**
 * Theme Updater Class
 *
 * Handles automatic theme updates via Slatan Update Server.
 * Supports fallback between multiple update servers.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Theme_Updater
 */
class WPSLT_Theme_Updater
{
    /**
     * Singleton instance
     *
     * @var WPSLT_Theme_Updater
     */
    private static $instance = null;

    /**
     * Theme slug
     *
     * @var string
     */
    private $theme_slug = 'wp-slatan-theme';

    /**
     * Update servers (with fallback support)
     *
     * @var array
     */
    private $update_servers = array(
        'https://slatan-update-server.onrender.com/api',
        'https://middleware.slatanhost.com/api',
    );

    /**
     * Cache transient name
     *
     * @var string
     */
    private $cache_key = 'wpslt_update_cache';

    /**
     * Cache duration in seconds
     *
     * @var int
     */
    private $cache_expiration = 86400; // 24 hours

    /**
     * Get singleton instance
     *
     * @return WPSLT_Theme_Updater
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        // Check for theme updates
        add_filter('pre_set_site_transient_update_themes', array($this, 'check_for_update'));

        // Add info to theme details popup
        add_filter('themes_api', array($this, 'theme_info'), 20, 3);

        // Clear cache on theme switch
        add_action('switch_theme', array($this, 'clear_cache'));

        // Clear cache after update
        add_action('upgrader_process_complete', array($this, 'after_update'), 10, 2);
    }

    /**
     * Check for theme updates
     *
     * @param object $transient Update transient.
     * @return object Modified transient.
     */
    public function check_for_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get current theme version
        $theme = wp_get_theme($this->theme_slug);
        if (!$theme->exists()) {
            return $transient;
        }

        $current_version = $theme->get('Version');

        // Check cache first
        $cached = get_transient($this->cache_key);
        if (false !== $cached) {
            if (isset($cached['new_version']) && version_compare($cached['new_version'], $current_version, '>')) {
                $transient->response[$this->theme_slug] = $cached;
            }
            return $transient;
        }

        // Get license key
        $settings = get_option('wpslt_settings', array());
        $license_key = isset($settings['license_key']) ? $settings['license_key'] : '';

        // Request update info from server
        $update_info = $this->request_update_info($current_version, $license_key);

        if ($update_info && isset($update_info['new_version'])) {
            // Prepare response for WordPress
            $response = array(
                'theme' => $this->theme_slug,
                'new_version' => $update_info['new_version'],
                'url' => isset($update_info['url']) ? $update_info['url'] : '',
                'package' => isset($update_info['package']) ? $update_info['package'] : '',
            );

            // Cache the response
            set_transient($this->cache_key, $response, $this->cache_expiration);

            // Check if update is available
            if (version_compare($update_info['new_version'], $current_version, '>')) {
                $transient->response[$this->theme_slug] = $response;
            }
        }

        return $transient;
    }

    /**
     * Request update info from server with fallback
     *
     * @param string $current_version Current theme version.
     * @param string $license_key     License key.
     * @return array|false Update info or false on failure.
     */
    private function request_update_info($current_version, $license_key)
    {
        $body = array(
            'license_key' => $license_key,
            'site_url' => home_url(),
            'slug' => $this->theme_slug,
            'version' => $current_version,
        );

        // Try each server until one succeeds
        foreach ($this->update_servers as $server_url) {
            $response = wp_remote_post(
                $server_url . '/check-update',
                array(
                    'timeout' => 15,
                    'sslverify' => true,
                    'body' => wp_json_encode($body),
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                )
            );

            if (!is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (is_array($data) && isset($data['new_version'])) {
                    return $data;
                }
            }
        }

        return false;
    }

    /**
     * Get theme info for the popup
     *
     * @param false|object|array $result The result object or array.
     * @param string             $action The type of information being requested.
     * @param object             $args   Theme API arguments.
     * @return false|object|array Modified result.
     */
    public function theme_info($result, $action, $args)
    {
        if ('theme_information' !== $action) {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== $this->theme_slug) {
            return $result;
        }

        // Get license key
        $settings = get_option('wpslt_settings', array());
        $license_key = isset($settings['license_key']) ? $settings['license_key'] : '';

        // Get current version
        $theme = wp_get_theme($this->theme_slug);
        $current_version = $theme->exists() ? $theme->get('Version') : '1.0.0';

        // Request info from server
        $update_info = $this->request_update_info($current_version, $license_key);

        if ($update_info) {
            $result = (object) array(
                'name' => isset($update_info['name']) ? $update_info['name'] : 'WP Slatan Theme',
                'slug' => $this->theme_slug,
                'version' => isset($update_info['new_version']) ? $update_info['new_version'] : $current_version,
                'author' => '<a href="https://slatan.design/">Slatan Design</a>',
                'homepage' => isset($update_info['url']) ? $update_info['url'] : 'https://slatan.design/',
                'requires' => isset($update_info['requires']) ? $update_info['requires'] : '6.0',
                'tested' => isset($update_info['tested']) ? $update_info['tested'] : '6.7',
                'requires_php' => isset($update_info['requires_php']) ? $update_info['requires_php'] : '7.4',
                'last_updated' => isset($update_info['last_updated']) ? $update_info['last_updated'] : '',
                'sections' => isset($update_info['sections']) ? $update_info['sections'] : array(
                    'description' => $theme->get('Description'),
                    'changelog' => isset($update_info['changelog']) ? $update_info['changelog'] : '',
                ),
                'download_link' => isset($update_info['package']) ? $update_info['package'] : '',
            );
        }

        return $result;
    }

    /**
     * Validate license key
     *
     * @param string $license_key License key to validate.
     * @return array Validation result.
     */
    public function validate_license($license_key)
    {
        $body = array(
            'license_key' => $license_key,
            'site_url' => home_url(),
            'product' => $this->theme_slug,
        );

        // Try each server until one succeeds
        foreach ($this->update_servers as $server_url) {
            $response = wp_remote_post(
                $server_url . '/license/validate',
                array(
                    'timeout' => 15,
                    'sslverify' => true,
                    'body' => wp_json_encode($body),
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                )
            );

            if (!is_wp_error($response)) {
                $code = wp_remote_retrieve_response_code($response);
                $data = json_decode(wp_remote_retrieve_body($response), true);

                if (200 === $code && is_array($data)) {
                    return array(
                        'valid' => true,
                        'message' => isset($data['message']) ? $data['message'] : __('License is valid.', 'wp-slatan-theme'),
                        'data' => $data,
                    );
                }

                // Return error from server
                if (is_array($data) && isset($data['message'])) {
                    return array(
                        'valid' => false,
                        'message' => $data['message'],
                    );
                }
            }
        }

        return array(
            'valid' => false,
            'message' => __('Unable to connect to license server.', 'wp-slatan-theme'),
        );
    }

    /**
     * Clear update cache
     */
    public function clear_cache()
    {
        delete_transient($this->cache_key);
    }

    /**
     * After theme update
     *
     * @param WP_Upgrader $upgrader Upgrader instance.
     * @param array       $hook_extra Extra arguments.
     */
    public function after_update($upgrader, $hook_extra)
    {
        if (isset($hook_extra['type']) && 'theme' === $hook_extra['type']) {
            $this->clear_cache();
        }
    }

    /**
     * Get license status
     *
     * @return array License status info.
     */
    public function get_license_status()
    {
        $settings = get_option('wpslt_settings', array());
        $license_key = isset($settings['license_key']) ? $settings['license_key'] : '';

        if (empty($license_key)) {
            return array(
                'status' => 'inactive',
                'message' => __('No license key entered.', 'wp-slatan-theme'),
            );
        }

        // Check cached status
        $cached_status = get_transient('wpslt_license_status');
        if (false !== $cached_status) {
            return $cached_status;
        }

        // Validate license
        $result = $this->validate_license($license_key);

        $status = array(
            'status' => $result['valid'] ? 'active' : 'invalid',
            'message' => $result['message'],
        );

        // Cache for 12 hours
        set_transient('wpslt_license_status', $status, 12 * HOUR_IN_SECONDS);

        return $status;
    }

    /**
     * Get update servers
     *
     * @return array List of update server URLs.
     */
    public function get_update_servers()
    {
        return $this->update_servers;
    }
}

// Initialize the updater
function wpslt_theme_updater()
{
    return WPSLT_Theme_Updater::get_instance();
}

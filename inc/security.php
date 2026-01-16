<?php
/**
 * Security hardening for WP Slatan Theme
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add security headers
 */
function wpslt_security_headers()
{
    // Only on frontend
    if (is_admin()) {
        return;
    }

    // Ensure headers haven't been sent
    if (headers_sent()) {
        return;
    }

    // X-Content-Type-Options - Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // X-Frame-Options - Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // X-XSS-Protection - Enable browser XSS filter
    header('X-XSS-Protection: 1; mode=block');

    // Referrer-Policy - Control referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Permissions-Policy - Restrict browser features
    $permissions = apply_filters('wpslt_permissions_policy', array(
        'accelerometer' => '()',
        'autoplay' => '()',
        'camera' => '()',
        'cross-origin-isolated' => '()',
        'display-capture' => '()',
        'encrypted-media' => '()',
        'fullscreen' => '(self)',
        'geolocation' => '()',
        'gyroscope' => '()',
        'magnetometer' => '()',
        'microphone' => '()',
        'midi' => '()',
        'payment' => '()',
        'picture-in-picture' => '()',
        'publickey-credentials-get' => '()',
        'screen-wake-lock' => '()',
        'usb' => '()',
    ));

    $policy_string = '';
    foreach ($permissions as $feature => $value) {
        $policy_string .= $feature . '=' . $value . ', ';
    }
    header('Permissions-Policy: ' . rtrim($policy_string, ', '));
}
add_action('send_headers', 'wpslt_security_headers');

/**
 * Disable XML-RPC (optional - can be toggled via theme settings)
 */
function wpslt_disable_xmlrpc()
{
    $settings = get_option('wpslt_settings', array());

    if (!empty($settings['disable_xmlrpc'])) {
        add_filter('xmlrpc_enabled', '__return_false');

        // Remove X-Pingback header
        add_filter('wp_headers', function ($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });
    }
}
add_action('init', 'wpslt_disable_xmlrpc');

/**
 * Disable file editor in admin
 */
function wpslt_disable_file_editor()
{
    $settings = get_option('wpslt_settings', array());

    if (!empty($settings['disable_file_editor']) && !defined('DISALLOW_FILE_EDIT')) {
        define('DISALLOW_FILE_EDIT', true);
    }
}
add_action('init', 'wpslt_disable_file_editor', 1);

/**
 * Remove WordPress version from scripts and styles
 *
 * @param string $src Source URL.
 * @return string
 */
function wpslt_remove_version_from_assets($src)
{
    if (strpos($src, 'ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}
// Only enable if setting is active
add_action('init', function () {
    $settings = get_option('wpslt_settings', array());

    if (!empty($settings['remove_version_strings'])) {
        add_filter('style_loader_src', 'wpslt_remove_version_from_assets', 9999);
        add_filter('script_loader_src', 'wpslt_remove_version_from_assets', 9999);
    }
});

/**
 * Disable author archives to prevent username enumeration
 */
function wpslt_disable_author_archives()
{
    $settings = get_option('wpslt_settings', array());

    if (!empty($settings['disable_author_archives'])) {
        if (is_author()) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }
}
add_action('template_redirect', 'wpslt_disable_author_archives');

/**
 * Disable REST API user enumeration for non-logged-in users
 *
 * @param mixed           $result  Response to replace the requested version with.
 * @param WP_REST_Server  $server  Server instance.
 * @param WP_REST_Request $request Request used to generate the response.
 * @return mixed
 */
function wpslt_disable_rest_user_enumeration($result, $server, $request)
{
    $settings = get_option('wpslt_settings', array());

    if (empty($settings['disable_rest_users'])) {
        return $result;
    }

    $route = $request->get_route();

    // Block /wp/v2/users endpoint for non-logged-in users
    if (preg_match('/^\/wp\/v2\/users/', $route) && !is_user_logged_in()) {
        return new WP_Error(
            'rest_cannot_access',
            __('Access denied.', 'wp-slatan-theme'),
            array('status' => 401)
        );
    }

    return $result;
}
add_filter('rest_pre_dispatch', 'wpslt_disable_rest_user_enumeration', 10, 3);

/**
 * Add noindex to login and register pages
 */
function wpslt_noindex_login()
{
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
}
add_action('login_head', 'wpslt_noindex_login');

/**
 * Limit login attempts (basic implementation)
 * For production, recommend using a dedicated plugin
 */
function wpslt_limit_login_attempts()
{
    $settings = get_option('wpslt_settings', array());

    if (empty($settings['limit_login_attempts'])) {
        return;
    }

    $ip = wpslt_get_client_ip();
    $transient_key = 'wpslt_login_attempts_' . md5($ip);
    $attempts = get_transient($transient_key);

    if ($attempts === false) {
        $attempts = 0;
    }

    // Max 5 attempts per 15 minutes
    if ($attempts >= 5) {
        wp_die(
            esc_html__('Too many login attempts. Please try again in 15 minutes.', 'wp-slatan-theme'),
            esc_html__('Login Blocked', 'wp-slatan-theme'),
            array('response' => 403)
        );
    }
}
add_action('wp_login_failed', function () {
    $settings = get_option('wpslt_settings', array());

    if (empty($settings['limit_login_attempts'])) {
        return;
    }

    $ip = wpslt_get_client_ip();
    $transient_key = 'wpslt_login_attempts_' . md5($ip);
    $attempts = get_transient($transient_key);

    if ($attempts === false) {
        $attempts = 0;
    }

    set_transient($transient_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
});
add_action('wp_authenticate', 'wpslt_limit_login_attempts', 1);

/**
 * Get client IP address
 * 
 * Note: Prioritizes REMOTE_ADDR for security. 
 * Only uses forwarded headers if behind trusted proxy.
 *
 * @return string
 */
function wpslt_get_client_ip()
{
    $ip = '';

    // List of trusted proxy headers (only use if you trust your server config)
    $trusted_headers = apply_filters('wpslt_trusted_proxy_headers', array());

    // First, try REMOTE_ADDR (most reliable, cannot be spoofed)
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
    }

    // Only check forwarded headers if trusted proxies are configured
    if (!empty($trusted_headers)) {
        foreach ($trusted_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $forwarded_ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                // Get the first IP if multiple (client IP is typically first)
                if (strpos($forwarded_ip, ',') !== false) {
                    $ips = explode(',', $forwarded_ip);
                    $forwarded_ip = trim($ips[0]);
                }
                if (filter_var($forwarded_ip, FILTER_VALIDATE_IP)) {
                    $ip = $forwarded_ip;
                    break;
                }
            }
        }
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Hide login error messages (don't reveal if username exists)
 *
 * @param string $error Error message.
 * @return string
 */
function wpslt_hide_login_errors($error)
{
    $settings = get_option('wpslt_settings', array());

    if (!empty($settings['hide_login_errors'])) {
        return __('Invalid login credentials.', 'wp-slatan-theme');
    }

    return $error;
}
add_filter('login_errors', 'wpslt_hide_login_errors');

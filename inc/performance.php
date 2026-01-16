<?php
/**
 * Performance optimizations for WP Slatan Theme
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove emoji scripts and styles
 */
function wpslt_disable_emojis()
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

    // Remove from TinyMCE
    add_filter('tiny_mce_plugins', 'wpslt_disable_emojis_tinymce');
    add_filter('wp_resource_hints', 'wpslt_disable_emojis_dns_prefetch', 10, 2);
}
add_action('init', 'wpslt_disable_emojis');

/**
 * Filter out emoji plugin from TinyMCE
 *
 * @param array $plugins TinyMCE plugins.
 * @return array
 */
function wpslt_disable_emojis_tinymce($plugins)
{
    if (is_array($plugins)) {
        return array_diff($plugins, array('wpemoji'));
    }
    return $plugins;
}

/**
 * Remove emoji CDN hostname from DNS prefetch hints
 *
 * @param array  $urls          URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array
 */
function wpslt_disable_emojis_dns_prefetch($urls, $relation_type)
{
    if ('dns-prefetch' === $relation_type) {
        $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/');
        $urls = array_filter(
            $urls,
            function ($url) use ($emoji_svg_url) {
                return strpos($url, $emoji_svg_url) === false;
            }
        );
    }
    return $urls;
}

/**
 * Dequeue jQuery Migrate on frontend
 */
function wpslt_dequeue_jquery_migrate($scripts)
{
    $settings = get_option('wpslt_settings', array());

    // Check if optimization is enabled (default: true)
    $enabled = isset($settings['remove_jquery_migrate']) ? $settings['remove_jquery_migrate'] : true;

    if (!$enabled) {
        return;
    }

    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, array('jquery-migrate'));
        }
    }
}
add_action('wp_default_scripts', 'wpslt_dequeue_jquery_migrate');

/**
 * Remove unnecessary header meta tags
 */
function wpslt_remove_header_meta()
{
    $settings = get_option('wpslt_settings', array());

    // Check if optimization is enabled (default: true)
    $enabled = isset($settings['clean_header']) ? $settings['clean_header'] : true;

    if (!$enabled) {
        return;
    }

    // Remove WordPress version
    remove_action('wp_head', 'wp_generator');

    // Remove wlwmanifest link (Windows Live Writer)
    remove_action('wp_head', 'wlwmanifest_link');

    // Remove RSD link
    remove_action('wp_head', 'rsd_link');

    // Remove shortlink
    remove_action('wp_head', 'wp_shortlink_wp_head');

    // Remove REST API link
    remove_action('wp_head', 'rest_output_link_wp_head');

    // Remove oEmbed discovery links
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
}
add_action('init', 'wpslt_remove_header_meta');

/**
 * Defer non-critical JavaScript
 *
 * @param string $tag    Script HTML tag.
 * @param string $handle Script handle.
 * @param string $src    Script source URL.
 * @return string
 */
function wpslt_defer_scripts($tag, $handle, $src)
{
    $settings = get_option('wpslt_settings', array());

    // Check if optimization is enabled (default: true)
    $enabled = isset($settings['defer_scripts']) ? $settings['defer_scripts'] : true;

    if (!$enabled) {
        return $tag;
    }

    // Don't defer in admin
    if (is_admin()) {
        return $tag;
    }

    // Scripts to defer
    $defer_scripts = array(
        'wp-slatan-theme-navigation',
        'comment-reply',
    );

    // Scripts to NOT defer (critical scripts)
    $no_defer = array(
        'jquery-core',
        'jquery',
        'elementor-frontend',
    );

    if (in_array($handle, $no_defer, true)) {
        return $tag;
    }

    if (in_array($handle, $defer_scripts, true)) {
        return str_replace(' src=', ' defer src=', $tag);
    }

    return $tag;
}
add_filter('script_loader_tag', 'wpslt_defer_scripts', 10, 3);

/**
 * Preload critical assets
 */
function wpslt_preload_assets()
{
    // Preload main stylesheet
    echo '<link rel="preload" href="' . esc_url(get_stylesheet_uri()) . '" as="style">' . "\n";
}
add_action('wp_head', 'wpslt_preload_assets', 1);

/**
 * Add async/defer to specific scripts
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @return string
 */
function wpslt_async_scripts($tag, $handle)
{
    // Scripts to load async
    $async_scripts = array();

    if (in_array($handle, $async_scripts, true)) {
        return str_replace(' src=', ' async src=', $tag);
    }

    return $tag;
}
add_filter('script_loader_tag', 'wpslt_async_scripts', 10, 2);

/**
 * Disable self-pingbacks
 *
 * @param array $links Links to ping.
 */
function wpslt_disable_self_pingbacks(&$links)
{
    $settings = get_option('wpslt_settings', array());

    // Check if optimization is enabled (default: true)
    $enabled = isset($settings['disable_self_pingbacks']) ? $settings['disable_self_pingbacks'] : true;

    if (!$enabled) {
        return;
    }

    $home = get_option('home');
    foreach ($links as $l => $link) {
        if (0 === strpos($link, $home)) {
            unset($links[$l]);
        }
    }
}
add_action('pre_ping', 'wpslt_disable_self_pingbacks');

/**
 * Limit post revisions
 *
 * @param int     $num  Number of revisions to keep.
 * @param WP_Post $post Post object.
 * @return int
 */
function wpslt_limit_revisions($num, $post)
{
    $settings = get_option('wpslt_settings', array());
    $limit = isset($settings['revisions_limit']) ? absint($settings['revisions_limit']) : 5;
    return $limit;
}
add_filter('wp_revisions_to_keep', 'wpslt_limit_revisions', 10, 2);

/**
 * Disable Dashicons on frontend for non-logged users
 */
function wpslt_disable_dashicons()
{
    $settings = get_option('wpslt_settings', array());

    if (!empty($settings['disable_dashicons']) && !is_user_logged_in()) {
        wp_dequeue_style('dashicons');
        wp_deregister_style('dashicons');
    }
}
add_action('wp_enqueue_scripts', 'wpslt_disable_dashicons', 100);

/**
 * Disable oEmbed functionality
 */
function wpslt_disable_oembed()
{
    $settings = get_option('wpslt_settings', array());

    if (!empty($settings['disable_oembed'])) {
        // Remove oEmbed discovery links
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');

        // Remove oEmbed REST API endpoint
        remove_action('rest_api_init', 'wp_oembed_register_route');

        // Turn off oEmbed auto discovery
        add_filter('embed_oembed_discover', '__return_false');

        // Remove oEmbed-specific JavaScript
        remove_action('wp_head', 'wp_oembed_add_host_js');

        // Remove filter for the oEmbed result
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

        // Remove oEmbed rewrite rules
        add_filter('rewrite_rules_array', 'wpslt_disable_oembed_rewrites');
    }
}
add_action('init', 'wpslt_disable_oembed', 9999);

/**
 * Remove oEmbed rewrite rules
 *
 * @param array $rules Rewrite rules.
 * @return array
 */
function wpslt_disable_oembed_rewrites($rules)
{
    foreach ($rules as $rule => $rewrite) {
        if (strpos($rewrite, 'embed=true') !== false) {
            unset($rules[$rule]);
        }
    }
    return $rules;
}

/**
 * Control WordPress Heartbeat API
 */
function wpslt_heartbeat_control()
{
    $settings = get_option('wpslt_settings', array());

    // Disable heartbeat on frontend
    if (!empty($settings['disable_heartbeat_frontend']) && !is_admin()) {
        wp_deregister_script('heartbeat');
        return;
    }

    // Reduce heartbeat frequency
    if (!empty($settings['reduce_heartbeat'])) {
        add_filter('heartbeat_settings', 'wpslt_heartbeat_frequency');
    }
}
add_action('init', 'wpslt_heartbeat_control', 1);

/**
 * Modify heartbeat frequency
 *
 * @param array $settings Heartbeat settings.
 * @return array
 */
function wpslt_heartbeat_frequency($settings)
{
    $settings['interval'] = 60; // 60 seconds instead of default 15-60
    return $settings;
}


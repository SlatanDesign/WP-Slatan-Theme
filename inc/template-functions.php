<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function wpslt_body_classes($classes)
{
	// Adds a class of hfeed to non-singular pages.
	if (!is_singular()) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if (!is_active_sidebar('sidebar-1')) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter('body_class', 'wpslt_body_classes');

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function wpslt_pingback_header()
{
	if (is_singular() && pings_open()) {
		printf('<link rel="pingback" href="%s">', esc_url(get_bloginfo('pingback_url')));
	}
}
add_action('wp_head', 'wpslt_pingback_header');

/**
 * Check if current page is built with Elementor
 *
 * @param int $post_id Post ID (optional).
 * @return bool
 */
function wpslt_is_built_with_elementor($post_id = null)
{
	if (!wpslt_is_elementor_active()) {
		return false;
	}

	if (null === $post_id) {
		$post_id = get_the_ID();
	}

	if (!$post_id) {
		return false;
	}

	return \Elementor\Plugin::$instance->documents->get($post_id)->is_built_with_elementor();
}

/**
 * Get page content container class based on settings
 *
 * @return string
 */
function wpslt_get_container_class()
{
	$classes = array('wpslt-container');

	// Check if full width on Elementor pages
	if (wpslt_is_elementor_active() && is_singular()) {
		$document = \Elementor\Plugin::$instance->documents->get(get_the_ID());
		if ($document && $document->is_built_with_elementor()) {
			$classes[] = 'elementor-container';
		}
	}

	return implode(' ', $classes);
}

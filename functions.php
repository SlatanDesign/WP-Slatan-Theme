<?php
/**
 * WP Slatan Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Theme version constant
 */
define('WPSLT_VERSION', '1.0.22');

/**
 * Theme directory path
 */
define('WPSLT_DIR', get_template_directory());

/**
 * Theme directory URI
 */
define('WPSLT_URI', get_template_directory_uri());

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function wpslt_setup()
{
	/*
	 * Make theme available for translation.
	 */
	load_theme_textdomain('wp-slatan-theme', WPSLT_DIR . '/languages');

	// Add default posts and comments RSS feed links to head.
	add_theme_support('automatic-feed-links');

	/*
	 * Let WordPress manage the document title.
	 */
	add_theme_support('title-tag');

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 */
	add_theme_support('post-thumbnails');

	// Register navigation menus
	register_nav_menus(
		array(
			'primary' => esc_html__('Primary Menu', 'wp-slatan-theme'),
			'footer' => esc_html__('Footer Menu', 'wp-slatan-theme'),
		)
	);

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'wpslt_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support('customize-selective-refresh-widgets');

	/**
	 * Add support for core custom logo.
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height' => 100,
			'width' => 350,
			'flex-width' => true,
			'flex-height' => true,
		)
	);

	// Add support for responsive embeds.
	add_theme_support('responsive-embeds');

	// Add support for wide and full alignment (Gutenberg).
	add_theme_support('align-wide');

	// Editor styles
	add_theme_support('editor-styles');
}
add_action('after_setup_theme', 'wpslt_setup');

/**
 * Set the content width in pixels.
 *
 * @global int $content_width
 */
function wpslt_content_width()
{
	$GLOBALS['content_width'] = apply_filters('wpslt_content_width', 1140);
}
add_action('after_setup_theme', 'wpslt_content_width', 0);

/**
 * Register widget area.
 */
function wpslt_widgets_init()
{
	register_sidebar(
		array(
			'name' => esc_html__('Sidebar', 'wp-slatan-theme'),
			'id' => 'sidebar-1',
			'description' => esc_html__('Add widgets here.', 'wp-slatan-theme'),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget' => '</section>',
			'before_title' => '<h2 class="widget-title">',
			'after_title' => '</h2>',
		)
	);

	register_sidebar(
		array(
			'name' => esc_html__('Footer Widget Area', 'wp-slatan-theme'),
			'id' => 'footer-1',
			'description' => esc_html__('Add footer widgets here.', 'wp-slatan-theme'),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		)
	);
}
add_action('widgets_init', 'wpslt_widgets_init');

/**
 * Enqueue scripts and styles.
 */
function wpslt_scripts()
{
	// Main stylesheet
	wp_enqueue_style(
		'wp-slatan-theme-style',
		get_stylesheet_uri(),
		array(),
		WPSLT_VERSION
	);

	// Navigation script (only if needed)
	if (!wpslt_is_elementor_header_active()) {
		wp_enqueue_script(
			'wp-slatan-theme-navigation',
			WPSLT_URI . '/js/navigation.js',
			array(),
			WPSLT_VERSION,
			true
		);
	}

	// Comment reply script
	if (is_singular() && comments_open() && get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}
}
add_action('wp_enqueue_scripts', 'wpslt_scripts');

/*--------------------------------------------------------------
# Elementor Integration
--------------------------------------------------------------*/

/**
 * Check if Elementor is active
 *
 * @return bool
 */
function wpslt_is_elementor_active()
{
	return defined('ELEMENTOR_VERSION');
}

/**
 * Check if Elementor Pro is active
 *
 * @return bool
 */
function wpslt_is_elementor_pro_active()
{
	return defined('ELEMENTOR_PRO_VERSION');
}

/**
 * Check if Elementor header is active
 *
 * @return bool
 */
function wpslt_is_elementor_header_active()
{
	if (!wpslt_is_elementor_pro_active()) {
		return false;
	}

	if (!function_exists('elementor_theme_do_location')) {
		return false;
	}

	// Check if there's a header template assigned
	$locations = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_locations_manager();
	$header_documents = $locations->get_documents_for_location('header');

	return !empty($header_documents);
}

/**
 * Check if Elementor footer is active
 *
 * @return bool
 */
function wpslt_is_elementor_footer_active()
{
	if (!wpslt_is_elementor_pro_active()) {
		return false;
	}

	if (!function_exists('elementor_theme_do_location')) {
		return false;
	}

	// Check if there's a footer template assigned
	$locations = \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_locations_manager();
	$footer_documents = $locations->get_documents_for_location('footer');

	return !empty($footer_documents);
}

/**
 * Register Elementor theme locations
 *
 * @param \ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager
 */
function wpslt_register_elementor_locations($elementor_theme_manager)
{
	$elementor_theme_manager->register_location('header');
	$elementor_theme_manager->register_location('footer');
}
add_action('elementor/theme/register_locations', 'wpslt_register_elementor_locations');

/**
 * Add Elementor page template classes
 *
 * @param array $classes Body classes.
 * @return array
 */
function wpslt_elementor_body_classes($classes)
{
	// Check if editing with Elementor
	if (wpslt_is_elementor_active()) {
		if (\Elementor\Plugin::$instance->preview->is_preview_mode()) {
			$classes[] = 'elementor-preview';
		}
	}

	// Add full-width class for Elementor pages and CPTs
	if (is_singular() && wpslt_is_elementor_active()) {
		$document = \Elementor\Plugin::$instance->documents->get(get_the_ID());
		if ($document && $document->is_built_with_elementor()) {
			$classes[] = 'elementor-page';
		}
	}

	return $classes;
}
add_filter('body_class', 'wpslt_elementor_body_classes');

/*--------------------------------------------------------------
# Include Required Files
--------------------------------------------------------------*/

/**
 * Custom template tags for this theme.
 */
require WPSLT_DIR . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require WPSLT_DIR . '/inc/template-functions.php';

/**
 * Performance optimizations.
 */
require WPSLT_DIR . '/inc/performance.php';

/**
 * Security hardening.
 */
require WPSLT_DIR . '/inc/security.php';

/**
 * Theme Settings / Control Panel.
 */
require WPSLT_DIR . '/inc/class-theme-settings.php';

/**
 * Code Snippets feature.
 */
require WPSLT_DIR . '/inc/snippets/loader.php';

/**
 * Floating Contact feature.
 */
require WPSLT_DIR . '/inc/contact/class-contact-admin.php';
require WPSLT_DIR . '/inc/contact/class-contact-output.php';

/**
 * Cookie Consent feature.
 */
require WPSLT_DIR . '/inc/cookie/class-cookie-admin.php';
require WPSLT_DIR . '/inc/cookie/class-cookie-output.php';

/**
 * Theme Updater - Automatic updates from Slatan Update Server.
 */
require WPSLT_DIR . '/inc/class-theme-updater.php';
wpslt_theme_updater();


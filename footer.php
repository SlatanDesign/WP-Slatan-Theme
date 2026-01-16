<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Elementor Footer Support
 * If Elementor Pro footer is set, use it. Otherwise, show default footer.
 * Also check if default footer is disabled via theme settings.
 */
$footer_rendered = false;

if (function_exists('elementor_theme_do_location')) {
	$footer_rendered = elementor_theme_do_location('footer');
}

// Check if default footer is disabled in theme settings
$settings = get_option('wpslt_settings', array());
$footer_disabled = !empty($settings['disable_default_footer']);

if (!$footer_rendered && !$footer_disabled):
	// Default fallback footer
	?>
	<footer id="colophon" class="site-footer">
		<div class="site-info">
			<?php
			printf(
				/* translators: %s: Theme author. */
				esc_html__('Powered by %s', 'wp-slatan-theme'),
				'<a href="https://slatan.design/" target="_blank" rel="noopener">Slatan Design</a>'
			);
			?>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
	<?php
endif;
?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>
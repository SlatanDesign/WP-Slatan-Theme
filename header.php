<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
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
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

	<div id="page" class="site">
		<a class="skip-link screen-reader-text" href="#primary">
			<?php esc_html_e('Skip to content', 'wp-slatan-theme'); ?>
		</a>

		<?php
		/**
		 * Elementor Header Support
		 * If Elementor Pro header is set, use it. Otherwise, show default header.
		 * Also check if default header is disabled via theme settings.
		 */
		$header_rendered = false;

		if (function_exists('elementor_theme_do_location')) {
			$header_rendered = elementor_theme_do_location('header');
		}

		// Check if default header is disabled in theme settings
		$settings = get_option('wpslt_settings', array());
		$header_disabled = !empty($settings['disable_default_header']);

		if (!$header_rendered && !$header_disabled):
			// Default fallback header
			?>
			<header id="masthead" class="site-header">
				<div class="container">
					<div class="site-branding">
						<?php
						if (has_custom_logo()):
							the_custom_logo();
						else:
							if (is_front_page() && is_home()):
								?>
								<h1 class="site-title">
									<a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
										<?php bloginfo('name'); ?>
									</a>
								</h1>
								<?php
							else:
								?>
								<p class="site-title">
									<a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
										<?php bloginfo('name'); ?>
									</a>
								</p>
								<?php
							endif;

							$wpslt_description = get_bloginfo('description', 'display');
							if ($wpslt_description || is_customize_preview()):
								?>
								<p class="site-description"><?php echo esc_html($wpslt_description); ?></p>
								<?php
							endif;
						endif;
						?>
					</div><!-- .site-branding -->

					<?php if (has_nav_menu('primary')): ?>
						<nav id="site-navigation" class="main-navigation"
							aria-label="<?php esc_attr_e('Primary Menu', 'wp-slatan-theme'); ?>">
							<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
								<span class="screen-reader-text"><?php esc_html_e('Menu', 'wp-slatan-theme'); ?></span>
								<span class="menu-icon"></span>
							</button>
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'primary',
									'menu_id' => 'primary-menu',
									'container' => false,
								)
							);
							?>
						</nav><!-- #site-navigation -->
					<?php endif; ?>
				</div><!-- .container -->
			</header><!-- #masthead -->
			<?php
		endif;
		?>
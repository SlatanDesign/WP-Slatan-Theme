<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main">

	<section class="error-404 not-found">
		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e('Oops! That page can&rsquo;t be found.', 'wp-slatan-theme'); ?>
			</h1>
		</header><!-- .page-header -->

		<div class="page-content">
			<p><?php esc_html_e('It looks like nothing was found at this location. Maybe try a search?', 'wp-slatan-theme'); ?>
			</p>

			<?php get_search_form(); ?>

		</div><!-- .page-content -->
	</section><!-- .error-404 -->

</main><!-- #main -->

<?php
get_footer();

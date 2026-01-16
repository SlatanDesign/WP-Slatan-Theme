<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
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

	<?php
	while (have_posts()):
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php if (!is_front_page()): ?>
				<header class="entry-header">
					<?php the_title('<h1 class="entry-title">', '</h1>'); ?>
				</header>
			<?php endif; ?>

			<div class="entry-content">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__('Pages:', 'wp-slatan-theme'),
						'after' => '</div>',
					)
				);
				?>
			</div>

			<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if (comments_open() || get_comments_number()):
				comments_template();
			endif;
			?>
		</article>
		<?php
	endwhile;
	?>

</main><!-- #main -->

<?php
get_sidebar();
get_footer();

<?php
/**
 * Template Name: Boxed Content
 * Template Post Type: page, post
 *
 * Content is contained within a max-width container.
 * Ideal for articles and blog posts using Block Editor.
 * Shows sidebar if active.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main">
    <div class="container">
        <div class="content-area">
            <?php
            while (have_posts()):
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                        <?php if ('post' === get_post_type()): ?>
                            <div class="entry-meta">
                                <?php
                                wpslt_posted_on();
                                wpslt_posted_by();
                                ?>
                            </div>
                        <?php endif; ?>
                    </header>

                    <?php wpslt_post_thumbnail(); ?>

                    <div class="entry-content">
                        <?php the_content(); ?>

                        <?php
                        wp_link_pages(array(
                            'before' => '<div class="page-links">' . esc_html__('Pages:', 'wp-slatan-theme'),
                            'after' => '</div>',
                        ));
                        ?>
                    </div>

                    <footer class="entry-footer">
                        <?php wpslt_entry_footer(); ?>
                    </footer>
                </article>

                <?php
                // If comments are open or we have at least one comment, load up the comment template.
                if (comments_open() || get_comments_number()):
                    comments_template();
                endif;

            endwhile;
            ?>
        </div>

        <?php get_sidebar(); ?>
    </div>
</main>

<?php
get_footer();

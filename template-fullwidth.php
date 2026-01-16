<?php
/**
 * Template Name: Full Width
 * Template Post Type: page, post
 *
 * Full width template without sidebar.
 * Uses theme header and footer.
 * Content has no container width restrictions.
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

<main id="primary" class="site-main wpslt-full-width">

    <?php
    while (have_posts()):
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php
            if (!is_front_page()):
                the_title('<header class="entry-header"><h1 class="entry-title">', '</h1></header>');
            endif;
            ?>

            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>

</main>

<?php
get_footer();

<?php
/**
 * The sidebar containing the main widget area
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

if (!is_active_sidebar('sidebar-1')) {
	return;
}
?>

<aside id="secondary" class="widget-area" role="complementary">
	<?php dynamic_sidebar('sidebar-1'); ?>
</aside><!-- #secondary -->
<?php
/**
 * WP Slatan Theme - General Tab
 * Renders the General settings tab content
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render General tab content
 *
 * @param array $settings Current settings values.
 */
function wpslt_render_tab_general($settings)
{
    $option_name = 'wpslt_settings';
    ?>
    <div class="wpslt-card">
        <h3><?php esc_html_e('📐 Layout Settings', 'wp-slatan-theme'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Container Width', 'wp-slatan-theme'); ?></th>
                <td>
                    <div class="wpslt-number-input">
                        <input type="number" name="<?php echo esc_attr($option_name . '[container_width]'); ?>"
                            value="<?php echo esc_attr(isset($settings['container_width']) ? $settings['container_width'] : 1140); ?>"
                            min="320" max="2560" class="small-text">
                        <span class="unit">px</span>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Maximum width for content container.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable Default Header', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_default_header]'); ?>"
                            value="1" <?php checked(!empty($settings['disable_default_header'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Hide the default theme header. Enable this when using Elementor Theme Builder to create a custom header, or when you want a blank page without header.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable Default Footer', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_default_footer]'); ?>"
                            value="1" <?php checked(!empty($settings['disable_default_footer'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Hide the default theme footer. Enable this when using Elementor Theme Builder to create a custom footer, or when you want a blank page without footer.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="wpslt-info-box">
        <h3><?php esc_html_e('🔌 Elementor Integration', 'wp-slatan-theme'); ?></h3>
        <?php if (wpslt_is_elementor_active()): ?>
            <p class="wpslt-status-active">
                ✓ <?php esc_html_e('Elementor is active', 'wp-slatan-theme'); ?>
                <?php if (wpslt_is_elementor_pro_active()): ?>
                    (<?php esc_html_e('Pro version detected', 'wp-slatan-theme'); ?>)
                <?php endif; ?>
            </p>
            <p class="description">
                <?php esc_html_e('Theme supports header, footer, single, and archive locations. Elementor Theme Builder templates will automatically override theme defaults.', 'wp-slatan-theme'); ?>
            </p>
        <?php else: ?>
            <p class="wpslt-status-inactive">
                ⚠ <?php esc_html_e('Elementor is not installed.', 'wp-slatan-theme'); ?>
            </p>
            <p class="description">
                <?php esc_html_e('Install Elementor for the best experience. The theme is optimized specifically for Elementor page builder.', 'wp-slatan-theme'); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

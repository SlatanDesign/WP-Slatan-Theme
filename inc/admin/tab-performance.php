<?php
/**
 * WP Slatan Theme - Performance Tab
 * Renders the Performance settings tab content
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Performance tab content
 *
 * @param array $settings Current settings values.
 */
function wpslt_render_tab_performance($settings)
{
    $option_name = 'wpslt_settings';
    ?>
    <div class="wpslt-card">
        <h3><?php esc_html_e('⚡ Performance Optimization', 'wp-slatan-theme'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Disable Emojis', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_emojis]'); ?>" value="1"
                            <?php checked(isset($settings['disable_emojis']) ? $settings['disable_emojis'] : true); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Removes WordPress emoji JavaScript and CSS files (~20KB). Modern browsers support emojis natively.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable Dashicons', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_dashicons]'); ?>"
                            value="1" <?php checked(!empty($settings['disable_dashicons'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Removes Dashicons CSS on frontend for non-logged-in users (~46KB). Safe to enable unless your theme uses Dashicons.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable oEmbed', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_oembed]'); ?>" value="1"
                            <?php checked(!empty($settings['disable_oembed'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Disables oEmbed which allows embedding content from other sites. Enable if you don\'t embed YouTube, Twitter, etc.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable Heartbeat (Frontend)', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_heartbeat_frontend]'); ?>"
                            value="1" <?php checked(!empty($settings['disable_heartbeat_frontend'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Disables WordPress Heartbeat API on frontend. Reduces server requests from visitors. Admin functions remain unaffected.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Reduce Heartbeat Frequency', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[reduce_heartbeat]'); ?>" value="1"
                            <?php checked(!empty($settings['reduce_heartbeat'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Reduces Heartbeat frequency from 15-60 seconds to 60 seconds. Lowers server load while maintaining autosave functionality.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Limit Post Revisions', 'wp-slatan-theme'); ?></th>
                <td>
                    <div class="wpslt-number-input">
                        <input type="number" name="<?php echo esc_attr($option_name . '[revisions_limit]'); ?>"
                            value="<?php echo esc_attr(isset($settings['revisions_limit']) ? $settings['revisions_limit'] : 5); ?>"
                            min="0" max="100" class="small-text">
                        <span class="unit"><?php esc_html_e('revisions', 'wp-slatan-theme'); ?></span>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Maximum number of post revisions to keep. Set to 0 to disable revisions. Reduces database size.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Remove jQuery Migrate', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[remove_jquery_migrate]'); ?>"
                            value="1" <?php checked(isset($settings['remove_jquery_migrate']) ? $settings['remove_jquery_migrate'] : true); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Removes jQuery Migrate script for backwards compatibility (~10KB). Modern jQuery plugins don\'t need this.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Defer Scripts', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[defer_scripts]'); ?>" value="1"
                            <?php checked(isset($settings['defer_scripts']) ? $settings['defer_scripts'] : true); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Adds defer attribute to non-critical scripts. Improves Core Web Vitals and page load speed.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Clean Header', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[clean_header]'); ?>" value="1"
                            <?php checked(isset($settings['clean_header']) ? $settings['clean_header'] : true); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Removes RSD, WLW manifest, shortlink, and generator meta tags. Cleans up HTML head section.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable Self-Pingbacks', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_self_pingbacks]'); ?>"
                            value="1" <?php checked(isset($settings['disable_self_pingbacks']) ? $settings['disable_self_pingbacks'] : true); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Prevents pingbacks to your own site. Reduces unnecessary self-notifications.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

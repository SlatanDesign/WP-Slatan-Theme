<?php
/**
 * WP Slatan Theme - Security Tab
 * Renders the Security settings tab content
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render Security tab content
 *
 * @param array $settings Current settings values.
 */
function wpslt_render_tab_security($settings)
{
    $option_name = 'wpslt_settings';
    ?>
    <div class="wpslt-card">
        <h3><?php esc_html_e('🔒 Security Hardening', 'wp-slatan-theme'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Disable XML-RPC', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_xmlrpc]'); ?>" value="1"
                            <?php checked(!empty($settings['disable_xmlrpc'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('XML-RPC is a remote procedure call protocol. Disabling it prevents brute force attacks and DDoS amplification. Recommended unless you use mobile apps or Jetpack.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable File Editor', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_file_editor]'); ?>"
                            value="1" <?php checked(!empty($settings['disable_file_editor'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Disables the built-in theme/plugin file editor in WordPress admin. Prevents attackers from injecting malicious code if they gain admin access.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Hide Login Errors', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[hide_login_errors]'); ?>"
                            value="1" <?php checked(!empty($settings['hide_login_errors'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Shows a generic error message on login failure instead of revealing whether the username or password was incorrect. Prevents username enumeration.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Limit Login Attempts', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[limit_login_attempts]'); ?>"
                            value="1" <?php checked(!empty($settings['limit_login_attempts'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Limits failed login attempts to 5 per IP address within 15 minutes. Protects against brute force password attacks.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable Author Archives', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_author_archives]'); ?>"
                            value="1" <?php checked(!empty($settings['disable_author_archives'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Redirects author archive pages (example.com/author/admin) to the homepage. Prevents attackers from discovering usernames via author archives.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Disable REST API Users', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[disable_rest_users]'); ?>"
                            value="1" <?php checked(!empty($settings['disable_rest_users'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Blocks access to /wp-json/wp/v2/users endpoint for non-logged-in users. Prevents username enumeration via REST API.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Remove Version Strings', 'wp-slatan-theme'); ?></th>
                <td>
                    <label class="wpslt-toggle">
                        <input type="checkbox" name="<?php echo esc_attr($option_name . '[remove_version_strings]'); ?>"
                            value="1" <?php checked(!empty($settings['remove_version_strings'])); ?>>
                        <span class="wpslt-toggle-slider"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Removes version query strings (?ver=x.x.x) from CSS and JavaScript files. Makes it harder for attackers to identify vulnerable plugin/theme versions.', 'wp-slatan-theme'); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

<?php
/**
 * Floating Contact Output Handler
 * 
 * Renders the floating contact widget on frontend.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Contact_Output
 */
class WPSLT_Contact_Output
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Settings
     */
    private $settings = null;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('wp_footer', array($this, 'render_widget'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Get settings
     */
    private function get_settings()
    {
        if (null === $this->settings) {
            $admin = WPSLT_Contact_Admin::get_instance();
            $this->settings = $admin->get_settings();
        }
        return $this->settings;
    }

    /**
     * Check if widget should display
     */
    private function should_display()
    {
        $settings = $this->get_settings();

        // Check if enabled
        if (empty($settings['enabled'])) {
            return false;
        }

        // Check if has contacts
        if (empty($settings['contacts'])) {
            return false;
        }

        // Note: We removed server-side mobile/desktop checks to support page caching.
        // Visibility is now controlled via CSS classes in render_widget().

        // Check page display rules
        $page_setting = $settings['display']['pages'];
        $page_ids = $settings['display']['page_ids'];

        if ($page_setting === 'include' && !empty($page_ids)) {
            if (!in_array(get_the_ID(), $page_ids)) {
                return false;
            }
        } elseif ($page_setting === 'exclude' && !empty($page_ids)) {
            if (in_array(get_the_ID(), $page_ids)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets()
    {
        if (!$this->should_display()) {
            return;
        }

        $settings = $this->get_settings();

        // CSS
        wp_enqueue_style(
            'wpslt-floating-contact',
            WPSLT_URI . '/assets/css/floating-contact.css',
            array(),
            WPSLT_VERSION
        );

        // Inline CSS for dynamic styles
        wp_add_inline_style('wpslt-floating-contact', $this->generate_inline_css());

        // JS
        wp_enqueue_script(
            'wpslt-floating-contact',
            WPSLT_URI . '/assets/js/floating-contact.js',
            array(),
            WPSLT_VERSION,
            true
        );

        // Font Awesome - load from CDN if enabled in settings
        if (!empty($settings['display']['load_fontawesome'])) {
            // Smart Detection: Check if Font Awesome is already loaded by other plugins (e.g., Elementor)
            // commonly used handles for Font Awesome
            $fa_handles = array(
                'elementor-icons',
                'elementor-icons-fa-solid',
                'elementor-icons-fa-brands',
                'elementor-icons-fa-regular',
                'font-awesome',
                'fontawesome',
                'font-awesome-5',
                'font-awesome-6',
                'fa5',
                'fa6'
            );

            $is_already_loaded = false;
            foreach ($fa_handles as $handle) {
                if (wp_style_is($handle, 'enqueued') || wp_style_is($handle, 'registered')) {
                    $is_already_loaded = true;
                    break;
                }
            }

            // Only enqueue if NOT already loaded
            if (!$is_already_loaded) {
                wp_enqueue_style(
                    'wpslt-font-awesome',
                    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                    array(),
                    '6.5.1'
                );
            }
        }
    }

    /**
     * Generate inline CSS
     */
    private function generate_inline_css()
    {
        $settings = $this->get_settings();
        $style = $settings['style'];
        $position = $settings['position'];
        $tooltip = $settings['tooltip'];
        $spacing = $settings['spacing'] ?? [];
        $accessibility = $settings['accessibility'] ?? [];
        $mobile = $settings['mobile'] ?? [];

        $css = ':root {';

        // Button styles
        $css .= '--wpslt-fc-button-size: ' . $style['button_size'] . $style['button_size_unit'] . ';';
        $css .= '--wpslt-fc-icon-size: ' . $style['icon_size'] . $style['icon_size_unit'] . ';';
        $css .= '--wpslt-fc-primary-color: ' . $style['primary_color'] . ';';
        $css .= '--wpslt-fc-border-radius: ' . $style['border_radius'] . $style['border_radius_unit'] . ';';

        // Custom contact sizing (if enabled)
        if (!empty($style['use_custom_contact_size'])) {
            $css .= '--wpslt-fc-contact-size: ' . ($style['contact_button_size'] ?? $style['button_size']) . ($style['contact_button_size_unit'] ?? $style['button_size_unit']) . ';';
            $css .= '--wpslt-fc-contact-icon-size: ' . ($style['contact_icon_size'] ?? $style['icon_size']) . ($style['contact_icon_size_unit'] ?? $style['icon_size_unit']) . ';';
        } else {
            $css .= '--wpslt-fc-contact-size: ' . $style['button_size'] . $style['button_size_unit'] . ';';
            $css .= '--wpslt-fc-contact-icon-size: ' . $style['icon_size'] . $style['icon_size_unit'] . ';';
        }

        // Position
        $css .= '--wpslt-fc-offset-x: ' . $position['offset_x'] . $position['offset_x_unit'] . ';';
        $css .= '--wpslt-fc-offset-y: ' . $position['offset_y'] . $position['offset_y_unit'] . ';';

        // Spacing & Layout
        $gap = $spacing['contact_gap'] ?? '10';
        $gap_unit = $spacing['contact_gap_unit'] ?? 'px';
        $z_index = $spacing['z_index'] ?? '99999';
        $css .= '--wpslt-fc-contact-gap: ' . $gap . $gap_unit . ';';
        $css .= '--wpslt-fc-z-index: ' . $z_index . ';';

        // Animation timing
        $anim_speed_map = array('fast' => '0.2', 'normal' => '0.3', 'slow' => '0.4');
        $anim_speed_key = $style['animation_speed'] ?? 'normal';
        $anim_speed = $anim_speed_map[$anim_speed_key] ?? '0.3';
        $menu_delay = $style['menu_item_delay'] ?? '50';
        $css .= '--wpslt-fc-anim-duration: ' . $anim_speed . 's;';
        $css .= '--wpslt-fc-menu-delay: ' . $menu_delay . 'ms;';

        // Tooltip styles
        $css .= '--wpslt-fc-tooltip-bg: ' . $tooltip['background'] . ';';
        $css .= '--wpslt-fc-tooltip-color: ' . $tooltip['text_color'] . ';';
        $css .= '--wpslt-fc-tooltip-font-size: ' . $tooltip['font_size'] . $tooltip['font_size_unit'] . ';';
        $css .= '--wpslt-fc-tooltip-radius: ' . $tooltip['border_radius'] . $tooltip['border_radius_unit'] . ';';

        $css .= '}';

        // Mobile overrides
        if (!empty($mobile['override_position']) || !empty($mobile['override_size'])) {
            $css .= '@media (max-width: 768px) {';
            $css .= ':root {';

            // Mobile position override
            if (!empty($mobile['override_position'])) {
                $mobile_offset_x = $mobile['offset_x'] ?? '20';
                $mobile_offset_x_unit = $mobile['offset_x_unit'] ?? 'px';
                $mobile_offset_y = $mobile['offset_y'] ?? '20';
                $mobile_offset_y_unit = $mobile['offset_y_unit'] ?? 'px';

                $css .= '--wpslt-fc-offset-x: ' . $mobile_offset_x . $mobile_offset_x_unit . ';';
                $css .= '--wpslt-fc-offset-y: ' . $mobile_offset_y . $mobile_offset_y_unit . ';';
            }

            // Mobile size override
            if (!empty($mobile['override_size'])) {
                $mobile_button_size = $mobile['button_size'] ?? '50';
                $mobile_button_size_unit = $mobile['button_size_unit'] ?? 'px';
                $mobile_icon_size = $mobile['icon_size'] ?? '20';
                $mobile_icon_size_unit = $mobile['icon_size_unit'] ?? 'px';

                $css .= '--wpslt-fc-button-size: ' . $mobile_button_size . $mobile_button_size_unit . ';';
                $css .= '--wpslt-fc-icon-size: ' . $mobile_icon_size . $mobile_icon_size_unit . ';';
                $css .= '--wpslt-fc-contact-size: ' . $mobile_button_size . $mobile_button_size_unit . ';';
                $css .= '--wpslt-fc-contact-icon-size: ' . $mobile_icon_size . $mobile_icon_size_unit . ';';
            }

            $css .= '}';
            $css .= '}';
        }

        // Box shadow
        if (!empty($style['box_shadow'])) {
            $css .= '.wpslt-fc-button, .wpslt-fc-contact { box-shadow: 0 4px 15px rgba(0,0,0,0.15); }';
        }

        // Open state icon color
        $open_icon_color = !empty($style['open_icon_color']) ? $style['open_icon_color'] : '#ffffff';
        $css .= '.wpslt-fc-button { color: ' . $open_icon_color . ' !important; }';

        // Close button custom colors
        $close_bg = !empty($style['close_bg_color']) ? $style['close_bg_color'] : '';
        $close_icon_color = !empty($style['close_icon_color']) ? $style['close_icon_color'] : '';

        if ($close_bg) {
            $css .= '.wpslt-floating-contact.is-open .wpslt-fc-button { background: ' . $close_bg . ' !important; }';
        }
        if ($close_icon_color) {
            $css .= '.wpslt-floating-contact.is-open .wpslt-fc-button { color: ' . $close_icon_color . ' !important; }';
        }

        // Accessibility: High Contrast Mode
        if (!empty($accessibility['high_contrast'])) {
            $css .= '.wpslt-floating-contact.wpslt-high-contrast .wpslt-fc-button { border: 2px solid currentColor; filter: contrast(1.5); }';
            $css .= '.wpslt-floating-contact.wpslt-high-contrast .wpslt-fc-contact { border: 2px solid currentColor; filter: contrast(1.5); }';
        }

        // Dynamic toggle animation keyframes (CSS keyframes cannot use CSS variables)
        $toggle_rotate_deg = !empty($style['toggle_rotate_deg']) ? $style['toggle_rotate_deg'] : '180';
        $toggle_flip_axis = !empty($style['toggle_flip_axis']) ? $style['toggle_flip_axis'] : 'Y';
        $toggle_scale_min = !empty($style['toggle_scale_min']) ? $style['toggle_scale_min'] : '0.7';
        $toggle_scale_rotate_deg = !empty($style['toggle_scale_rotate_deg']) ? $style['toggle_scale_rotate_deg'] : '360';

        // Rotate animation keyframe
        $css .= '@keyframes wpslt-toggle-rotate-in {';
        $css .= 'from { transform: rotate(0deg); }';
        $css .= 'to { transform: rotate(' . $toggle_rotate_deg . 'deg); }';
        $css .= '}';

        // Flip animation keyframe
        $css .= '@keyframes wpslt-toggle-flip-in {';
        $css .= 'from { transform: rotate' . $toggle_flip_axis . '(0deg); }';
        $css .= 'to { transform: rotate' . $toggle_flip_axis . '(180deg); }';
        $css .= '}';

        // Scale+Rotate animation keyframe
        $half_deg = intval($toggle_scale_rotate_deg) / 2;
        $css .= '@keyframes wpslt-toggle-scale-rotate-in {';
        $css .= '0% { transform: scale(1) rotate(0deg); }';
        $css .= '50% { transform: scale(' . $toggle_scale_min . ') rotate(' . $half_deg . 'deg); }';
        $css .= '100% { transform: scale(1) rotate(' . $toggle_scale_rotate_deg . 'deg); }';
        $css .= '}';

        return $css;
    }

    /**
     * Render the widget
     */
    public function render_widget()
    {
        if (!$this->should_display()) {
            return;
        }

        $settings = $this->get_settings();
        $style = $settings['style'];
        $position = $settings['position'];
        $tooltip = $settings['tooltip'];
        $contacts = $settings['contacts'];
        $accessibility = $settings['accessibility'] ?? array();
        $admin = WPSLT_Contact_Admin::get_instance();
        $preset_types = $admin->get_preset_types();

        // Sort contacts by order
        usort($contacts, function ($a, $b) {
            return ($a['order'] ?? 0) - ($b['order'] ?? 0);
        });

        // Position classes
        $position_class = 'wpslt-fc-' . $position['horizontal'] . ' wpslt-fc-' . $position['vertical'];

        // Caching compatibility: Add visibility classes instead of server-side check
        if (empty($settings['display']['mobile'])) {
            $position_class .= ' wpslt-fc-hide-mobile';
        }
        if (empty($settings['display']['desktop'])) {
            $position_class .= ' wpslt-fc-hide-desktop';
        }

        $animation_class = $style['animation'] !== 'none' ? 'wpslt-fc-animate-' . $style['animation'] : '';
        $hover_animation_class = 'wpslt-fc-hover-' . ($style['hover_animation'] ?? 'scale');
        $menu_animation_class = 'wpslt-fc-menu-' . ($style['menu_animation'] ?? 'slide');
        $toggle_animation_class = 'wpslt-fc-toggle-' . ($style['toggle_animation'] ?? 'rotate');
        $tooltip_position = $tooltip['position'] === 'auto' ? ($position['horizontal'] === 'right' ? 'left' : 'right') : $tooltip['position'];

        // Accessibility attributes
        $enable_keyboard = !empty($accessibility['enable_keyboard_nav']);
        $high_contrast = !empty($accessibility['high_contrast_mode']);
        $custom_aria_label = !empty($accessibility['custom_aria_label']) ? $accessibility['custom_aria_label'] : __('Contact menu', 'wp-slatan-theme');
        $accessibility_class = $high_contrast ? 'wpslt-high-contrast' : '';
        $use_custom_contact_size = !empty($style['use_custom_contact_size']) ? 'wpslt-fc-custom-contact-size' : '';

        // New Settings Logic
        $show_toggle_button = isset($style['show_toggle_button']) ? $style['show_toggle_button'] : true;
        $tooltip_display_mode = isset($tooltip['display_mode']) ? $tooltip['display_mode'] : 'hover';

        $output_classes = array();
        $output_classes[] = $position_class;
        $output_classes[] = $hover_animation_class;
        $output_classes[] = $menu_animation_class;
        $output_classes[] = $toggle_animation_class;
        $output_classes[] = $accessibility_class;
        $output_classes[] = $use_custom_contact_size;

        if ($tooltip_display_mode === 'always') {
            $output_classes[] = 'wpslt-tooltip-always';
        }

        if (!empty($tooltip['show_mobile'])) {
            $output_classes[] = 'wpslt-tooltip-mobile-visible';
        }

        $default_state = $style['default_state'] ?? 'closed';

        if (!$show_toggle_button) {
            $output_classes[] = 'wpslt-hide-toggle-button';
            // Force open state if toggle button is hidden, otherwise contacts would be inaccessible
            $default_state = 'open';
        }

        $classes_str = implode(' ', array_filter($output_classes));
        ?>
        <div id="wpslt-floating-contact" class="wpslt-floating-contact <?php echo esc_attr($classes_str); ?>" role="navigation"
            aria-label="<?php echo esc_attr($custom_aria_label); ?>"
            data-tooltip-position="<?php echo esc_attr($tooltip_position); ?>"
            data-default-state="<?php echo esc_attr($default_state); ?>"
            data-remember-state="<?php echo !empty($style['remember_state']) ? 'true' : 'false'; ?>"
            data-enable-keyboard="<?php echo $enable_keyboard ? 'true' : 'false'; ?>"
            data-high-contrast="<?php echo $high_contrast ? 'true' : 'false'; ?>">
            <!-- Main Toggle Button -->
            <?php
            if ($show_toggle_button):
                $main_tooltip = $style['close_tooltip_text'] ?? __('Contact Us', 'wp-slatan-theme');
                ?>
                <button type="button" class="wpslt-fc-button <?php echo esc_attr($animation_class); ?>"
                    aria-label="<?php echo esc_attr($main_tooltip); ?>" aria-expanded="false" <?php if ($enable_keyboard): ?>tabindex="0" <?php endif; ?>             <?php if (!empty($tooltip['enabled'])): ?>
                        data-tooltip="<?php echo esc_attr($main_tooltip); ?>" <?php endif; ?>>
                    <span class="wpslt-fc-icon-open">
                        <?php if (!empty($style['open_custom_icon'])): ?>
                            <img src="<?php echo esc_url($style['open_custom_icon']); ?>" alt="" class="wpslt-fc-custom-icon">
                        <?php else: ?>
                            <i class="<?php echo esc_attr($style['open_icon'] ?? 'fas fa-comment-dots'); ?>"></i>
                        <?php endif; ?>
                    </span>
                    <span class="wpslt-fc-icon-close">
                        <?php if (!empty($style['close_custom_icon'])): ?>
                            <img src="<?php echo esc_url($style['close_custom_icon']); ?>" alt="" class="wpslt-fc-custom-icon">
                        <?php else: ?>
                            <i class="<?php echo esc_attr($style['close_icon'] ?? 'fas fa-times'); ?>"></i>
                        <?php endif; ?>
                    </span>
                </button>
            <?php endif; ?>

            <!-- Contact Items -->
            <div class="wpslt-fc-contacts">
                <?php foreach ($contacts as $contact):
                    $url = $this->get_contact_url($contact, $preset_types);
                    $tooltip_text = !empty($contact['tooltip']) ? $contact['tooltip'] : $contact['label'];
                    ?>
                    <a href="<?php echo esc_url($url); ?>" class="wpslt-fc-contact"
                        style="background-color: <?php echo esc_attr($contact['color']); ?>; color: <?php echo esc_attr($contact['icon_color'] ?? '#ffffff'); ?>;"
                        target="_blank" rel="noopener noreferrer" <?php if (!empty($tooltip['enabled'])): ?> data-tooltip="
            <?php echo esc_attr($tooltip_text); ?>" <?php endif; ?> aria-label="
            <?php echo esc_attr($contact['label']); ?>">
                        <?php if (!empty($contact['custom_icon'])): ?>
                            <img src="<?php echo esc_url($contact['custom_icon']); ?>" alt="<?php echo esc_attr($contact['label']); ?>"
                                class="wpslt-fc-custom-icon">
                        <?php else: ?>
                            <i class="<?php echo esc_attr($contact['icon']); ?>"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get contact URL
     */
    private function get_contact_url($contact, $preset_types)
    {
        $type = $contact['type'];
        $value = $contact['value'];

        // If custom or already a full URL
        if ($type === 'custom' || filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Get prefix from preset
        $prefix = isset($preset_types[$type]['url_prefix']) ? $preset_types[$type]['url_prefix'] : '';

        // Handle special cases
        switch ($type) {
            case 'phone':
                // Remove non-numeric characters except +
                $value = preg_replace('/[^0-9+]/', '', $value);
                break;
            case 'whatsapp':
                // Remove non-numeric characters
                $value = preg_replace('/[^0-9]/', '', $value);
                break;
            case 'line':
                // Handle @id format
                $value = ltrim($value, '@');
                break;
        }

        return $prefix . $value;
    }
}

// Initialize
WPSLT_Contact_Output::get_instance();

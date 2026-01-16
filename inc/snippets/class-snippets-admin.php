<?php
/**
 * Snippets Admin Handler
 * 
 * Handles the admin interface for managing code snippets.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Snippets_Admin
 */
class WPSLT_Snippets_Admin
{

    /**
     * Database instance
     *
     * @var WPSLT_Snippets_DB
     */
    private $db;

    /**
     * Admin page hook
     *
     * @var string
     */
    private $page_hook;

    /**
     * Singleton instance
     *
     * @var WPSLT_Snippets_Admin
     */
    private static $instance = null;

    /**
     * Cached counts for all scopes (memory cache)
     *
     * @var array|null
     */
    private $all_counts = null;

    /**
     * Get singleton instance
     *
     * @return WPSLT_Snippets_Admin
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
        $this->db = WPSLT_Snippets_DB::get_instance();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('admin_menu', array($this, 'add_menu'), 20);
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_notices', array($this, 'display_security_notice'));
        add_action('wp_ajax_wpslt_save_snippet', array($this, 'ajax_save_snippet'));
        add_action('wp_ajax_wpslt_check_conflict_loopback', array($this, 'ajax_handle_loopback_check'));
    }

    /**
     * Get tab name from scope
     *
     * @param string $scope Snippet scope.
     * @return string Tab name (css, js, php).
     */
    private function get_tab_from_scope($scope)
    {
        if (strpos($scope, '-css') !== false) {
            return 'css';
        }
        if (strpos($scope, 'php-') === 0) {
            return 'php';
        }
        if (strpos($scope, '-js') !== false) {
            return 'js';
        }
        return 'css';
    }

    /**
     * Add submenu page
     */
    public function add_menu()
    {
        $this->page_hook = add_submenu_page(
            'wpslt-settings',
            __('Code Snippets', 'wp-slatan-theme'),
            __('Code Snippets', 'wp-slatan-theme'),
            'manage_options',
            'wpslt-snippets',
            array($this, 'render_page')
        );
    }

    /**
     * Display security notice for PHP snippets.
     */
    public function display_security_notice()
    {
        // Only show on snippets page
        if (!isset($_GET['page']) || $_GET['page'] !== 'wpslt-snippets') {
            return;
        }

        // Only show on PHP tab
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'css';
        if ($current_tab !== 'php') {
            return;
        }

        // Only show when adding or editing PHP snippets
        $current_action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
        if ($current_action !== 'add' && $current_action !== 'edit') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('⚠️ Security Warning:', 'wp-slatan-theme'); ?></strong>
                <?php esc_html_e('PHP snippets execute code directly on your server. Only add code from trusted sources. Malicious code can compromise your website security.', 'wp-slatan-theme'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets($hook)
    {
        if ($hook !== $this->page_hook) {
            return;
        }

        // CodeMirror from WordPress core with linting support
        $cm_settings = array(
            'lineNumbers' => true,
            'lineWrapping' => true,
            'tabSize' => 2,
            'indentUnit' => 2,
            'mode' => 'css',
            'gutters' => array('CodeMirror-lint-markers'),
            'lint' => true,
        );

        wp_enqueue_code_editor(array('type' => 'text/css'));
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');

        // CodeMirror linting addons
        $cm_base = includes_url('js/codemirror/');

        // Lint CSS
        wp_enqueue_style('codemirror-lint', $cm_base . 'addon/lint/lint.css', array('wp-codemirror'), WPSLT_VERSION);
        wp_enqueue_script('codemirror-lint', $cm_base . 'addon/lint/lint.js', array('wp-codemirror'), WPSLT_VERSION, true);
        wp_enqueue_script('codemirror-css-lint', $cm_base . 'addon/lint/css-lint.js', array('codemirror-lint'), WPSLT_VERSION, true);

        // CSSLint library (external CDN - WordPress doesn't include this)
        wp_enqueue_script('csslint', 'https://cdnjs.cloudflare.com/ajax/libs/csslint/1.0.5/csslint.min.js', array(), '1.0.5', true);

        // JSHint library (external CDN - WordPress doesn't include this)
        wp_enqueue_script('jshint', 'https://cdnjs.cloudflare.com/ajax/libs/jshint/2.13.6/jshint.min.js', array(), '2.13.6', true);
        wp_enqueue_script('codemirror-js-lint', $cm_base . 'addon/lint/javascript-lint.js', array('codemirror-lint', 'jshint'), WPSLT_VERSION, true);

        // Base admin styles (includes toggle switches)
        wp_enqueue_style(
            'wpslt-admin-base',
            WPSLT_URI . '/assets/admin/css/base.css',
            array(),
            WPSLT_VERSION
        );

        // Custom admin styles
        wp_enqueue_style(
            'wpslt-snippets-admin',
            WPSLT_URI . '/assets/admin/css/snippets.css',
            array('wpslt-admin-base', 'codemirror-lint'),
            WPSLT_VERSION
        );

        // Custom admin script
        wp_enqueue_script(
            'wpslt-snippets-admin',
            WPSLT_URI . '/assets/admin/js/snippets.js',
            array('jquery', 'wp-theme-plugin-editor', 'codemirror-lint', 'codemirror-css-lint', 'codemirror-js-lint', 'csslint', 'jshint'),
            WPSLT_VERSION,
            true
        );

        wp_localize_script('wpslt-snippets-admin', 'wpsltSnippets', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpslt_snippets'),
            'i18n' => array(
                'deleteConfirm' => __('Are you sure you want to delete this snippet?', 'wp-slatan-theme'),
            ),
        ));
    }

    /**
     * Handle form actions
     */
    public function handle_actions()
    {
        if (!isset($_REQUEST['page']) || 'wpslt-snippets' !== $_REQUEST['page']) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Create table on first visit
        if (!$this->db->table_exists()) {
            $this->db->create_table();
        }

        // Handle save
        if (isset($_POST['wpslt_snippet_save'])) {
            $this->handle_save();
        }

        // Handle delete
        if (isset($_GET['action']) && 'delete' === $_GET['action'] && isset($_GET['id'])) {
            $this->handle_delete();
        }

        // Handle activate
        if (isset($_GET['action']) && 'activate' === $_GET['action'] && isset($_GET['id'])) {
            // DEBUG
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('=== ACTIVATE ACTION TRIGGERED ===');
                error_log('Action: ' . $_GET['action']);
                error_log('ID: ' . $_GET['id']);
            }
            $this->handle_activate();
        }

        // Handle deactivate
        if (isset($_GET['action']) && 'deactivate' === $_GET['action'] && isset($_GET['id'])) {
            $this->handle_deactivate();
        }

        // Handle bulk actions
        if (isset($_POST['wpslt_bulk_action']) && isset($_POST['snippet_ids'])) {
            $this->handle_bulk_action();
        }
    }

    /**
     * Handle bulk actions
     */
    private function handle_bulk_action()
    {
        $nonce = isset($_POST['wpslt_bulk_nonce']) ? $_POST['wpslt_bulk_nonce'] : '';
        if (!wp_verify_nonce($nonce, 'wpslt_bulk_action')) {
            wp_die(esc_html__('Security check failed.', 'wp-slatan-theme'));
        }

        $action = isset($_POST['bulk_action']) ? sanitize_key($_POST['bulk_action']) : '';
        $ids = array_map('absint', (array) $_POST['snippet_ids']);
        $ids = array_filter($ids); // Remove zeros

        if (empty($ids) || empty($action)) {
            $tab = isset($_POST['current_tab']) ? sanitize_key($_POST['current_tab']) : 'css';
            wp_safe_redirect(add_query_arg(array(
                'page' => 'wpslt-snippets',
                'tab' => $tab,
                'message' => 'no_selection',
            ), admin_url('admin.php')));
            exit;
        }

        $count = 0;
        $errors = array(); // Track activation errors
        foreach ($ids as $id) {
            switch ($action) {
                case 'delete':
                    if ($this->db->delete_snippet($id)) {
                        $count++;
                    }
                    break;
                case 'activate':
                    // Check for collisions before activating (for PHP snippets)
                    $snippet = $this->db->get_snippet($id);
                    $can_activate = true;

                    if ($snippet && strpos($snippet['scope'], 'php-') === 0) {
                        $collisions = WPSLT_Snippets_Validator::check_collisions($snippet['code'], $id);

                        if (!empty($collisions)) {
                            // Filter to get only ACTIVE collisions
                            $active_collisions = array_filter($collisions, function ($collision) {
                                return isset($collision['active']) && $collision['active'] === true;
                            });

                            if (!empty($active_collisions)) {
                                $can_activate = false;
                                // Store error for this snippet
                                $errors[] = sprintf(
                                    /* translators: %s: snippet name */
                                    __('Snippet "%s" was not activated due to conflicts.', 'wp-slatan-theme'),
                                    $snippet['name']
                                );
                            }
                        }
                    }

                    if ($can_activate && $this->db->activate_snippet($id)) {
                        $count++;
                    }
                    break;
                case 'deactivate':
                    if ($this->db->deactivate_snippet($id)) {
                        $count++;
                    }
                    break;
            }
        }

        $message = $action . '_bulk';
        $tab = isset($_POST['current_tab']) ? sanitize_key($_POST['current_tab']) : 'css';

        $redirect_args = array(
            'page' => 'wpslt-snippets',
            'tab' => $tab,
            'message' => $message,
            'count' => $count,
        );

        // Add errors if any snippets failed to activate
        if (!empty($errors)) {
            $redirect_args['bulk_errors'] = urlencode(base64_encode(json_encode($errors)));
        }

        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    /**
     * Handle save action
     */
    private function handle_save()
    {
        if (!wp_verify_nonce($_POST['wpslt_snippet_nonce'] ?? '', 'wpslt_save_snippet')) {
            wp_die(esc_html__('Security check failed.', 'wp-slatan-theme'));
        }

        $data = array(
            'id' => isset($_POST['snippet_id']) ? absint($_POST['snippet_id']) : 0,
            'name' => isset($_POST['snippet_name']) ? sanitize_text_field($_POST['snippet_name']) : '',
            'description' => isset($_POST['snippet_description']) ? sanitize_textarea_field($_POST['snippet_description']) : '',
            'code' => isset($_POST['snippet_code']) ? wp_unslash($_POST['snippet_code']) : '',
            'scope' => isset($_POST['snippet_scope']) ? sanitize_key($_POST['snippet_scope']) : 'site-css',
            'priority' => isset($_POST['snippet_priority']) ? absint($_POST['snippet_priority']) : 10,
            'active' => isset($_POST['snippet_active']) ? 1 : 0,
        );

        // Determine tab from scope
        $tab = $this->get_tab_from_scope($data['scope']);

        // For PHP snippets, check for collisions (for warnings only, don't block save)
        // Collisions will block ACTIVATION, not saving (allows backup/versioning)
        $collision_warnings = array();
        if (strpos($data['scope'], 'php-') === 0) {
            // DEBUG
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('=== SAVE: Collision Check ===');
                error_log('Snippet ID: ' . $data['id']);
                error_log('Scope: ' . $data['scope']);
                error_log('Code length: ' . strlen($data['code']));
            }

            $collisions = WPSLT_Snippets_Validator::check_collisions($data['code'], $data['id']);

            // DEBUG
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Collisions found: ' . count($collisions));
                if (!empty($collisions)) {
                    error_log('Collision details: ' . print_r($collisions, true));
                }
            }

            // Store collisions as warnings, but don't block save
            if (!empty($collisions)) {
                foreach ($collisions as $collision) {
                    $collision_warnings[] = isset($collision['message']) ? $collision['message'] : __('Unknown conflict', 'wp-slatan-theme');
                }
            }
        }

        $result = $this->db->save_snippet($data);

        if ($result) {
            $redirect = add_query_arg(array(
                'page' => 'wpslt-snippets',
                'tab' => $tab,
                'message' => 'saved',
            ), admin_url('admin.php'));
        } else {
            $redirect = add_query_arg(array(
                'page' => 'wpslt-snippets',
                'tab' => $tab,
                'message' => 'error',
            ), admin_url('admin.php'));
        }

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Perform a loopback request to check for conflicts in a live environment.
     *
     * @param string $code snippet code
     * @param int $snippet_id snippet ID
     * @return array|WP_Error
     */
    private static function check_conflict_loopback($code, $snippet_id)
    {
        $cookies = array();
        foreach ($_COOKIE as $name => $value) {
            $cookies[] = new WP_Http_Cookie(array('name' => $name, 'value' => $value));
        }

        $args = array(
            'timeout' => 5,
            'blocking' => true,
            'cookies' => $cookies,
            'body' => array(
                'action' => 'wpslt_check_conflict_loopback',
                'snippet_code' => $code,
                'snippet_id' => $snippet_id,
                'nonce' => wp_create_nonce('wpslt_loopback_check'),
            ),
        );

        // Use admin-ajax.php for the check
        $url = admin_url('admin-ajax.php');
        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['success'])) {
            // Invalid JSON response, arguably a failure or just empty
            return array('collisions' => array());
        }

        if (!$data['success'] && isset($data['data']['collisions'])) {
            return $data['data'];
        }

        return array('collisions' => array());
    }

    /**
     * Handle Ajax loopback conflict check
     */
    public function ajax_handle_loopback_check()
    {
        // Verify nonce but don't die (return json)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpslt_loopback_check')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $code = isset($_POST['snippet_code']) ? wp_unslash($_POST['snippet_code']) : '';
        $id = isset($_POST['snippet_id']) ? absint($_POST['snippet_id']) : 0;

        if (empty($code)) {
            wp_send_json_success();
        }

        // Just check collisions using the standard Validator.
        // Since this Runs in a separate HTTP request, it sees the "Full" environment (potentially).
        // WARNING: admin-ajax.php might still be minimal.
        // Ideally we would want a Front-end request, but admin-ajax is safer for now.
        // If plugins don't load in admin-ajax, we still miss them.
        // BUT, if the snippet is being saved as 'active', usually plugins load in admin context too.

        $collisions = WPSLT_Snippets_Validator::check_collisions($code, $id);

        if (!empty($collisions)) {
            wp_send_json_error(array('collisions' => $collisions));
        }

        wp_send_json_success();
    }

    /**
     * Handle Ajax save snippet request
     */
    public function ajax_save_snippet()
    {
        // Check nonce
        check_ajax_referer('wpslt_save_snippet', 'wpslt_snippet_nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'wp-slatan-theme'),
            ));
        }

        // SECURITY: Rate limiting - prevent rapid-fire saves
        $user_id = get_current_user_id();
        $rate_key = 'wpslt_snippet_save_rate_' . $user_id;
        if (get_transient($rate_key)) {
            wp_send_json_error(array(
                'message' => __('⏱️ Please wait a moment before saving again.', 'wp-slatan-theme'),
            ));
        }
        // Set rate limit (5 seconds)
        set_transient($rate_key, 1, 5);

        $data = array(
            'id' => isset($_POST['snippet_id']) ? absint($_POST['snippet_id']) : 0,
            'name' => isset($_POST['snippet_name']) ? sanitize_text_field($_POST['snippet_name']) : '',
            'description' => isset($_POST['snippet_description']) ? sanitize_textarea_field($_POST['snippet_description']) : '',
            'code' => isset($_POST['snippet_code']) ? wp_unslash($_POST['snippet_code']) : '',
            'scope' => isset($_POST['snippet_scope']) ? sanitize_key($_POST['snippet_scope']) : 'site-css',
            'priority' => isset($_POST['snippet_priority']) ? absint($_POST['snippet_priority']) : 10,
            'active' => isset($_POST['snippet_active']) ? absint($_POST['snippet_active']) : 0,
        );

        // Validate required fields
        if (empty($data['name'])) {
            wp_send_json_error(array(
                'message' => __('Snippet name is required.', 'wp-slatan-theme'),
            ));
        }

        if (empty($data['code'])) {
            wp_send_json_error(array(
                'message' => __('Snippet code is required.', 'wp-slatan-theme'),
            ));
        }

        // CHECK: Duplicate snippet name
        $existing = $this->db->get_snippets();
        foreach ($existing as $existing_snippet) {
            // Skip current snippet when editing
            if ($data['id'] > 0 && absint($existing_snippet['id']) === $data['id']) {
                continue;
            }

            // Check if name already exists
            if (trim(strtolower($existing_snippet['name'])) === trim(strtolower($data['name']))) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        /* translators: %s: snippet name */
                        __('⛔ A snippet named "%s" already exists. Please use a different name.', 'wp-slatan-theme'),
                        $data['name']
                    ),
                ));
            }
        }

        $warnings = array();
        $collisions = array();

        // For PHP snippets, validate syntax and check for collisions
        if (strpos($data['scope'], 'php-') === 0) {
            // Validate syntax
            $validation = WPSLT_Snippets_Validator::validate_syntax($data['code']);
            if (!$validation['valid']) {
                $error_messages = array();
                foreach ($validation['errors'] as $error) {
                    $error_messages[] = isset($error['message']) ? $error['message'] : __('Unknown error', 'wp-slatan-theme');
                }
                wp_send_json_error(array(
                    'message' => __('PHP Syntax Error:', 'wp-slatan-theme') . ' ' . implode(' ', $error_messages),
                ));
            }

            // Check for dangerous functions (warnings)
            if (!empty($validation['warnings'])) {
                foreach ($validation['warnings'] as $warning) {
                    $warnings[] = isset($warning['message']) ? $warning['message'] : '';
                }
            }

            // Check for collisions - validator only returns ACTIVE collisions
            $collisions = WPSLT_Snippets_Validator::check_collisions($data['code'], $data['id']);
            if (!empty($collisions)) {
                if ($data['active']) {
                    // Block activation - these are all ACTIVE collisions (will crash site)
                    $collision_messages = array();
                    foreach ($collisions as $collision) {
                        $collision_messages[] = isset($collision['message']) ? $collision['message'] : '';
                    }
                    wp_send_json_error(array(
                        'message' => __('Cannot activate snippet due to name collisions:', 'wp-slatan-theme'),
                        'collisions' => $collision_messages,
                    ));
                } else {
                    // If just saving (not activating), add as warnings
                    foreach ($collisions as $collision) {
                        $warnings[] = isset($collision['message']) ? $collision['message'] : '';
                    }
                }
            }
        }

        // Save the snippet
        $result = $this->db->save_snippet($data);

        if ($result) {
            // SECURITY: Audit logging
            $log_action = (0 === $data['id']) ? 'created' : 'updated';
            $this->log_snippet_change($result, $log_action, $data);

            // Get the saved snippet data
            $snippet = $this->db->get_snippet($result);
            $tab = $this->get_tab_from_scope($data['scope']);

            // Determine redirect URL
            $redirect_url = null;

            // Only redirect if it was a new snippet (to initialize the edit view with ID)
            if (0 === $data['id']) {
                $redirect_url = add_query_arg(array(
                    'page' => 'wpslt-snippets',
                    'tab' => $tab,
                    'action' => 'edit',
                    'id' => $result,
                ), admin_url('admin.php'));
            }

            wp_send_json_success(array(
                'message' => __('Snippet saved successfully!', 'wp-slatan-theme'),
                'snippet' => $snippet,
                'tab' => $tab,
                'warnings' => $warnings,
                'redirect_url' => $redirect_url,
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save snippet. Please try again.', 'wp-slatan-theme'),
            ));
        }
    }

    /**
     * Handle delete action
     */
    private function handle_delete()
    {
        $id = absint($_GET['id']);

        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_snippet_' . $id)) {
            wp_die(esc_html__('Security check failed.', 'wp-slatan-theme'));
        }

        $this->db->delete_snippet($id);

        // Get tab from URL parameter
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'css';

        wp_safe_redirect(add_query_arg(array(
            'page' => 'wpslt-snippets',
            'tab' => $tab,
            'message' => 'deleted',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle activate action
     */
    private function handle_activate()
    {
        $id = absint($_GET['id']);

        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'activate_snippet_' . $id)) {
            wp_die(esc_html__('Security check failed.', 'wp-slatan-theme'));
        }

        // Get tab from URL parameter
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'css';

        // For PHP snippets, check for collisions before activating
        $snippet = $this->db->get_snippet($id);

        if ($snippet && strpos($snippet['scope'], 'php-') === 0) {
            $collisions = WPSLT_Snippets_Validator::check_collisions($snippet['code'], $id);

            if (!empty($collisions)) {
                // Filter to get only ACTIVE collisions (critical - will crash site)
                $active_collisions = array_filter($collisions, function ($collision) {
                    return isset($collision['active']) && $collision['active'] === true;
                });

                // Only BLOCK if there are collisions with ACTIVE snippets
                if (!empty($active_collisions)) {
                    // Encode collision errors in URL for multi-node compatibility
                    $collision_messages = array();
                    foreach ($active_collisions as $collision) {
                        $collision_messages[] = isset($collision['message']) ? $collision['message'] : __('Unknown conflict', 'wp-slatan-theme');
                    }

                    wp_safe_redirect(add_query_arg(array(
                        'page' => 'wpslt-snippets',
                        'tab' => $tab,
                        'message' => 'collision',
                        'collision_data' => urlencode(base64_encode(json_encode($collision_messages))),
                    ), admin_url('admin.php')));
                    exit;
                }

                // If only inactive collisions, show warning but allow activation
                // (The warning will be displayed from collision_data in URL)
            }
        }

        $this->db->activate_snippet($id);

        wp_safe_redirect(add_query_arg(array(
            'page' => 'wpslt-snippets',
            'tab' => $tab,
            'message' => 'activated',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Handle deactivate action
     */
    private function handle_deactivate()
    {
        $id = absint($_GET['id']);

        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'deactivate_snippet_' . $id)) {
            wp_die(esc_html__('Security check failed.', 'wp-slatan-theme'));
        }

        $this->db->deactivate_snippet($id);

        // Get tab from URL parameter
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'css';

        wp_safe_redirect(add_query_arg(array(
            'page' => 'wpslt-snippets',
            'tab' => $tab,
            'message' => 'deactivated',
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Render the admin page
     */
    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Determine view
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        echo '<div class="wrap wpslt-snippets-wrap">';

        // Show messages
        $this->show_messages();

        if ('add' === $action || 'edit' === $action) {
            $this->render_edit_form($id);
        } else {
            $this->render_list();
        }

        echo '</div>';
    }

    /**
     * Show admin messages
     */
    private function show_messages()
    {
        // Handle collision errors first (from URL parameters for multi-node compatibility)
        if (isset($_GET['message']) && 'collision' === $_GET['message']) {
            // Decode collision data from URL with security validation
            if (isset($_GET['collision_data'])) {
                // Sanitize input first
                $collision_data = sanitize_text_field(wp_unslash($_GET['collision_data']));
                $decoded_data = base64_decode($collision_data, true); // Strict mode

                // Validate base64 decode
                if ($decoded_data !== false) {
                    $collision_messages = json_decode($decoded_data, true);

                    // Validate JSON decode and array structure
                    if (is_array($collision_messages) && !empty($collision_messages)) {
                        echo '<div class="notice notice-error is-dismissible">';
                        echo '<p><strong>' . esc_html__('⛔ Cannot activate snippet due to code conflicts:', 'wp-slatan-theme') . '</strong></p>';
                        echo '<ul style="list-style: disc; margin-left: 20px;">';
                        foreach ($collision_messages as $message) {
                            // Extra sanitization for each message
                            if (is_string($message) && !empty($message)) {
                                echo '<li>' . esc_html(sanitize_text_field($message)) . '</li>';
                            }
                        }
                        echo '</ul>';
                        echo '<p><em>' . esc_html__('Please edit the snippet to rename conflicting functions/classes before activating.', 'wp-slatan-theme') . '</em></p>';
                        echo '</div>';
                        return;
                    }
                }
            }

            // Fallback if no collision data provided or validation failed
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . esc_html__('⛔ Cannot activate snippet due to code conflicts.', 'wp-slatan-theme') . '</strong></p>';
            echo '</div>';
            return;
        }

        if (!isset($_GET['message'])) {
            return;
        }

        $messages = array(
            'saved' => __('Snippet saved successfully.', 'wp-slatan-theme'),
            'deleted' => __('Snippet deleted.', 'wp-slatan-theme'),
            'activated' => __('Snippet activated.', 'wp-slatan-theme'),
            'deactivated' => __('Snippet deactivated.', 'wp-slatan-theme'),
            'error' => __('An error occurred.', 'wp-slatan-theme'),
            'collision' => __('Cannot activate snippet due to code conflicts.', 'wp-slatan-theme'),
            'activate_bulk' => __('Snippets activated.', 'wp-slatan-theme'),
            'deactivate_bulk' => __('Snippets deactivated.', 'wp-slatan-theme'),
            'delete_bulk' => __('Snippets deleted.', 'wp-slatan-theme'),
            'no_selection' => __('No snippets selected.', 'wp-slatan-theme'),
        );

        $message_key = sanitize_key($_GET['message']);
        if (isset($messages[$message_key])) {
            $error_types = array('error', 'collision', 'no_selection');
            $class = in_array($message_key, $error_types, true) ? 'notice-error' : 'notice-success';

            // Add count for bulk actions
            $message_text = $messages[$message_key];
            if (isset($_GET['count']) && strpos($message_key, '_bulk') !== false) {
                $count = absint($_GET['count']);
                $message_text = sprintf(
                    /* translators: %d: number of snippets */
                    _n('%d snippet processed.', '%d snippets processed.', $count, 'wp-slatan-theme'),
                    $count
                );
            }

            printf(
                '<div class="notice %s is-dismissible"><p>%s</p></div>',
                esc_attr($class),
                esc_html($message_text)
            );
        }
    }

    /**
     * Render list view with tabs
     */
    private function render_list()
    {
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'css';
        $scopes = WPSLT_Snippets_DB::get_scopes();

        // Pagination parameters
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
        $per_page = in_array($per_page, array(10, 25, 50, 100), true) ? $per_page : 20;
        $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        // Get total count and snippets filtered by tab
        if ('css' === $current_tab) {
            $total_items = $this->get_css_count();
            $css_snippets = $this->db->get_snippets(array('scope' => 'site-css'));
            $admin_css_snippets = $this->db->get_snippets(array('scope' => 'admin-css'));
            $all_snippets = array_merge($css_snippets, $admin_css_snippets);
        } elseif ('js' === $current_tab) {
            $total_items = $this->get_js_count();
            $head_js_snippets = $this->db->get_snippets(array('scope' => 'site-head-js'));
            $footer_js_snippets = $this->db->get_snippets(array('scope' => 'site-footer-js'));
            $all_snippets = array_merge($head_js_snippets, $footer_js_snippets);
        } else {
            // PHP tab
            $total_items = $this->get_php_count();
            $php_everywhere = $this->db->get_snippets(array('scope' => 'php-everywhere'));
            $php_frontend = $this->db->get_snippets(array('scope' => 'php-frontend'));
            $php_admin = $this->db->get_snippets(array('scope' => 'php-admin'));
            $all_snippets = array_merge($php_everywhere, $php_frontend, $php_admin);
        }

        // Apply pagination manually since we merge multiple scopes
        $snippets = array_slice($all_snippets, $offset, $per_page);
        $total_pages = $per_page > 0 ? (int) ceil($total_items / $per_page) : 1;
        ?>
        <h1 class="wp-heading-inline wpslt-page-heading">
            <span class="dashicons dashicons-editor-code"></span>
            <?php esc_html_e('Code Snippets', 'wp-slatan-theme'); ?>
        </h1>
        <p class="wpslt-page-description">
            <?php esc_html_e('Add custom code snippets to your website', 'wp-slatan-theme'); ?>
        </p>
        <hr class="wp-header-end">

        <!-- Safe Mode Notice -->
        <?php $this->maybe_show_safe_mode_notice(); ?>

        <!-- Tabs Navigation -->
        <div class="wpslt-snippets-tabs">
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&tab=css')); ?>"
                class="wpslt-snippets-tab <?php echo 'css' === $current_tab ? 'active' : ''; ?>">
                <?php esc_html_e('CSS', 'wp-slatan-theme'); ?>
                <span class="count">(<?php echo esc_html($this->get_css_count()); ?>)</span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&tab=js')); ?>"
                class="wpslt-snippets-tab <?php echo 'js' === $current_tab ? 'active' : ''; ?>">
                <?php esc_html_e('JavaScript', 'wp-slatan-theme'); ?>
                <span class="count">(<?php echo esc_html($this->get_js_count()); ?>)</span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&tab=php')); ?>"
                class="wpslt-snippets-tab <?php echo 'php' === $current_tab ? 'active' : ''; ?>">
                <?php esc_html_e('PHP', 'wp-slatan-theme'); ?>
                <span class="count">(<?php echo esc_html($this->get_php_count()); ?>)</span>
            </a>
        </div>

        <!-- Tab Content -->
        <div class="wpslt-snippets-tab-content">
            <!-- Toolbar with title and Add Button -->
            <div class="wpslt-snippets-toolbar">
                <h2 class="wpslt-snippets-title">
                    <?php
                    if ('css' === $current_tab) {
                        esc_html_e('CSS Snippets', 'wp-slatan-theme');
                    } elseif ('js' === $current_tab) {
                        esc_html_e('JavaScript Snippets', 'wp-slatan-theme');
                    } else {
                        esc_html_e('PHP Snippets', 'wp-slatan-theme');
                    }
                    ?>
                </h2>
                <?php if ('css' === $current_tab): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&action=add&tab=css&scope=site-css')); ?>"
                        class="button button-primary">
                        <?php esc_html_e('+ Add CSS Snippet', 'wp-slatan-theme'); ?>
                    </a>
                <?php elseif ('js' === $current_tab): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&action=add&tab=js&scope=site-footer-js')); ?>"
                        class="button button-primary">
                        <?php esc_html_e('+ Add JavaScript Snippet', 'wp-slatan-theme'); ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&action=add&tab=php&scope=php-everywhere')); ?>"
                        class="button button-primary">
                        <?php esc_html_e('+ Add PHP Snippet', 'wp-slatan-theme'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Bulk Actions Form -->
            <form method="post" id="wpslt-bulk-form">
                <?php wp_nonce_field('wpslt_bulk_action', 'wpslt_bulk_nonce'); ?>
                <input type="hidden" name="wpslt_bulk_action" value="1">
                <input type="hidden" name="current_tab" value="<?php echo esc_attr($current_tab); ?>">

                <!-- Bulk Actions Bar -->
                <div class="wpslt-bulk-actions">
                    <div class="wpslt-bulk-left">
                        <select name="bulk_action" id="bulk-action-selector">
                            <option value=""><?php esc_html_e('Bulk Actions', 'wp-slatan-theme'); ?></option>
                            <option value="activate"><?php esc_html_e('Activate', 'wp-slatan-theme'); ?></option>
                            <option value="deactivate"><?php esc_html_e('Deactivate', 'wp-slatan-theme'); ?></option>
                            <option value="delete"><?php esc_html_e('Delete', 'wp-slatan-theme'); ?></option>
                        </select>
                        <button type="submit" class="button action"><?php esc_html_e('Apply', 'wp-slatan-theme'); ?></button>
                        <span class="wpslt-selected-count" style="display:none;">
                            <?php esc_html_e('Selected:', 'wp-slatan-theme'); ?> <strong>0</strong>
                        </span>
                    </div>
                    <div class="wpslt-bulk-right">
                        <label for="per-page-selector"><?php esc_html_e('Per page:', 'wp-slatan-theme'); ?></label>
                        <select id="per-page-selector" onchange="window.location.href=this.value;">
                            <?php
                            $per_page_options = array(10, 25, 50, 100);
                            foreach ($per_page_options as $option):
                                $url = add_query_arg(array(
                                    'page' => 'wpslt-snippets',
                                    'tab' => $current_tab,
                                    'per_page' => $option,
                                    'paged' => 1,
                                ), admin_url('admin.php'));
                                ?>
                                <option value="<?php echo esc_url($url); ?>" <?php selected($per_page, $option); ?>>
                                    <?php echo esc_html($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" id="cb-select-all">
                            </th>
                            <th class="column-name">
                                <?php esc_html_e('Name', 'wp-slatan-theme'); ?>
                            </th>
                            <th class="column-scope">
                                <?php esc_html_e('Location', 'wp-slatan-theme'); ?>
                            </th>
                            <th class="column-priority">
                                <?php esc_html_e('Priority', 'wp-slatan-theme'); ?>
                            </th>
                            <th class="column-status">
                                <?php esc_html_e('Status', 'wp-slatan-theme'); ?>
                            </th>
                            <th class="column-modified">
                                <?php esc_html_e('Modified', 'wp-slatan-theme'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($snippets)): ?>
                            <tr>
                                <td colspan="6">
                                    <?php
                                    if ('css' === $current_tab) {
                                        esc_html_e('No CSS snippets found.', 'wp-slatan-theme');
                                    } elseif ('php' === $current_tab) {
                                        esc_html_e('No PHP snippets found.', 'wp-slatan-theme');
                                    } else {
                                        esc_html_e('No JavaScript snippets found.', 'wp-slatan-theme');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($snippets as $snippet): ?>
                                <tr class="<?php echo $snippet['active'] ? 'snippet-active' : 'snippet-inactive'; ?>">
                                    <td class="check-column">
                                        <input type="checkbox" name="snippet_ids[]" value="<?php echo esc_attr($snippet['id']); ?>"
                                            class="snippet-checkbox">
                                    </td>
                                    <td class="column-name">
                                        <strong>
                                            <a
                                                href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&action=edit&id=' . $snippet['id'] . '&tab=' . $current_tab)); ?>">
                                                <?php echo esc_html($snippet['name'] ?: __('(no name)', 'wp-slatan-theme')); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span class="edit">
                                                <a
                                                    href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&action=edit&id=' . $snippet['id'] . '&tab=' . $current_tab)); ?>">
                                                    <?php esc_html_e('Edit', 'wp-slatan-theme'); ?>
                                                </a> |
                                            </span>
                                            <?php if ($snippet['active']): ?>
                                                <span class="deactivate">
                                                    <a
                                                        href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpslt-snippets&action=deactivate&id=' . $snippet['id'] . '&tab=' . $current_tab), 'deactivate_snippet_' . $snippet['id'])); ?>">
                                                        <?php esc_html_e('Deactivate', 'wp-slatan-theme'); ?>
                                                    </a> |
                                                </span>
                                            <?php else: ?>
                                                <span class="activate">
                                                    <a
                                                        href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpslt-snippets&action=activate&id=' . $snippet['id'] . '&tab=' . $current_tab), 'activate_snippet_' . $snippet['id'])); ?>">
                                                        <?php esc_html_e('Activate', 'wp-slatan-theme'); ?>
                                                    </a> |
                                                </span>
                                            <?php endif; ?>
                                            <span class="delete">
                                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wpslt-snippets&action=delete&id=' . $snippet['id'] . '&tab=' . $current_tab), 'delete_snippet_' . $snippet['id'])); ?>"
                                                    class="submitdelete"
                                                    onclick="return confirm('<?php esc_attr_e('Are you sure?', 'wp-slatan-theme'); ?>');">
                                                    <?php esc_html_e('Delete', 'wp-slatan-theme'); ?>
                                                </a>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="column-scope">
                                        <span class="snippet-scope snippet-scope-<?php echo esc_attr($snippet['scope']); ?>">
                                            <?php echo esc_html($scopes[$snippet['scope']] ?? $snippet['scope']); ?>
                                        </span>
                                    </td>
                                    <td class="column-priority">
                                        <?php echo esc_html($snippet['priority']); ?>
                                    </td>
                                    <td class="column-status">
                                        <?php if ($snippet['active']): ?>
                                            <span class="snippet-status-active">✓ <?php esc_html_e('Active', 'wp-slatan-theme'); ?></span>
                                        <?php else: ?>
                                            <span class="snippet-status-inactive">○
                                                <?php esc_html_e('Inactive', 'wp-slatan-theme'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="column-modified">
                                        <?php echo esc_html(human_time_diff(strtotime($snippet['modified']), current_time('timestamp')) . ' ' . __('ago', 'wp-slatan-theme')); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <!-- Pagination Controls -->
            <?php if ($total_pages > 1 || $total_items > 0): ?>
                <div class="wpslt-pagination">
                    <div class="wpslt-pagination-info">
                        <?php
                        $start_item = $total_items > 0 ? $offset + 1 : 0;
                        $end_item = min($offset + $per_page, $total_items);
                        printf(
                            /* translators: 1: first item number 2: last item number 3: total items */
                            esc_html__('Showing %1$d - %2$d of %3$d snippets', 'wp-slatan-theme'),
                            $start_item,
                            $end_item,
                            $total_items
                        );
                        ?>
                    </div>

                    <div class="wpslt-pagination-controls">

                        <!-- Page Navigation -->
                        <?php if ($total_pages > 1): ?>
                            <?php
                            $prev_url = add_query_arg(array(
                                'page' => 'wpslt-snippets',
                                'tab' => $current_tab,
                                'per_page' => $per_page,
                                'paged' => max(1, $current_page - 1),
                            ), admin_url('admin.php'));
                            $next_url = add_query_arg(array(
                                'page' => 'wpslt-snippets',
                                'tab' => $current_tab,
                                'per_page' => $per_page,
                                'paged' => min($total_pages, $current_page + 1),
                            ), admin_url('admin.php'));
                            ?>
                            <a href="<?php echo esc_url($prev_url); ?>"
                                class="button <?php echo $current_page <= 1 ? 'disabled' : ''; ?>" <?php echo $current_page <= 1 ? 'aria-disabled="true"' : ''; ?>>
                                &laquo; <?php esc_html_e('Previous', 'wp-slatan-theme'); ?>
                            </a>
                            <span class="wpslt-page-info">
                                <?php
                                printf(
                                    /* translators: 1: current page 2: total pages */
                                    esc_html__('Page %1$d of %2$d', 'wp-slatan-theme'),
                                    $current_page,
                                    $total_pages
                                );
                                ?>
                            </span>
                            <a href="<?php echo esc_url($next_url); ?>"
                                class="button <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>" <?php echo $current_page >= $total_pages ? 'aria-disabled="true"' : ''; ?>>
                                <?php esc_html_e('Next', 'wp-slatan-theme'); ?> &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get cached counts for all scopes (memory and transient cached)
     *
     * @return array Associative array of scope => count
     */
    private function get_cached_counts()
    {
        if (null === $this->all_counts) {
            $this->all_counts = $this->db->get_all_counts();
        }
        return $this->all_counts;
    }

    /**
     * Get CSS snippets count (optimized with cache)
     */
    private function get_css_count()
    {
        $counts = $this->get_cached_counts();
        return ($counts['site-css'] ?? 0) + ($counts['admin-css'] ?? 0);
    }

    /**
     * Get JS snippets count (optimized with cache)
     */
    private function get_js_count()
    {
        $counts = $this->get_cached_counts();
        return ($counts['site-head-js'] ?? 0) + ($counts['site-footer-js'] ?? 0);
    }

    /**
     * Get PHP snippets count (optimized with cache)
     */
    private function get_php_count()
    {
        $counts = $this->get_cached_counts();
        return ($counts['php-everywhere'] ?? 0) + ($counts['php-frontend'] ?? 0) + ($counts['php-admin'] ?? 0);
    }

    /**
     * Show safe mode notice if active
     */
    private function maybe_show_safe_mode_notice()
    {
        if (!class_exists('WPSLT_Snippets_Executor')) {
            return;
        }

        $executor = WPSLT_Snippets_Executor::get_instance();

        if ($executor->is_safe_mode()) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Safe Mode Active', 'wp-slatan-theme'); ?></strong> -
                    <?php esc_html_e('PHP snippets are disabled. Fix any problematic snippets before disabling safe mode.', 'wp-slatan-theme'); ?>
                </p>
            </div>
            <?php
        }

        // Show any snippet execution errors
        $errors = $executor->get_errors();
        if (!empty($errors)) {
            foreach ($errors as $id => $error) {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p>
                        <strong><?php printf(esc_html__('Snippet "%s" was deactivated due to an error:', 'wp-slatan-theme'), esc_html($error['name'])); ?></strong>
                        <?php echo esc_html($error['message']); ?>
                    </p>
                </div>
                <?php
            }
            // Clear errors after showing
            $executor->clear_errors();
        }
    }

    /**
     * Render edit form
     *
     * @param int $id Snippet ID (0 for new).
     */
    private function render_edit_form($id = 0)
    {
        $snippet = $id > 0 ? $this->db->get_snippet($id) : null;

        // Determine current tab from URL or snippet scope
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'css';

        // Get scopes filtered by current tab type
        $scopes = WPSLT_Snippets_DB::get_scopes_by_type($current_tab);

        // Get default scope from URL parameter
        $default_scope = isset($_GET['scope']) ? sanitize_key($_GET['scope']) : '';

        // If no default scope or invalid, use first scope of current type
        if (empty($default_scope) || !array_key_exists($default_scope, $scopes)) {
            $scope_keys = array_keys($scopes);
            $default_scope = !empty($scope_keys) ? $scope_keys[0] : 'site-css';
        }

        $defaults = array(
            'id' => 0,
            'name' => '',
            'description' => '',
            'code' => '',
            'scope' => $default_scope,
            'priority' => 10,
            'active' => 0,
        );

        $snippet = wp_parse_args($snippet ?? array(), $defaults);
        $is_new = 0 === $snippet['id'];

        // Determine code mode based on scope
        if (strpos($snippet['scope'], '-css') !== false) {
            $mode = 'css';
        } elseif (strpos($snippet['scope'], 'php-') !== false) {
            $mode = 'php';
        } else {
            $mode = 'javascript';
        }
        ?>
        <h1>
            <?php echo $is_new ? esc_html__('Add New Snippet', 'wp-slatan-theme') : esc_html__('Edit Snippet', 'wp-slatan-theme'); ?>
        </h1>

        <form method="post" action="" id="wpslt-snippet-form">
            <?php wp_nonce_field('wpslt_save_snippet', 'wpslt_snippet_nonce'); ?>
            <input type="hidden" name="snippet_id" value="<?php echo esc_attr($snippet['id']); ?>">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="snippet_name">
                            <?php esc_html_e('Name', 'wp-slatan-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" name="snippet_name" id="snippet_name" class="regular-text"
                            value="<?php echo esc_attr($snippet['name']); ?>"
                            placeholder="<?php esc_attr_e('Enter snippet name...', 'wp-slatan-theme'); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snippet_description">
                            <?php esc_html_e('Description', 'wp-slatan-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea name="snippet_description" id="snippet_description" rows="2" class="large-text"
                            placeholder="<?php esc_attr_e('Optional description for this snippet...', 'wp-slatan-theme'); ?>"><?php echo esc_textarea($snippet['description']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snippet_scope">
                            <?php esc_html_e('Type', 'wp-slatan-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <select name="snippet_scope" id="snippet_scope">
                            <?php foreach ($scopes as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($snippet['scope'], $value); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snippet_priority">
                            <?php esc_html_e('Priority', 'wp-slatan-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="snippet_priority" id="snippet_priority" class="small-text"
                            value="<?php echo esc_attr($snippet['priority']); ?>" min="1" max="100" placeholder="10">
                        <p class="description">
                            <?php esc_html_e('Lower numbers execute first.', 'wp-slatan-theme'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snippet_active">
                            <?php esc_html_e('Status', 'wp-slatan-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <label class="wpslt-toggle">
                            <input type="checkbox" name="snippet_active" id="snippet_active" value="1" <?php checked($snippet['active'], 1); ?>>
                            <span class="wpslt-toggle-slider"></span>
                            <span class="wpslt-toggle-label"><?php esc_html_e('Active', 'wp-slatan-theme'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="snippet_code">
                            <?php esc_html_e('Code', 'wp-slatan-theme'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea name="snippet_code" id="snippet_code" rows="15" class="large-text code"
                            data-mode="<?php echo esc_attr($mode); ?>"
                            placeholder="<?php esc_attr_e('Enter your code here...', 'wp-slatan-theme'); ?>"><?php echo esc_textarea($snippet['code']); ?></textarea>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="wpslt_snippet_save" class="button button-primary"
                    value="<?php echo $is_new ? esc_attr__('Add Snippet', 'wp-slatan-theme') : esc_attr__('Update Snippet', 'wp-slatan-theme'); ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpslt-snippets&tab=' . esc_attr($current_tab))); ?>"
                    class="button">
                    <?php esc_html_e('Cancel', 'wp-slatan-theme'); ?>
                </a>
            </p>
        </form>
        <?php
    }

    /**
     * Log snippet changes for security audit trail
     *
     * @param int    $snippet_id Snippet ID
     * @param string $action     Action performed (created, updated, activated, deactivated, deleted)
     * @param array  $data       Snippet data
     */
    private function log_snippet_change($snippet_id, $action, $data)
    {
        $user = wp_get_current_user();
        $log_entry = sprintf(
            '[%s] User %s (#%d) %s snippet #%d "%s" (scope: %s)',
            current_time('Y-m-d H:i:s'),
            $user->user_login,
            $user->ID,
            $action,
            $snippet_id,
            isset($data['name']) ? $data['name'] : 'Unknown',
            isset($data['scope']) ? $data['scope'] : 'unknown'
        );

        // Log to error_log for server-level tracking
        error_log('WPSLT Snippet Audit: ' . $log_entry);

        // Store in option for admin review (keep last 100 entries)
        $audit_log = get_option('wpslt_snippet_audit_log', array());
        array_unshift($audit_log, array(
            'timestamp' => current_time('timestamp'),
            'user_id' => $user->ID,
            'user_login' => $user->user_login,
            'action' => $action,
            'snippet_id' => $snippet_id,
            'snippet_name' => isset($data['name']) ? $data['name'] : 'Unknown',
            'scope' => isset($data['scope']) ? $data['scope'] : 'unknown',
        ));

        // Keep only last 100 entries
        $audit_log = array_slice($audit_log, 0, 100);
        update_option('wpslt_snippet_audit_log', $audit_log);
    }
}

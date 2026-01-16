<?php
/**
 * PHP Code Executor for Snippets
 *
 * Safely executes PHP code snippets with error handling and safe mode support.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Snippets_Executor
 *
 * Executes PHP snippets with comprehensive error handling.
 */
class WPSLT_Snippets_Executor
{
    /**
     * Singleton instance.
     *
     * @var WPSLT_Snippets_Executor|null
     */
    private static $instance = null;

    /**
     * Database instance.
     *
     * @var WPSLT_Snippets_DB
     */
    private $db;

    /**
     * Executed snippet IDs (to prevent double execution).
     *
     * @var array
     */
    private $executed = array();

    /**
     * Whether snippets have been loaded.
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * Get singleton instance.
     *
     * @return WPSLT_Snippets_Executor
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->db = WPSLT_Snippets_DB::get_instance();
    }

    /**
     * Initialize the executor and hook into WordPress.
     */
    public function init()
    {
        // Check safe mode first
        if ($this->is_safe_mode()) {
            add_action('admin_notices', array($this, 'display_safe_mode_notice'));
            return;
        }

        /**
         * Execute snippets.
         * If plugins_loaded has already fired (e.g. theme functions.php context),
         * run immediately. Otherwise hook into plugins_loaded.
         */
        if (did_action('plugins_loaded')) {
            $this->execute_snippets();
        } else {
            add_action('plugins_loaded', array($this, 'execute_snippets'), 1);
        }
    }

    /**
     * Check if safe mode is active.
     *
     * @return bool
     */
    public function is_safe_mode()
    {
        // Check constant (can be set in wp-config.php)
        if (defined('WPSLT_SAFE_MODE') && WPSLT_SAFE_MODE) {
            return true;
        }

        // Check URL parameter (admin only with proper capability)
        if (is_admin() && isset($_GET['safe-mode'])) {
            // SECURITY: Sanitize GET parameter to prevent XSS
            $safe_mode_value = sanitize_key($_GET['safe-mode']);

            if ($safe_mode_value === '1') {
                // Verify this is a user that can manage options
                if (function_exists('current_user_can') && current_user_can('manage_options')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Display safe mode notice in admin.
     */
    public function display_safe_mode_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $current_url = remove_query_arg('safe-mode');
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e('Snippets Safe Mode Check: Safe Mode is ACTIVE. Snippets are not executing.', 'wp-slatan-theme'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Execute active snippets
     */
    public function execute_snippets()
    {


        // Prevent double execution
        if ($this->loaded || $this->is_safe_mode()) {
            return;
        }
        $this->loaded = true;

        $snippets = $this->db->get_snippets(array(
            'scope' => array('php-everywhere', 'php-admin', 'php-frontend'),
            'active' => 1
        ));

        if (empty($snippets)) {
            return;
        }

        foreach ($snippets as $snippet) {


            // Scope check
            if ($snippet['scope'] === 'php-admin' && !is_admin()) {
                continue;
            }
            if ($snippet['scope'] === 'php-frontend' && is_admin()) {
                continue;
            }

            try {
                // Execute PHP code
                // Code is validated before saving, but handle runtime errors
                $code = $snippet['code'];

                // Remove <?php tags if present (already stripped in validation but safety check)
                $code = preg_replace('/^<\?php\s*/', '', $code);
                $code = preg_replace('/\?>\s*$/', '', $code);

                // Pass the whole snippet array
                $this->execute_snippet($snippet);
            } catch (Exception $e) {
                error_log('Snippet Execution Error (ID ' . $snippet['id'] . '): ' . $e->getMessage());
            } catch (Throwable $e) {
                // Catch PHP 7+ errors
                error_log('Snippet Execution Error (ID ' . $snippet['id'] . '): ' . $e->getMessage());
            }
        }
    }

    /**
     * Conditionally execute a snippet based on its scope.
     *
     * @param array $snippet Snippet data.
     */
    private function maybe_execute_snippet($snippet)
    {
        $id = (int) $snippet['id'];

        // Prevent double execution
        if (in_array($id, $this->executed, true)) {
            return;
        }

        $scope = $snippet['scope'];

        // Check scope conditions
        if ($scope === 'php-admin' && !is_admin()) {
            return;
        }

        if ($scope === 'php-frontend' && is_admin()) {
            return;
        }

        // Execute the snippet
        $result = $this->execute_snippet($snippet);

        // Mark as executed
        $this->executed[] = $id;

        // Handle errors
        if (is_wp_error($result) || $result instanceof \Throwable) {
            $this->handle_execution_error($snippet, $result);
        }
    }

    /**
     * Execute a single snippet.
     *
     * @param array $snippet Snippet data.
     * @return mixed Result of execution or WP_Error on failure.
     */
    public function execute_snippet($snippet)
    {
        $code = $snippet['code'];
        $id = (int) $snippet['id'];

        if (empty(trim($code))) {
            return null;
        }



        // Start output buffering to capture any unwanted output
        ob_start();

        try {
            // Execute the code
            $result = eval ($code);
        } catch (\ParseError $e) {
            ob_end_clean();
            return new WP_Error(
                'parse_error',
                sprintf(
                    /* translators: 1: error message, 2: line number */
                    __('Parse error: %1$s on line %2$d', 'wp-slatan-theme'),
                    $e->getMessage(),
                    $e->getLine()
                ),
                array('snippet_id' => $id, 'exception' => $e)
            );
        } catch (\Error $e) {
            ob_end_clean();
            return new WP_Error(
                'fatal_error',
                sprintf(
                    /* translators: 1: error message, 2: line number */
                    __('Fatal error: %1$s on line %2$d', 'wp-slatan-theme'),
                    $e->getMessage(),
                    $e->getLine()
                ),
                array('snippet_id' => $id, 'exception' => $e)
            );
        } catch (\Throwable $e) {
            ob_end_clean();
            return new WP_Error(
                'execution_error',
                sprintf(
                    /* translators: 1: error message */
                    __('Execution error: %1$s', 'wp-slatan-theme'),
                    $e->getMessage()
                ),
                array('snippet_id' => $id, 'exception' => $e)
            );
        }

        // Capture output
        $output = ob_get_clean();

        // Check if eval returned something (e.g. user used 'return' instead of echo)
        if (is_string($result) && !empty($result)) {
            $output .= $result;
        }



        // If snippet produced output (and it's not an error), output it in footer
        if (!empty($output) && !is_wp_error($result)) {
            add_action('wp_footer', function () use ($output) {
                echo $output;
            }, 99);
        }

        return $result;
    }

    /**
     * Handle execution errors by deactivating the snippet.
     *
     * @param array                $snippet Snippet data.
     * @param WP_Error|\Throwable $error   The error that occurred.
     */
    private function handle_execution_error($snippet, $error)
    {
        $id = (int) $snippet['id'];
        $msg = is_wp_error($error) ? $error->get_error_message() : $error->getMessage();



        // Auto-deactivate the snippet
        $this->db->deactivate_snippet($id);

        // Store error for admin notice
        $errors = get_transient('wpslt_snippet_errors');
        if (!is_array($errors)) {
            $errors = array();
        }

        $error_message = is_wp_error($error) ? $error->get_error_message() : $error->getMessage();

        $errors[$id] = array(
            'name' => $snippet['name'],
            'message' => $error_message,
            'time' => current_time('mysql'),
        );

        set_transient('wpslt_snippet_errors', $errors, HOUR_IN_SECONDS);

        // Log the error
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[WP Slatan Theme] PHP Snippet #%d "%s" deactivated due to error: %s',
                $id,
                $snippet['name'],
                $error_message
            ));
        }
    }

    /**
     * Test execute a snippet without actually running it.
     *
     * @param string $code PHP code to test.
     * @return true|WP_Error True if valid, WP_Error on failure.
     */
    public function test_snippet($code)
    {
        if (!class_exists('WPSLT_Snippets_Validator')) {
            return true;
        }

        $result = WPSLT_Snippets_Validator::test_execution($code);

        if ($result === true) {
            return true;
        }

        return new WP_Error(
            'validation_error',
            $result['message'],
            array('line' => $result['line'])
        );
    }

    /**
     * Get stored execution errors.
     *
     * @return array
     */
    public function get_errors()
    {
        $errors = get_transient('wpslt_snippet_errors');
        return is_array($errors) ? $errors : array();
    }

    /**
     * Clear stored execution errors.
     *
     * @param int|null $snippet_id Optional specific snippet ID to clear.
     */
    public function clear_errors($snippet_id = null)
    {
        if ($snippet_id === null) {
            delete_transient('wpslt_snippet_errors');
        } else {
            $errors = $this->get_errors();
            unset($errors[$snippet_id]);

            if (empty($errors)) {
                delete_transient('wpslt_snippet_errors');
            } else {
                set_transient('wpslt_snippet_errors', $errors, HOUR_IN_SECONDS);
            }
        }
    }
}

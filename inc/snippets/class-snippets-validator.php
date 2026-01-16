<?php
/**
 * PHP Syntax Validator for Snippets
 *
 * Validates PHP code syntax before saving to prevent fatal errors.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Snippets_Validator
 *
 * Validates PHP code syntax and checks for dangerous patterns.
 */
class WPSLT_Snippets_Validator
{
    /**
     * List of dangerous functions that should trigger warnings.
     *
     * @var array
     */
    private static $dangerous_functions = array(
        'exec',
        'shell_exec',
        'system',
        'passthru',
        'popen',
        'proc_open',
        'pcntl_exec',
        'dl',
        'putenv',
        'ini_set',
        'ini_alter',
        'ini_restore',
        'apache_setenv',
        'virtual',
        'call_user_func',
        'call_user_func_array',
        'create_function',
        'unlink',
        'rmdir',
        'rename',
        'copy',
        'file_put_contents',
        'fwrite',
        'fputs',
        'fputcsv',
        'chmod',
        'chown',
        'chgrp',
        'symlink',
        'link',
        'mail',
        'wp_mail',
        'curl_init',
        'curl_exec',
        'file_get_contents',
        'socket_create',
        'fsockopen',
        'pfsockopen',
    );

    /**
     * Validate PHP code syntax.
     *
     * @param string $code PHP code to validate.
     * @return array Result with 'valid' boolean and 'errors' array.
     */
    public static function validate_syntax($code)
    {
        $result = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array(),
        );

        if (empty(trim($code))) {
            return $result;
        }

        // Clean the code - remove opening and closing PHP tags
        $code = self::clean_code($code);

        // Try to tokenize the code to check for syntax errors
        // Using chr(60) for '<' to prevent IDE confusion
        $php_open_tag = chr(60) . '?php ';
        $tokens = @token_get_all($php_open_tag . $code);

        if ($tokens === false) {
            $result['valid'] = false;
            $result['errors'][] = array(
                'message' => __('Unable to parse PHP code.', 'wp-slatan-theme'),
                'line' => 0,
            );
            return $result;
        }

        // Use PHP's built-in syntax check via lint
        $syntax_check = self::lint_code($code);
        if ($syntax_check !== true) {
            $result['valid'] = false;
            $result['errors'][] = $syntax_check;
            return $result;
        }

        // Check for dangerous functions (warnings only)
        $dangerous = self::check_dangerous_functions($code);
        if (!empty($dangerous)) {
            $result['warnings'] = $dangerous;
        }

        return $result;
    }

    /**
     * Clean PHP code by removing opening/closing tags.
     *
     * @param string $code Raw PHP code.
     * @return string Cleaned code.
     */
    public static function clean_code($code)
    {
        // Handle null/empty code
        if ($code === null || $code === '') {
            return '';
        }

        // Remove opening PHP tags
        // Using chr(60) for '<' and chr(63) for '?' to prevent IDE confusion
        $open_tag_pattern = '/^\s*<\?(?:php)?/i';
        $code = preg_replace($open_tag_pattern, '', $code);

        // Remove closing PHP tags
        $close_tag_pattern = '/\?>\s*$/';
        $code = preg_replace($close_tag_pattern, '', $code);

        return trim($code);
    }

    /**
     * Lint PHP code using temporary file execution.
     *
     * @param string $code PHP code to lint.
     * @return true|array True if valid, error array if not.
     */
    private static function lint_code($code)
    {
        // Create a temporary file for syntax checking
        $temp_file = wp_tempnam('wpslt_snippet_');

        if (!$temp_file) {
            return true; // If we can't create temp file, skip lint check
        }

        // Write code to temp file with PHP tags
        // Using chr(60) for '<' to prevent IDE confusion
        $php_open_tag = chr(60) . '?php ';
        file_put_contents($temp_file, $php_open_tag . $code);

        // Run PHP lint check
        $output = array();
        $return_code = 0;

        // Use PHP's -l flag for syntax checking
        $php_binary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $command = sprintf('%s -l %s 2>&1', escapeshellarg($php_binary), escapeshellarg($temp_file));

        if (!function_exists('exec')) {
            return true;
        }

        exec($command, $output, $return_code);

        // Clean up temp file
        @unlink($temp_file);

        if ($return_code !== 0) {
            // Parse error message
            $error_message = implode("\n", $output);
            $line = 1;

            // Try to extract line number
            if (preg_match('/on line (\d+)/', $error_message, $matches)) {
                $line = (int) $matches[1];
            }

            // Clean up error message
            $error_message = preg_replace('/in .+ on line \d+/', '', $error_message);
            $error_message = preg_replace('/^.*error:\s*/i', '', $error_message);
            $error_message = trim($error_message);

            return array(
                'message' => $error_message ?: __('Syntax error in PHP code.', 'wp-slatan-theme'),
                'line' => max(1, $line - 1), // Adjust for added opening PHP tag
            );
        }

        return true;
    }

    /**
     * Check for potentially dangerous functions.
     *
     * @param string $code PHP code to check.
     * @return array Array of warnings.
     */
    private static function check_dangerous_functions($code)
    {
        $warnings = array();

        foreach (self::$dangerous_functions as $func) {
            // Match function calls (not method calls)
            $pattern = '/\b' . preg_quote($func, '/') . '\s*\(/i';

            if (preg_match($pattern, $code)) {
                $warnings[] = array(
                    'function' => $func,
                    'message' => sprintf(
                        /* translators: %s: function name */
                        __('Warning: Using "%s" function could be dangerous.', 'wp-slatan-theme'),
                        $func
                    ),
                );
            }
        }

        return $warnings;
    }

    /**
     * Validate and prepare code for saving.
     *
     * @param string $code Raw PHP code.
     * @return array Result with 'code' and 'validation' keys.
     */
    public static function prepare_for_save($code)
    {
        $cleaned = self::clean_code($code);
        $validation = self::validate_syntax($cleaned);

        return array(
            'code' => $cleaned,
            'validation' => $validation,
        );
    }

    /**
     * Test execute code without actually running it.
     *
     * Uses PHP's internal parsing to check for runtime errors that
     * can be detected without execution.
     *
     * @param string $code PHP code to test.
     * @return true|array True if no issues found, error array otherwise.
     */
    public static function test_execution($code)
    {
        $code = self::clean_code($code);

        // First, validate syntax
        $validation = self::validate_syntax($code);
        if (!$validation['valid']) {
            return $validation['errors'][0];
        }

        // Check for common runtime issues
        $checks = array(
            // Check for undefined variable usage in dangerous context
            '/\$\{[^}]*\}/' => __('Variable variables can be dangerous.', 'wp-slatan-theme'),
            // Check for backtick execution
            '/`[^`]+`/' => __('Backtick execution operators are not allowed.', 'wp-slatan-theme'),
        );

        foreach ($checks as $pattern => $message) {
            if (preg_match($pattern, $code)) {
                return array(
                    'message' => $message,
                    'line' => 0,
                );
            }
        }

        return true;
    }

    /**
     * Check for function/class name collisions.
     *
     * Detects duplicate function/class definitions that would cause fatal errors.
     *
     * @param string $code PHP code to check.
     * @param int $snippet_id Current snippet ID (0 for new snippets).
     * @return array Array of collision errors, empty if safe.
     */
    public static function check_collisions($code, $snippet_id = 0)
    {
        $code = self::clean_code($code);
        $collisions = array();

        if (empty(trim($code))) {
            return $collisions;
        }

        // NOTE: We allow exact duplicate code to be saved (for backup/versioning)
        // but we'll detect it and add as a warning, not an error

        // Extract function and class names from the code
        $defined_items = self::extract_definitions($code);

        // Check for duplicates within the same snippet
        $internal_duplicates = self::check_internal_duplicates($defined_items);
        if (!empty($internal_duplicates)) {
            $collisions = array_merge($collisions, $internal_duplicates);
        }

        // Check against other active PHP snippets FIRST
        // This way we know which functions come from our own snippets
        $snippet_conflicts = self::check_snippet_conflicts($defined_items, $snippet_id);

        // CRITICAL: Check against external plugin snippets (Code Snippets, WPCode, etc.)
        $plugin_conflicts = self::check_plugin_conflicts($defined_items);
        if (!empty($plugin_conflicts)) {
            $collisions = array_merge($collisions, $plugin_conflicts);
        }

        // Get list of functions/classes that exist in snippets or plugins
        // so we can exclude them from core conflict check
        $snippet_functions = array();
        $snippet_classes = array();
        foreach ($snippet_conflicts as $conflict) {
            if (isset($conflict['type']) && isset($conflict['name'])) {
                if ($conflict['type'] === 'function') {
                    $snippet_functions[] = $conflict['name'];
                } elseif ($conflict['type'] === 'class') {
                    $snippet_classes[] = $conflict['name'];
                }
            }
        }
        foreach ($plugin_conflicts as $conflict) {
            if (isset($conflict['type']) && isset($conflict['name'])) {
                if ($conflict['type'] === 'function') {
                    $snippet_functions[] = $conflict['name'];
                } elseif ($conflict['type'] === 'class') {
                    $snippet_classes[] = $conflict['name'];
                }
            }
        }

        // CRITICAL FIX: If editing an existing snippet, extract its CURRENT functions/classes
        // and add them to exclusion list. This prevents false positives when editing
        // an active snippet (its functions are already loaded in PHP)
        if ($snippet_id > 0) {
            $db = WPSLT_Snippets_DB::get_instance();
            $current_snippet = $db->get_snippet($snippet_id);

            if ($current_snippet && !empty($current_snippet['code'])) {
                $current_code = self::clean_code($current_snippet['code']);
                $current_definitions = self::extract_definitions($current_code);

                // Add current snippet's functions/classes to exclusion list
                $snippet_functions = array_merge($snippet_functions, $current_definitions['functions']);
                $snippet_classes = array_merge($snippet_classes, $current_definitions['classes']);

                // Remove duplicates
                $snippet_functions = array_unique($snippet_functions);
                $snippet_classes = array_unique($snippet_classes);
            }
        }

        // Check against WordPress core and loaded functions/classes
        // but EXCLUDE functions/classes that we know come from our snippets
        $core_conflicts = self::check_core_conflicts($defined_items, $snippet_functions, $snippet_classes);
        if (!empty($core_conflicts)) {
            $collisions = array_merge($collisions, $core_conflicts);
        }

        // Add snippet conflicts to collisions
        if (!empty($snippet_conflicts)) {
            $collisions = array_merge($collisions, $snippet_conflicts);
        }

        return $collisions;
    }

    /**
     * Check for exact duplicate code.
     *
     * Prevents saving snippets with identical code.
     *
     * @param string $code PHP code to check.
     * @param int $snippet_id Current snippet ID (0 for new snippets).
     * @return array|null Collision error or null if no duplicate found.
     */
    private static function check_exact_duplicate($code, $snippet_id)
    {
        // Get database instance
        $db = WPSLT_Snippets_DB::get_instance();

        // Get ALL PHP snippets
        $php_scopes = array('php-everywhere', 'php-frontend', 'php-admin');
        $snippets = $db->get_snippets(array('scope' => $php_scopes));

        // Filter out current snippet
        $snippets = array_filter($snippets, function ($snippet) use ($snippet_id) {
            return absint($snippet['id']) !== absint($snippet_id);
        });

        // Normalize code for comparison (remove whitespace variations)
        $normalized_code = self::normalize_code_for_comparison($code);
        $code_hash = md5($normalized_code);

        foreach ($snippets as $snippet) {
            $other_code = isset($snippet['code']) ? self::clean_code($snippet['code']) : '';
            if (empty($other_code)) {
                continue;
            }

            $other_normalized = self::normalize_code_for_comparison($other_code);
            $other_hash = md5($other_normalized);

            // Check exact match by hash (faster than string comparison)
            if ($code_hash === $other_hash) {
                return array(
                    'type' => 'duplicate_code',
                    'snippet' => $snippet['name'],
                    'snippet_id' => $snippet['id'],
                    'message' => sprintf(
                        /* translators: %s: snippet name */
                        __('⛔ This code is identical to snippet "%s". Cannot save duplicate snippets.', 'wp-slatan-theme'),
                        $snippet['name']
                    ),
                );
            }
        }

        return null;
    }

    /**
     * Normalize code for comparison.
     *
     * Removes comments and normalizes whitespace to detect functional duplicates.
     *
     * @param string $code PHP code.
     * @return string Normalized code.
     */
    private static function normalize_code_for_comparison($code)
    {
        // Remove all comments and normalize whitespace
        $php_open_tag = chr(60) . '?php ';
        $tokens = token_get_all($php_open_tag . $code);

        $normalized = '';
        foreach ($tokens as $token) {
            if (is_array($token)) {
                // Skip comments and docblocks
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }
                // Normalize whitespace
                if ($token[0] === T_WHITESPACE) {
                    $normalized .= ' ';
                    continue;
                }
                $normalized .= $token[1];
            } else {
                $normalized .= $token;
            }
        }

        // Remove multiple spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    /**
     * Extract function and class definitions from PHP code.
     *
     * @param string $code PHP code.
     * @return array Array with 'functions' and 'classes' keys.
     */
    private static function extract_definitions($code)
    {
        $definitions = array(
            'functions' => array(),
            'classes' => array(),
        );

        // Using chr(60) for '<' to prevent IDE confusion
        $php_open_tag = chr(60) . '?php ';
        $tokens = @token_get_all($php_open_tag . $code);

        if ($tokens === false) {
            return $definitions;
        }

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i])) {
                continue;
            }

            $token = $tokens[$i];

            // Check for function definitions
            if ($token[0] === T_FUNCTION) {
                // Find the function name (next non-whitespace token)
                for ($j = $i + 1; $j < $count; $j++) {
                    if (!is_array($tokens[$j])) {
                        break;
                    }
                    if ($tokens[$j][0] === T_STRING) {
                        $definitions['functions'][] = $tokens[$j][1];
                        break;
                    }
                    if ($tokens[$j][0] !== T_WHITESPACE) {
                        break;
                    }
                }
            }

            // Check for class definitions
            if ($token[0] === T_CLASS || $token[0] === T_INTERFACE || $token[0] === T_TRAIT) {
                // Find the class/interface/trait name
                for ($j = $i + 1; $j < $count; $j++) {
                    if (!is_array($tokens[$j])) {
                        break;
                    }
                    if ($tokens[$j][0] === T_STRING) {
                        $definitions['classes'][] = $tokens[$j][1];
                        break;
                    }
                    if ($tokens[$j][0] !== T_WHITESPACE) {
                        break;
                    }
                }
            }
        }

        return $definitions;
    }

    /**
     * Check for duplicate definitions within the same code.
     *
     * @param array $definitions Extracted definitions.
     * @return array Collision errors.
     */
    private static function check_internal_duplicates($definitions)
    {
        $errors = array();

        // Check duplicate functions
        $func_counts = array_count_values($definitions['functions']);
        foreach ($func_counts as $func_name => $count) {
            if ($count > 1) {
                $errors[] = array(
                    'type' => 'function',
                    'name' => $func_name,
                    'message' => sprintf(
                        /* translators: %s: function name */
                        __('Function "%s" is defined multiple times in this snippet.', 'wp-slatan-theme'),
                        $func_name
                    ),
                );
            }
        }

        // Check duplicate classes
        $class_counts = array_count_values($definitions['classes']);
        foreach ($class_counts as $class_name => $count) {
            if ($count > 1) {
                $errors[] = array(
                    'type' => 'class',
                    'name' => $class_name,
                    'message' => sprintf(
                        /* translators: %s: class name */
                        __('Class/Interface/Trait "%s" is defined multiple times in this snippet.', 'wp-slatan-theme'),
                        $class_name
                    ),
                );
            }
        }

        return $errors;
    }

    /**
     * Check for conflicts with WordPress core and loaded code.
     *
     * @param array $definitions Extracted definitions.
     * @param array $exclude_functions Functions to exclude from check (from snippets).
     * @param array $exclude_classes Classes to exclude from check (from snippets).
     * @return array Collision errors.
     */
    private static function check_core_conflicts($definitions, $exclude_functions = array(), $exclude_classes = array())
    {
        $errors = array();

        // Check functions
        foreach ($definitions['functions'] as $func_name) {
            // Skip if this function is from one of our snippets
            if (in_array($func_name, $exclude_functions, true)) {
                continue;
            }

            if (function_exists($func_name)) {
                $errors[] = array(
                    'type' => 'function',
                    'name' => $func_name,
                    'message' => sprintf(
                        /* translators: %s: function name */
                        __('Function "%s" already exists (WordPress core or active plugin/theme).', 'wp-slatan-theme'),
                        $func_name
                    ),
                );
            }
        }

        // Check classes
        foreach ($definitions['classes'] as $class_name) {
            // Skip if this class is from one of our snippets
            if (in_array($class_name, $exclude_classes, true)) {
                continue;
            }

            if (class_exists($class_name) || interface_exists($class_name) || trait_exists($class_name)) {
                $errors[] = array(
                    'type' => 'class',
                    'name' => $class_name,
                    'message' => sprintf(
                        /* translators: %s: class name */
                        __('Class/Interface/Trait "%s" already exists (WordPress core or active plugin/theme).', 'wp-slatan-theme'),
                        $class_name
                    ),
                );
            }
        }

        return $errors;
    }

    /**
     * Check for conflicts with other PHP snippets (active and inactive).
     *
     * @param array $definitions Extracted definitions.
     * @param int $current_id Current snippet ID to exclude.
     * @return array Collision errors.
     */
    private static function check_snippet_conflicts($definitions, $current_id)
    {
        $errors = array();

        // Get database instance
        $db = WPSLT_Snippets_DB::get_instance();

        // Get ALL active PHP snippets - these are already loaded and will cause immediate conflicts
        $php_scopes = array('php-everywhere', 'php-frontend', 'php-admin');
        $all_snippets = $db->get_snippets(array('scope' => $php_scopes));

        // Filter out current snippet
        $all_snippets = array_filter($all_snippets, function ($snippet) use ($current_id) {
            return absint($snippet['id']) !== absint($current_id);
        });

        // Separate active and inactive snippets
        $active_snippets = array_filter($all_snippets, function ($snippet) {
            return isset($snippet['active']) && $snippet['active'] == 1;
        });

        $inactive_snippets = array_filter($all_snippets, function ($snippet) {
            return !isset($snippet['active']) || $snippet['active'] != 1;
        });

        // CHECK 1: Active snippets - these will cause IMMEDIATE fatal errors
        foreach ($active_snippets as $snippet) {
            $other_code = isset($snippet['code']) ? $snippet['code'] : '';
            if (empty($other_code)) {
                continue;
            }

            $other_definitions = self::extract_definitions($other_code);

            // Check function conflicts with ACTIVE snippets
            foreach ($definitions['functions'] as $func_name) {
                if (in_array($func_name, $other_definitions['functions'], true)) {
                    $errors[] = array(
                        'type' => 'function',
                        'name' => $func_name,
                        'snippet' => $snippet['name'],
                        'active' => true,
                        'message' => sprintf(
                            /* translators: 1: function name, 2: snippet name */
                            __('⛔ Function "%1$s" is already loaded by ACTIVE snippet "%2$s". Activating this will crash your site!', 'wp-slatan-theme'),
                            $func_name,
                            $snippet['name']
                        ),
                    );
                }
            }

            // Check class conflicts with ACTIVE snippets
            foreach ($definitions['classes'] as $class_name) {
                if (in_array($class_name, $other_definitions['classes'], true)) {
                    $errors[] = array(
                        'type' => 'class',
                        'name' => $class_name,
                        'snippet' => $snippet['name'],
                        'active' => true,
                        'message' => sprintf(
                            /* translators: 1: class name, 2: snippet name */
                            __('⛔ Class "%1$s" is already loaded by ACTIVE snippet "%2$s". Activating this will crash your site!', 'wp-slatan-theme'),
                            $class_name,
                            $snippet['name']
                        ),
                    );
                }
            }
        }


        // Only return errors for ACTIVE snippet collisions
        // Inactive snippet collisions are not a problem - only one can be active at a time
        return $errors;
    }

    /**
     * Get snippets from external plugins that support code snippets.
     *
     * @return array Array of plugin snippets with 'plugin', 'name', 'code', 'active' keys.
     */
    private static function get_plugin_snippets()
    {
        global $wpdb;
        $plugin_snippets = array();

        // 1. Code Snippets Plugin (https://wordpress.org/plugins/code-snippets/)
        // Table: {prefix}snippets
        $table_name = $wpdb->prefix . 'snippets';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name) {
            // Get all PHP snippets from Code Snippets plugin
            $results = $wpdb->get_results(
                "SELECT id, name, code, active, scope FROM {$table_name} WHERE scope IN ('global', 'admin', 'front-end', 'single-use')",
                ARRAY_A
            );

            if (!empty($results)) {
                foreach ($results as $row) {
                    // Only include PHP snippets (Code Snippets plugin only has PHP)
                    $plugin_snippets[] = array(
                        'plugin' => 'Code Snippets',
                        'plugin_id' => 'code-snippets',
                        'snippet_id' => isset($row['id']) ? absint($row['id']) : 0,
                        'name' => isset($row['name']) ? $row['name'] : '',
                        'code' => isset($row['code']) ? $row['code'] : '',
                        'active' => isset($row['active']) && $row['active'] == 1,
                        'scope' => isset($row['scope']) ? $row['scope'] : 'global',
                    );
                }
            }
        }

        // 2. WPCode Plugin (https://wordpress.org/plugins/insert-headers-and-footers/)
        // Table: {prefix}wpcode_snippets
        $wpcode_table = $wpdb->prefix . 'wpcode_snippets';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpcode_table}'") === $wpcode_table) {
            // Get all active PHP snippets from WPCode plugin
            $results = $wpdb->get_results(
                "SELECT id, title, code, active, code_type FROM {$wpcode_table} WHERE code_type = 'php'",
                ARRAY_A
            );

            if (!empty($results)) {
                foreach ($results as $row) {
                    $plugin_snippets[] = array(
                        'plugin' => 'WPCode',
                        'plugin_id' => 'wpcode',
                        'snippet_id' => isset($row['id']) ? absint($row['id']) : 0,
                        'name' => isset($row['title']) ? $row['title'] : '',
                        'code' => isset($row['code']) ? $row['code'] : '',
                        'active' => isset($row['active']) && $row['active'] == 1,
                        'scope' => 'php',
                    );
                }
            }
        }

        return $plugin_snippets;
    }

    /**
     * Check for conflicts with snippets from external plugins.
     *
     * @param array $definitions Extracted definitions from current snippet.
     * @param int $current_id Current snippet ID to exclude.
     * @return array Collision errors.
     */
    private static function check_plugin_conflicts($definitions)
    {
        $errors = array();

        // Get all plugin snippets
        $plugin_snippets = self::get_plugin_snippets();

        if (empty($plugin_snippets)) {
            return $errors;
        }

        // Separate active and inactive plugin snippets
        $active_plugin_snippets = array_filter($plugin_snippets, function ($snippet) {
            return isset($snippet['active']) && $snippet['active'] === true;
        });

        // CHECK: Active plugin snippets - these will cause IMMEDIATE fatal errors
        foreach ($active_plugin_snippets as $snippet) {
            $plugin_code = isset($snippet['code']) ? $snippet['code'] : '';
            if (empty($plugin_code)) {
                continue;
            }

            // Clean and extract definitions from plugin snippet
            $plugin_code_clean = self::clean_code($plugin_code);
            $plugin_definitions = self::extract_definitions($plugin_code_clean);

            // Check function conflicts with ACTIVE plugin snippets
            foreach ($definitions['functions'] as $func_name) {
                if (in_array($func_name, $plugin_definitions['functions'], true)) {
                    $errors[] = array(
                        'type' => 'function',
                        'name' => $func_name,
                        'plugin' => $snippet['plugin'],
                        'snippet' => $snippet['name'],
                        'active' => true,
                        'message' => sprintf(
                            /* translators: 1: function name, 2: plugin name, 3: snippet name */
                            __('⛔ Function "%1$s" is already loaded by plugin "%2$s" (snippet: "%3$s"). Activating this will crash your site!', 'wp-slatan-theme'),
                            $func_name,
                            $snippet['plugin'],
                            $snippet['name']
                        ),
                    );
                }
            }

            // Check class conflicts with ACTIVE plugin snippets
            foreach ($definitions['classes'] as $class_name) {
                if (in_array($class_name, $plugin_definitions['classes'], true)) {
                    $errors[] = array(
                        'type' => 'class',
                        'name' => $class_name,
                        'plugin' => $snippet['plugin'],
                        'snippet' => $snippet['name'],
                        'active' => true,
                        'message' => sprintf(
                            /* translators: 1: class name, 2: plugin name, 3: snippet name */
                            __('⛔ Class "%1$s" is already loaded by plugin "%2$s" (snippet: "%3$s"). Activating this will crash your site!', 'wp-slatan-theme'),
                            $class_name,
                            $snippet['plugin'],
                            $snippet['name']
                        ),
                    );
                }
            }
        }

        return $errors;
    }
}

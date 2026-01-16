<?php
/**
 * Snippets Database Handler
 * 
 * Handles database operations for code snippets.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WPSLT_Snippets_DB
 */
class WPSLT_Snippets_DB
{

    /**
     * Table name without prefix
     */
    const TABLE_NAME = 'wpslt_snippets';

    /**
     * Full table name with prefix
     *
     * @var string
     */
    private $table;

    /**
     * Singleton instance
     *
     * @var WPSLT_Snippets_DB
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return WPSLT_Snippets_DB
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
        global $wpdb;
        $this->table = $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function get_table_name()
    {
        return $this->table;
    }

    /**
     * Create the snippets table
     *
     * @return bool Whether table creation was successful
     */
    public function create_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            name TINYTEXT NOT NULL,
            description TEXT,
            code LONGTEXT NOT NULL,
            scope VARCHAR(20) NOT NULL DEFAULT 'site-css',
            active TINYINT(1) NOT NULL DEFAULT 0,
            priority SMALLINT NOT NULL DEFAULT 10,
            modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY scope (scope),
            KEY active (active)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        return empty($wpdb->last_error);
    }

    /**
     * Check if table exists
     *
     * @return bool
     */
    public function table_exists()
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like($this->table)
            )
        );

        return $result === $this->table;
    }

    /**
     * Get all snippets
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_snippets($args = array())
    {
        global $wpdb;

        $defaults = array(
            'scope' => '',
            'active' => null,
            'orderby' => 'priority',
            'order' => 'ASC',
            'per_page' => -1,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['scope'])) {
            if (is_array($args['scope'])) {
                $placeholders = implode(',', array_fill(0, count($args['scope']), '%s'));
                $where[] = "scope IN ($placeholders)";
                $values = array_merge($values, $args['scope']);
            } else {
                $where[] = 'scope = %s';
                $values[] = $args['scope'];
            }
        }

        if (null !== $args['active']) {
            $where[] = 'active = %d';
            $values[] = (int) $args['active'];
        }

        $where_clause = implode(' AND ', $where);

        // Sanitize orderby
        $allowed_orderby = array('id', 'name', 'scope', 'active', 'priority', 'modified');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'priority';
        $order = 'DESC' === strtoupper($args['order']) ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$orderby} {$order}, id ASC";

        if ($args['per_page'] > 0) {
            $sql .= $wpdb->prepare(' LIMIT %d OFFSET %d', $args['per_page'], $args['offset']);
        }

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Get active snippets by scope
     *
     * @param string|array $scopes Scope or array of scopes.
     * @return array
     */
    public function get_active_snippets($scopes)
    {
        global $wpdb;

        if (!is_array($scopes)) {
            $scopes = array($scopes);
        }

        $placeholders = implode(',', array_fill(0, count($scopes), '%s'));

        $sql = $wpdb->prepare(
            "SELECT id, name, code, scope, priority 
             FROM {$this->table} 
             WHERE active = 1 AND scope IN ($placeholders) 
             ORDER BY priority ASC, id ASC",
            $scopes
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Get a single snippet by ID
     *
     * @param int $id Snippet ID.
     * @return array|null
     */
    public function get_snippet($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
    }

    /**
     * Save a snippet (insert or update)
     *
     * @param array $data Snippet data.
     * @return int|false Snippet ID on success, false on failure
     */
    public function save_snippet($data)
    {
        global $wpdb;

        $id = isset($data['id']) ? absint($data['id']) : 0;

        // Sanitize data
        $snippet = array(
            'name' => sanitize_text_field(isset($data['name']) ? $data['name'] : ''),
            'description' => sanitize_textarea_field(isset($data['description']) ? $data['description'] : ''),
            'code' => wp_unslash(isset($data['code']) ? $data['code'] : ''), // Don't sanitize code, could break it
            'scope' => $this->sanitize_scope(isset($data['scope']) ? $data['scope'] : 'site-css'),
            'active' => isset($data['active']) ? absint($data['active']) : 0,
            'priority' => isset($data['priority']) ? absint($data['priority']) : 10,
            'modified' => current_time('mysql'),
        );

        $format = array('%s', '%s', '%s', '%s', '%d', '%d', '%s');

        if ($id > 0) {
            // Update
            $result = $wpdb->update(
                $this->table,
                $snippet,
                array('id' => $id),
                $format,
                array('%d')
            );

            if (false !== $result) {
                $this->clear_counts_cache();
                return $id;
            }
            return false;
        } else {
            // Insert
            $result = $wpdb->insert($this->table, $snippet, $format);

            if ($result) {
                $this->clear_counts_cache();
                return $wpdb->insert_id;
            }
            return false;
        }
    }

    /**
     * Delete a snippet
     *
     * @param int $id Snippet ID.
     * @return bool
     */
    public function delete_snippet($id)
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            array('id' => absint($id)),
            array('%d')
        );

        if (false !== $result) {
            $this->clear_counts_cache();
        }

        return false !== $result;
    }

    /**
     * Activate a snippet
     *
     * @param int $id Snippet ID.
     * @return bool
     */
    public function activate_snippet($id)
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->table,
            array('active' => 1, 'modified' => current_time('mysql')),
            array('id' => absint($id)),
            array('%d', '%s'),
            array('%d')
        );

        if (false !== $result) {
            $this->clear_counts_cache();
        }

        return false !== $result;
    }

    /**
     * Deactivate a snippet
     *
     * @param int $id Snippet ID.
     * @return bool
     */
    public function deactivate_snippet($id)
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->table,
            array('active' => 0, 'modified' => current_time('mysql')),
            array('id' => absint($id)),
            array('%d', '%s'),
            array('%d')
        );

        if (false !== $result) {
            $this->clear_counts_cache();
        }

        return false !== $result;
    }

    /**
     * Get total count of snippets
     *
     * @param array $args Query arguments.
     * @return int
     */
    public function get_count($args = array())
    {
        global $wpdb;

        $where = array('1=1');
        $values = array();

        if (!empty($args['scope'])) {
            $where[] = 'scope = %s';
            $values[] = $args['scope'];
        }

        if (isset($args['active'])) {
            $where[] = 'active = %d';
            $values[] = (int) $args['active'];
        }

        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get all counts grouped by scope (optimized single query)
     *
     * @return array Associative array of scope => count
     */
    public function get_all_counts()
    {
        global $wpdb;

        // Try to get from transient cache first
        $cached = get_transient('wpslt_snippet_counts');
        if (false !== $cached && is_array($cached)) {
            return $cached;
        }

        // Single query with GROUP BY - reduces 7 queries to 1
        $sql = "SELECT scope, COUNT(*) as count FROM {$this->table} GROUP BY scope";
        $results = $wpdb->get_results($sql, ARRAY_A);

        // Format results as scope => count
        $counts = array();
        if ($results) {
            foreach ($results as $row) {
                $counts[$row['scope']] = (int) $row['count'];
            }
        }

        // Cache for 5 minutes
        set_transient('wpslt_snippet_counts', $counts, 5 * MINUTE_IN_SECONDS);

        return $counts;
    }

    /**
     * Clear the snippet counts cache
     * Called when snippets are added, updated, or deleted
     */
    public function clear_counts_cache()
    {
        delete_transient('wpslt_snippet_counts');
    }

    /**
     * Sanitize scope value
     *
     * @param string $scope Scope value.
     * @return string
     */
    private function sanitize_scope($scope)
    {
        $valid_scopes = array(
            // CSS Scopes
            'site-css',
            'admin-css',
            // JavaScript Scopes
            'site-head-js',
            'site-footer-js',
            // PHP Scopes
            'php-everywhere',
            'php-frontend',
            'php-admin',
        );

        return in_array($scope, $valid_scopes, true) ? $scope : 'site-css';
    }

    /**
     * Get available scopes
     *
     * @return array
     */
    public static function get_scopes()
    {
        return array(
            // CSS Scopes
            'site-css' => __('Frontend CSS', 'wp-slatan-theme'),
            'admin-css' => __('Admin CSS', 'wp-slatan-theme'),
            // JavaScript Scopes
            'site-head-js' => __('Frontend JS (Head)', 'wp-slatan-theme'),
            'site-footer-js' => __('Frontend JS (Footer)', 'wp-slatan-theme'),
            // PHP Scopes
            'php-everywhere' => __('Run Everywhere', 'wp-slatan-theme'),
            'php-frontend' => __('Frontend Only', 'wp-slatan-theme'),
            'php-admin' => __('Admin Only', 'wp-slatan-theme'),
        );
    }

    /**
     * Get scopes by type
     *
     * @param string $type Type of scopes (css, js, php).
     * @return array
     */
    public static function get_scopes_by_type($type = 'all')
    {
        $all_scopes = self::get_scopes();

        switch ($type) {
            case 'css':
                return array(
                    'site-css' => $all_scopes['site-css'],
                    'admin-css' => $all_scopes['admin-css'],
                );
            case 'js':
                return array(
                    'site-head-js' => $all_scopes['site-head-js'],
                    'site-footer-js' => $all_scopes['site-footer-js'],
                );
            case 'php':
                return array(
                    'php-everywhere' => $all_scopes['php-everywhere'],
                    'php-frontend' => $all_scopes['php-frontend'],
                    'php-admin' => $all_scopes['php-admin'],
                );
            default:
                return $all_scopes;
        }
    }

    /**
     * Get snippet type from scope
     *
     * @param string $scope Scope name.
     * @return string Type (css, js, php).
     */
    public static function get_type_from_scope($scope)
    {
        if (strpos($scope, '-css') !== false) {
            return 'css';
        }
        if (strpos($scope, '-js') !== false) {
            return 'js';
        }
        if (strpos($scope, 'php-') === 0) {
            return 'php';
        }
        return 'css';
    }
}

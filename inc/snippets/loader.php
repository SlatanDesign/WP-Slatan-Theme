<?php
/**
 * Snippets Feature Loader
 * 
 * Loads all snippet-related functionality.
 *
 * @package WP_Slatan_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load snippets classes
require_once __DIR__ . '/class-snippets-db.php';
require_once __DIR__ . '/class-snippets-validator.php';
require_once __DIR__ . '/class-snippets-executor.php';
require_once __DIR__ . '/class-snippets-output.php';
require_once __DIR__ . '/class-snippets-admin.php';

// Initialize PHP executor immediately (before any hooks fire!)
// This is critical because PHP snippets need to run early
$executor = WPSLT_Snippets_Executor::get_instance();
$executor->init();

/**
 * Initialize snippets feature
 */
function wpslt_init_snippets()
{
    // Initialize output (always needed for frontend)
    WPSLT_Snippets_Output::get_instance();

    // Initialize admin (only in admin)
    if (is_admin()) {
        WPSLT_Snippets_Admin::get_instance();
    }
}
add_action('init', 'wpslt_init_snippets');

/**
 * Create snippets table on theme activation
 */
function wpslt_snippets_activation()
{
    $db = WPSLT_Snippets_DB::get_instance();
    $db->create_table();
}

/**
 * Get snippet database instance
 *
 * @return WPSLT_Snippets_DB
 */
function wpslt_snippets_db()
{
    return WPSLT_Snippets_DB::get_instance();
}

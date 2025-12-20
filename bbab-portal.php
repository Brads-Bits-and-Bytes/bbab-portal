<?php
/**
 * Plugin Name: BBAB Portal
 * Plugin URI: https://bradwales.com
 * Description: Private admin portal with server-side authentication for managing portfolio entries.
 * Version: 1.0.0
 * Author: Brad Wales
 * Author URI: https://bradwales.com
 * Text Domain: bbab-portal
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'BBAB_PORTAL_VERSION', '1.0.0' );
define( 'BBAB_PORTAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'BBAB_PORTAL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin classes
 */
function bbab_portal_load_classes() {
    require_once BBAB_PORTAL_PATH . 'includes/class-access-control.php';
}
add_action( 'plugins_loaded', 'bbab_portal_load_classes' );

/**
 * Initialize access control
 * 
 * We hook this to 'init' to ensure WordPress is fully loaded
 * before we start checking authentication.
 */
function bbab_portal_init() {
    new BBAB_Portal_Access_Control();
}
add_action( 'init', 'bbab_portal_init' );

/**
 * Activation hook
 */
function bbab_portal_activate() {
    // Flush rewrite rules to ensure portal pages work correctly
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bbab_portal_activate' );

/**
 * Deactivation hook
 */
function bbab_portal_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bbab_portal_deactivate' );
<?php
/**
 * Plugin Name: BBAB Portal
 * Plugin URI: https://bradwales.com
 * Description: Private admin portal with server-side authentication for managing portfolio entries.
 * Version: 1.1.0
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
define( 'BBAB_PORTAL_VERSION', '1.1.0' );
define( 'BBAB_PORTAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'BBAB_PORTAL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin classes
 */
function bbab_portal_load_classes() {
    require_once BBAB_PORTAL_PATH . 'includes/class-access-control.php';
    require_once BBAB_PORTAL_PATH . 'includes/class-shortcodes.php';
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

/**
 * Add settings page as top level menu
 */
function bbab_po_add_settings_page() {
    add_menu_page(
        'BBAB Portal Settings',
        'BBAB Portal',
        'manage_options',
        'bbab-portal-settings',
        'bbab_po_render_settings',
        'dashicons-admin-home',  // or dashicons-dashboard, dashicons-lock, etc.
        31  // Right after Business Card at 30
    );
} // <-- This was missing

/**
 * Render the settings page content.
 */
function bbab_po_render_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'bbab-portal-settings-group' );
            do_settings_sections( 'bbab-portal-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings and fields.
 */
function bbab_po_register_settings() {
    register_setting(
        'bbab-portal-settings-group',
        'bbab_portal_settings',  // <-- Comma was missing here
        [
            'type'              => 'array',
            'sanitize_callback' => 'bbab_po_sanitize_settings',
            'default'           => [],
        ]
    );

    add_settings_section(
        'bbab-portal-main-section',
        'WPForms Integration',
        'bbab_po_main_section_callback',
        'bbab-portal-settings'
    );

    add_settings_field(
        'bbab-portal-form-id',
        'Portfolio Entry Form ID',
        'bbab_po_form_id_field_callback',
        'bbab-portal-settings',
        'bbab-portal-main-section'
    );
    // Add the Portal Page field - NEW
    add_settings_field(
        'bbab-portal-page-id',
        'Protected Portal Page',
        'bbab_po_portal_page_field_callback',
        'bbab-portal-settings',
        'bbab-portal-main-section'
    );
} // <-- This was missing

/**
 * Section description callback.
 */
function bbab_po_main_section_callback() {
    echo '<p>Enter the WPForms form ID for the portfolio entry form.</p>';
}

/**
 * Field callback for Form ID.
 */
function bbab_po_form_id_field_callback() {
    $options = get_option( 'bbab_portal_settings', [] );
    $form_id = isset( $options['form_id'] ) ? $options['form_id'] : '';
    ?>
    <input
        type="number"
        name="bbab_portal_settings[form_id]"
        value="<?php echo esc_attr( $form_id ); ?>"
        class="regular-text"
        min="0"
    >
    <p class="description">Find this in WPForms → All Forms → look at the ID column.</p>
    <?php
}

/**
 * Field callback for Portal Page dropdown.
 * 
 * Uses wp_dropdown_pages() - a WordPress helper that creates
 * a <select> element populated with all published pages.
 */
function bbab_po_portal_page_field_callback() {
    $options = get_option( 'bbab_portal_settings', [] );
    $page_id = isset( $options['portal_page_id'] ) ? $options['portal_page_id'] : 0;
    
    wp_dropdown_pages( [
        'name'              => 'bbab_portal_settings[portal_page_id]',
        'id'                => 'bbab_portal_page_id',
        'selected'          => $page_id,
        'show_option_none'  => '— Select a Page —',
        'option_none_value' => 0,
    ] );
    ?>
    <p class="description">
        Select the page to protect. All child pages will also be protected automatically.
    </p>
    <?php
}

/**
 * Sanitize settings.
 */
function bbab_po_sanitize_settings( $input ) {
    $sanitized = [];
    if ( isset( $input['form_id'] ) ) {
        $sanitized['form_id'] = absint( $input['form_id'] ); // absint for integer IDs
    }

     // NEW: Sanitize portal page ID
    if ( isset( $input['portal_page_id'] ) ) {
        $sanitized['portal_page_id'] = absint( $input['portal_page_id'] );
    }
    return $sanitized;
}

/**
 * Helper function to get portal settings
 */
function bbab_portal_get_settings() {
    $defaults = [
        'form_id'        => 0,
        'portal_page_id' => 0,  // NEW: The page ID to protect
    ];
    return wp_parse_args( get_option( 'bbab_portal_settings', [] ), $defaults );
}

/**
 * Reformat date field when saving portfolio entry meta
 */
function bbab_portal_fix_date_format( $meta_id, $post_id, $meta_key, $meta_value ) {
    // Only target our date field
    if ( $meta_key !== '_bbab_date_completed' ) {
        return;
    }
    
    // Only for portfolio entries
    if ( get_post_type( $post_id ) !== 'bbab_portfolio' ) {
        return;
    }
    
    // If it's in mm/dd/yyyy format, convert to Y-m-d
    if ( $meta_value && strpos( $meta_value, '/' ) !== false ) {
        $timestamp = strtotime( $meta_value );
        if ( $timestamp ) {
            $new_format = date( 'Y-m-d', $timestamp );
            // Remove this action temporarily to prevent infinite loop
            remove_action( 'added_post_meta', 'bbab_portal_fix_date_format', 10 );
            update_post_meta( $post_id, '_bbab_date_completed', $new_format );
            add_action( 'added_post_meta', 'bbab_portal_fix_date_format', 10, 4 );
        }
    }
}
add_action( 'added_post_meta', 'bbab_portal_fix_date_format', 10, 4 );

/**
 * Enqueue portal styles on frontend
 */
function bbab_portal_enqueue_styles() {
    // Load on all frontend pages for now - we can optimize later
    if ( ! is_admin() ) {
        wp_enqueue_style(
            'bbab-portal-styles',
            BBAB_PORTAL_URL . 'assets/css/portal.css',
            [],
            BBAB_PORTAL_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'bbab_portal_enqueue_styles' );

// Hook the settings page and registration
add_action( 'admin_menu', 'bbab_po_add_settings_page' );
add_action( 'admin_init', 'bbab_po_register_settings' );
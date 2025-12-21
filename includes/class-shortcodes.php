<?php
/**
 * Shortcodes for the BBAB Portal plugin.
 * 
 * Handles the following shortcodes:
 * [bbab_portal_links]  - Quick links to useful places
 * [bbab_portal_drafts] - Draft portfolio entries
 * [bbab_portal_recent] - Recently modified entries
 * [bbab_portal_stats]  - Portfolio statistics
 * 
 * @package BBAB_Portal
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the shortcodes
 */
function bbab_portal_register_shortcodes() {
    add_shortcode( 'bbab_portal_links', 'bbab_portal_links_shortcode' );
    add_shortcode( 'bbab_portal_drafts', 'bbab_portal_drafts_shortcode' );
    add_shortcode( 'bbab_portal_recent', 'bbab_portal_recent_shortcode' );
    add_shortcode( 'bbab_portal_stats', 'bbab_portal_stats_shortcode' );
}
add_action( 'init', 'bbab_portal_register_shortcodes' );

/**
 * Quick Links Shortcode
 * 
 * Displays a list of hardcoded useful links.
 * These are places Brad frequently needs to access.
 *
 * @return string HTML output
 */
function bbab_portal_links_shortcode() {
    // Define the links - easy to add/remove/modify
    $links = [
        'Add Portfolio'    => home_url( '/brads-portal/add-portfolio/' ),
        'All Portfolio'    => admin_url( 'edit.php?post_type=bbab_portfolio' ),
        'GitHub'           => 'https://github.com/Brads-Bits-and-Bytes',
        'SiteGround'       => 'https://my.siteground.com',
        'Google Analytics' => 'https://analytics.google.com',
        'WP Admin'         => admin_url(),
    ];

    ob_start();
    ?>
    <div class="bbab-portal-card bbab-portal-links">
        <h3>Quick Links</h3>
        <ul>
            <?php foreach ( $links as $label => $url ) : ?>
                <li>
                    <a href="<?php echo esc_url( $url ); ?>" <?php echo strpos( $url, home_url() ) === false ? 'target="_blank" rel="noopener"' : ''; ?>>
                        <?php echo esc_html( $label ); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Draft Entries Shortcode
 * 
 * Displays portfolio entries that are still in draft status.
 * Helps Brad see what needs to be finished/published.
 *
 * @return string HTML output
 */
function bbab_portal_drafts_shortcode() {
    // Check if Portfolio plugin is active
    if ( ! post_type_exists( 'bbab_portfolio' ) ) {
        return '<div class="bbab-portal-card bbab-portal-drafts"><p>Portfolio plugin not active.</p></div>';
    }

    // Query for draft portfolio entries
    $drafts = get_posts( [
        'post_type'      => 'bbab_portfolio',
        'post_status'    => 'draft',
        'posts_per_page' => 10,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ] );

    ob_start();
    ?>
    <div class="bbab-portal-card bbab-portal-drafts">
        <h3>Draft Entries</h3>
        <?php if ( empty( $drafts ) ) : ?>
            <p class="bbab-empty-state">No drafts - you're all caught up! 🎉</p>
        <?php else : ?>
            <ul>
                <?php foreach ( $drafts as $draft ) : ?>
                    <li>
                        <strong><?php echo esc_html( $draft->post_title ?: '(Untitled)' ); ?></strong>
                        <span class="bbab-meta">
                            Created: <?php echo esc_html( get_the_date( 'M j, Y', $draft ) ); ?>
                        </span>
                        <span class="bbab-actions">
                            <a href="<?php echo esc_url( get_edit_post_link( $draft->ID ) ); ?>">Edit</a>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Recent Entries Shortcode
 * 
 * Displays the most recently modified portfolio entries.
 * Shows both published and draft entries.
 *
 * @return string HTML output
 */
function bbab_portal_recent_shortcode() {
    // Check if Portfolio plugin is active
    if ( ! post_type_exists( 'bbab_portfolio' ) ) {
        return '<div class="bbab-portal-card bbab-portal-recent"><p>Portfolio plugin not active.</p></div>';
    }

    // Query for recently modified entries (any status)
    $recent = get_posts( [
        'post_type'      => 'bbab_portfolio',
        'post_status'    => [ 'publish', 'draft', 'pending' ],
        'posts_per_page' => 5,
        'orderby'        => 'modified',
        'order'          => 'DESC',
    ] );

    ob_start();
    ?>
    <div class="bbab-portal-card bbab-portal-recent">
        <h3>Recent Activity</h3>
        <?php if ( empty( $recent ) ) : ?>
            <p class="bbab-empty-state">No portfolio entries yet.</p>
        <?php else : ?>
            <ul>
                <?php foreach ( $recent as $entry ) : ?>
                    <li>
                        <strong><?php echo esc_html( $entry->post_title ?: '(Untitled)' ); ?></strong>
                        <?php if ( $entry->post_status !== 'publish' ) : ?>
                            <span class="bbab-status bbab-status-<?php echo esc_attr( $entry->post_status ); ?>">
                                <?php echo esc_html( ucfirst( $entry->post_status ) ); ?>
                            </span>
                        <?php endif; ?>
                        <span class="bbab-meta">
                            Modified: <?php echo esc_html( get_the_modified_date( 'M j, Y', $entry ) ); ?>
                        </span>
                        <span class="bbab-actions">
                            <a href="<?php echo esc_url( get_edit_post_link( $entry->ID ) ); ?>">Edit</a>
                            <?php if ( $entry->post_status === 'publish' ) : ?>
                                | <a href="<?php echo esc_url( get_permalink( $entry->ID ) ); ?>">View</a>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Portfolio Stats Shortcode
 * 
 * Displays statistics about portfolio entries:
 * - Total count
 * - Published vs draft
 * - Counts by project status meta field
 * - Last updated date
 *
 * @return string HTML output
 */
function bbab_portal_stats_shortcode() {
    // Check if Portfolio plugin is active
    if ( ! post_type_exists( 'bbab_portfolio' ) ) {
        return '<div class="bbab-portal-card bbab-portal-stats"><p>Portfolio plugin not active.</p></div>';
    }

    // Get counts by post status
    $counts = wp_count_posts( 'bbab_portfolio' );
    $published = isset( $counts->publish ) ? $counts->publish : 0;
    $drafts = isset( $counts->draft ) ? $counts->draft : 0;
    $total = $published + $drafts;

    // Count by project status meta field
    // These match the values from the Portfolio plugin
    $status_counts = [];
    $statuses = [
        'live'             => 'Live',
        'completed_offline'=> 'Offline',
        'development_only' => 'Dev Only',
    ];

    foreach ( $statuses as $status_value => $status_label ) {
        $query = new WP_Query( [
            'post_type'      => 'bbab_portfolio',
            'post_status'    => 'publish',
            'meta_key'       => '_bbab_project_status',
            'meta_value'     => $status_value,
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );
        $status_counts[ $status_label ] = $query->found_posts;
        wp_reset_postdata();
    }

    // Get most recently modified entry
    $recent = get_posts( [
        'post_type'      => 'bbab_portfolio',
        'posts_per_page' => 1,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'post_status'    => [ 'publish', 'draft' ],
    ] );
    $last_updated = ! empty( $recent ) ? get_the_modified_date( 'M j, Y', $recent[0] ) : 'Never';

    ob_start();
    ?>
    <div class="bbab-portal-card bbab-portal-stats">
        <h3>Portfolio Stats</h3>
        <div class="bbab-stats-grid">
            <div class="bbab-stat">
                <span class="bbab-stat-number"><?php echo esc_html( $total ); ?></span>
                <span class="bbab-stat-label">Total Entries</span>
            </div>
            <div class="bbab-stat">
                <span class="bbab-stat-number"><?php echo esc_html( $published ); ?></span>
                <span class="bbab-stat-label">Published</span>
            </div>
            <div class="bbab-stat">
                <span class="bbab-stat-number"><?php echo esc_html( $drafts ); ?></span>
                <span class="bbab-stat-label">Drafts</span>
            </div>
        </div>
        
        <h4>By Project Status</h4>
        <ul class="bbab-status-list">
            <?php foreach ( $status_counts as $label => $count ) : ?>
                <li>
                    <span class="bbab-status-label"><?php echo esc_html( $label ); ?>:</span>
                    <span class="bbab-status-count"><?php echo esc_html( $count ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="bbab-last-updated">
            <strong>Last Updated:</strong> <?php echo esc_html( $last_updated ); ?>
        </p>
    </div>
    <?php
    return ob_get_clean();
}
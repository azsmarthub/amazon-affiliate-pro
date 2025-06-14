<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check user capabilities
if (!current_user_can('activate_plugins')) {
    return;
}

// Check if it's the correct plugin
if ($_REQUEST['plugin'] !== 'amazon-affiliate-pro/amazon-affiliate-pro.php') {
    return;
}

// Security check
check_admin_referer('bulk-plugins');

/**
 * Clean up function
 */
function aapi_uninstall_cleanup() {
    global $wpdb;
    
    // Get option to check if data should be deleted
    $delete_data = get_option('aapi_delete_data_on_uninstall', false);
    
    if (!$delete_data) {
        return;
    }
    
    // Delete custom post type posts
    $posts = get_posts(array(
        'post_type' => 'amazon_product',
        'numberposts' => -1,
        'post_status' => 'any',
    ));
    
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
    
    // Delete custom taxonomies terms
    $taxonomies = array('amazon_product_cat', 'amazon_product_tag', 'amazon_product_brand');
    
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }
    
    // Drop custom tables
    $tables = array(
        $wpdb->prefix . 'aapi_products',
        $wpdb->prefix . 'aapi_api_logs',
        $wpdb->prefix . 'aapi_clicks',
        $wpdb->prefix . 'aapi_price_history',
        $wpdb->prefix . 'aapi_alerts',
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
    
    // Delete options
    $options = array(
        'aapi_version',
        'aapi_db_version',
        'aapi_settings',
        'aapi_api_settings',
        'aapi_display_settings',
        'aapi_tracking_settings',
        'aapi_last_deactivation',
        'aapi_delete_data_on_uninstall',
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Delete transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_aapi_%' 
         OR option_name LIKE '_transient_timeout_aapi_%'"
    );
    
    // Delete user meta
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} 
         WHERE meta_key LIKE 'aapi_%'"
    );
    
    // Delete post meta
    $wpdb->query(
        "DELETE FROM {$wpdb->postmeta} 
         WHERE meta_key LIKE '_aapi_%'"
    );
    
    // Remove capabilities
    $capabilities = array(
        'aapi_manage_products',
        'aapi_import_products',
        'aapi_view_analytics',
        'aapi_manage_settings',
        'aapi_export_data',
        'aapi_manage_api_keys',
        'aapi_view_api_logs',
        'aapi_manage_advanced_settings',
    );
    
    $roles = get_editable_roles();
    
    foreach ($roles as $role_name => $role_info) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
    
    // Clear scheduled cron events
    $cron_events = array(
        'aapi_update_products',
        'aapi_cleanup_logs',
        'aapi_check_prices',
        'aapi_aggregate_analytics',
    );
    
    foreach ($cron_events as $event) {
        $timestamp = wp_next_scheduled($event);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $event);
        }
        wp_clear_scheduled_hook($event);
    }
    
    // Delete upload directory
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/aapi-cache';
    
    if (file_exists($plugin_upload_dir)) {
        aapi_delete_directory($plugin_upload_dir);
    }
    
    // Clear any cached data
    wp_cache_flush();
}

/**
 * Recursively delete a directory
 *
 * @param string $dir Directory path
 * @return bool
 */
function aapi_delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!aapi_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// Check if multisite
if (is_multisite()) {
    // Get all blog ids
    $blog_ids = get_sites(array('fields' => 'ids'));
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        aapi_uninstall_cleanup();
        restore_current_blog();
    }
} else {
    // Single site
    aapi_uninstall_cleanup();
}
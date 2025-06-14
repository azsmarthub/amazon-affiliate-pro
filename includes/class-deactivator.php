<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes
 */

namespace AAPI;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes
 * @author     Your Name <email@example.com>
 */
class Deactivator {

    /**
     * Plugin deactivation handler.
     *
     * Clear scheduled events and flush rewrite rules.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Unschedule cron events
        self::unschedule_events();
        
        // Clear transients
        self::clear_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        self::log_deactivation();
    }

    /**
     * Unschedule all plugin cron events.
     *
     * @since    1.0.0
     */
    private static function unschedule_events() {
        // Get all scheduled events
        $events = array(
            'aapi_update_products',
            'aapi_cleanup_logs',
            'aapi_check_prices',
            'aapi_aggregate_analytics',
        );
        
        // Unschedule each event
        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
            
            // Clear all scheduled hooks in case there are multiple
            wp_clear_scheduled_hook($event);
        }
    }

    /**
     * Clear plugin transients.
     *
     * @since    1.0.0
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Delete transients with our prefix
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_aapi_%' 
             OR option_name LIKE '_transient_timeout_aapi_%'"
        );
        
        // Delete site transients for multisite
        if (is_multisite()) {
            $wpdb->query(
                "DELETE FROM {$wpdb->sitemeta} 
                 WHERE meta_key LIKE '_site_transient_aapi_%' 
                 OR meta_key LIKE '_site_transient_timeout_aapi_%'"
            );
        }
        
        // Clear object cache
        wp_cache_flush();
    }

    /**
     * Log plugin deactivation.
     *
     * @since    1.0.0
     */
    private static function log_deactivation() {
        // Get current user
        $current_user = wp_get_current_user();
        $user_info = $current_user->ID ? $current_user->user_login : 'System';
        
        // Log deactivation info
        $log_data = array(
            'event' => 'plugin_deactivated',
            'version' => AAPI_VERSION,
            'user' => $user_info,
            'timestamp' => current_time('mysql'),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
        );
        
        // Store in options for potential debugging
        update_option('aapi_last_deactivation', $log_data);
    }
}
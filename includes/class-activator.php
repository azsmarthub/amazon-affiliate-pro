<?php
/**
 * Fired during plugin activation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes
 */

namespace AAPI;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes
 * @author     Your Name <email@example.com>
 */
class Activator {

    /**
     * Plugin activation handler.
     *
     * Create database tables, set default options, and schedule cron events.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(AAPI_PLUGIN_BASENAME);
            wp_die(
                esc_html__('Amazon Affiliate Pro requires PHP 7.4 or higher.', 'aapi'),
                esc_html__('Plugin Activation Error', 'aapi'),
                array('back_link' => true)
            );
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            deactivate_plugins(AAPI_PLUGIN_BASENAME);
            wp_die(
                esc_html__('Amazon Affiliate Pro requires WordPress 5.8 or higher.', 'aapi'),
                esc_html__('Plugin Activation Error', 'aapi'),
                array('back_link' => true)
            );
        }

        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directories
        self::create_directories();
        
        // Schedule cron events
        self::schedule_cron_events();
        
        // Add capabilities
        self::add_capabilities();
        
        // Clear rewrite rules
        flush_rewrite_rules();
        
        // Set activation transient
        set_transient('aapi_activated', true, 5);
    }

    /**
     * Create plugin database tables.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Products table
        $table_products = $wpdb->prefix . 'aapi_products';
        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            asin varchar(20) NOT NULL,
            marketplace varchar(5) DEFAULT 'US',
            title text,
            price decimal(10,2),
            currency varchar(3),
            sale_price decimal(10,2),
            availability varchar(50),
            rating decimal(2,1),
            review_count int(11),
            prime_eligible tinyint(1) DEFAULT 0,
            image_url text,
            gallery_images longtext,
            features longtext,
            variations longtext,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            update_status varchar(20) DEFAULT 'success',
            PRIMARY KEY (id),
            UNIQUE KEY asin_market (asin, marketplace),
            KEY post_id (post_id),
            KEY last_updated (last_updated)
        ) $charset_collate;";
        
        dbDelta($sql_products);
        
        // API logs table
        $table_api_logs = $wpdb->prefix . 'aapi_api_logs';
        $sql_api_logs = "CREATE TABLE IF NOT EXISTS $table_api_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            api_provider varchar(50) NOT NULL,
            endpoint varchar(255),
            request_type varchar(20),
            request_data longtext,
            response_code int(3),
            response_message text,
            credits_used int(11) DEFAULT 1,
            execution_time float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY api_provider (api_provider),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_api_logs);
        
        // Click tracking table
        $table_clicks = $wpdb->prefix . 'aapi_clicks';
        $sql_clicks = "CREATE TABLE IF NOT EXISTS $table_clicks (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED,
            ip_address varchar(45),
            user_agent text,
            referrer text,
            click_position varchar(50),
            device_type varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql_clicks);
        
        // Price history table
        $table_price_history = $wpdb->prefix . 'aapi_price_history';
        $sql_price_history = "CREATE TABLE IF NOT EXISTS $table_price_history (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id bigint(20) UNSIGNED NOT NULL,
            price decimal(10,2) NOT NULL,
            currency varchar(3),
            recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id_date (product_id, recorded_at)
        ) $charset_collate;";
        
        dbDelta($sql_price_history);
        
        // User alerts table
        $table_alerts = $wpdb->prefix . 'aapi_alerts';
        $sql_alerts = "CREATE TABLE IF NOT EXISTS $table_alerts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_email varchar(255) NOT NULL,
            product_id bigint(20) UNSIGNED NOT NULL,
            alert_type varchar(20) DEFAULT 'price_drop',
            target_price decimal(10,2),
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_email (user_email),
            KEY product_status (product_id, status)
        ) $charset_collate;";
        
        dbDelta($sql_alerts);
        
        // Update database version
        update_option('aapi_db_version', AAPI_DB_VERSION);
    }

    /**
     * Set default plugin options.
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        // General settings
        add_option('aapi_settings', array(
            'affiliate_tag' => '',
            'default_marketplace' => 'US',
            'cache_duration' => 3600,
            'enable_tracking' => true,
            'enable_schema' => true,
            'enable_nofollow' => true,
            'link_cloaking' => true,
            'cloak_prefix' => 'go',
            'auto_update' => true,
            'update_frequency' => 'daily',
        ));
        
        // API settings
        add_option('aapi_api_settings', array(
            'primary_api' => 'paapi',
            'fallback_api' => '',
            'paapi_access_key' => '',
            'paapi_secret_key' => '',
            'paapi_partner_tag' => '',
            'scraper_api_key' => '',
            'api_timeout' => 30,
            'max_retries' => 3,
        ));
        
        // Display settings
        add_option('aapi_display_settings', array(
            'default_template' => 'default',
            'show_prime_badge' => true,
            'show_ratings' => true,
            'show_price' => true,
            'show_savings' => true,
            'button_text' => __('View on Amazon', 'aapi'),
            'out_of_stock_text' => __('Currently Unavailable', 'aapi'),
            'mobile_responsive' => true,
        ));
        
        // Tracking settings
        add_option('aapi_tracking_settings', array(
            'enable_click_tracking' => true,
            'track_user_id' => true,
            'track_ip_address' => true,
            'anonymize_ip' => true,
            'track_referrer' => true,
            'enable_heatmap' => false,
            'data_retention_days' => 90,
        ));
    }

    /**
     * Create necessary directories.
     *
     * @since    1.0.0
     */
    private static function create_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/aapi-cache';
        
        // Create cache directory
        if (!file_exists($plugin_upload_dir)) {
            wp_mkdir_p($plugin_upload_dir);
            
            // Add .htaccess to protect directory
            $htaccess_file = $plugin_upload_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "Options -Indexes\nDeny from all";
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Add index.php for extra protection
            $index_file = $plugin_upload_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
            }
        }
        
        // Create logs directory
        $logs_dir = AAPI_PLUGIN_DIR . 'logs';
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
            
            // Add .htaccess to protect logs
            $htaccess_file = $logs_dir . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "Options -Indexes\nDeny from all";
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Add index.php
            $index_file = $logs_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden');
            }
        }
    }

    /**
     * Schedule cron events.
     *
     * @since    1.0.0
     */
    private static function schedule_cron_events() {
        // Product update cron
        if (!wp_next_scheduled('aapi_update_products')) {
            wp_schedule_event(time(), 'daily', 'aapi_update_products');
        }
        
        // Cleanup old logs cron
        if (!wp_next_scheduled('aapi_cleanup_logs')) {
            wp_schedule_event(time(), 'weekly', 'aapi_cleanup_logs');
        }
        
        // Price check cron
        if (!wp_next_scheduled('aapi_check_prices')) {
            wp_schedule_event(time(), 'twicedaily', 'aapi_check_prices');
        }
        
        // Analytics aggregation cron
        if (!wp_next_scheduled('aapi_aggregate_analytics')) {
            wp_schedule_event(time(), 'daily', 'aapi_aggregate_analytics');
        }
    }

    /**
     * Add plugin capabilities to user roles.
     *
     * @since    1.0.0
     */
    private static function add_capabilities() {
        $roles = array('administrator', 'editor');
        
        $capabilities = array(
            'aapi_manage_products',
            'aapi_import_products',
            'aapi_view_analytics',
            'aapi_manage_settings',
            'aapi_export_data',
        );
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->add_cap($cap);
                }
            }
        }
        
        // Admin-only capabilities
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_caps = array(
                'aapi_manage_api_keys',
                'aapi_view_api_logs',
                'aapi_manage_advanced_settings',
            );
            
            foreach ($admin_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }
}
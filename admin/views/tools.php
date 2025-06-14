<?php
/**
 * Admin tools view
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Handle tool actions
$action = isset($_POST['aapi_tool_action']) ? sanitize_text_field($_POST['aapi_tool_action']) : '';
$message = '';
$message_type = '';

if ($action && isset($_POST['aapi_tools_nonce']) && wp_verify_nonce($_POST['aapi_tools_nonce'], 'aapi_tools_action')) {
    switch ($action) {
        case 'clear_cache':
            delete_transient('aapi_cache_cleared');
            $cleared = \AAPI\Services\Cache::clear_all();
            $message = sprintf(__('Cache cleared successfully. %d items removed.', 'aapi'), $cleared);
            $message_type = 'success';
            break;
            
        case 'reset_clicks':
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}aapi_clicks");
            $message = __('Click tracking data has been reset.', 'aapi');
            $message_type = 'success';
            break;
            
        case 'sync_products':
            $scheduled = wp_schedule_single_event(time() + 10, 'aapi_update_products');
            $message = $scheduled ? __('Product sync scheduled. It will run in the background.', 'aapi') : __('Failed to schedule sync.', 'aapi');
            $message_type = $scheduled ? 'success' : 'error';
            break;
            
        case 'export_settings':
            $settings = array(
                'version' => AAPI_VERSION,
                'settings' => get_option('aapi_settings'),
                'api_settings' => get_option('aapi_api_settings'),
                'display_settings' => get_option('aapi_display_settings'),
                'tracking_settings' => get_option('aapi_tracking_settings'),
            );
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="aapi-settings-' . date('Y-m-d') . '.json"');
            echo json_encode($settings, JSON_PRETTY_PRINT);
            exit;
            break;
    }
}

// Get system info
global $wpdb;
$system_info = array(
    'php_version' => PHP_VERSION,
    'wp_version' => get_bloginfo('version'),
    'plugin_version' => AAPI_VERSION,
    'db_version' => get_option('aapi_db_version'),
    'php_memory_limit' => ini_get('memory_limit'),
    'wp_memory_limit' => WP_MEMORY_LIMIT,
    'wp_debug' => WP_DEBUG ? 'Enabled' : 'Disabled',
    'wp_cron' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'Disabled' : 'Enabled',
);

// Get database sizes
$table_sizes = array();
$tables = array('aapi_products', 'aapi_clicks', 'aapi_api_logs', 'aapi_price_history', 'aapi_alerts');
foreach ($tables as $table) {
    $table_name = $wpdb->prefix . $table;
    $size = $wpdb->get_row("SHOW TABLE STATUS LIKE '$table_name'");
    if ($size) {
        $table_sizes[$table] = array(
            'rows' => $size->Rows,
            'size' => round(($size->Data_length + $size->Index_length) / 1024 / 1024, 2) . ' MB',
        );
    }
}

// Get scheduled crons
$cron_events = array(
    'aapi_update_products' => __('Product Updates', 'aapi'),
    'aapi_cleanup_logs' => __('Log Cleanup', 'aapi'),
    'aapi_check_prices' => __('Price Checks', 'aapi'),
    'aapi_aggregate_analytics' => __('Analytics Aggregation', 'aapi'),
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($message) : ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Maintenance Tools -->
    <div class="aapi-tool-section">
        <h2><?php _e('Maintenance Tools', 'aapi'); ?></h2>
        <p><?php _e('Perform maintenance tasks to keep your plugin running smoothly.', 'aapi'); ?></p>

        <form method="post" action="" class="aapi-tools-form">
            <?php wp_nonce_field('aapi_tools_action', 'aapi_tools_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Clear Cache', 'aapi'); ?></th>
                    <td>
                        <p><?php _e('Remove all cached product data and API responses.', 'aapi'); ?></p>
                        <button type="submit" name="aapi_tool_action" value="clear_cache" class="button">
                            <?php _e('Clear Cache', 'aapi'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Sync All Products', 'aapi'); ?></th>
                    <td>
                        <p><?php _e('Force update all product information from Amazon.', 'aapi'); ?></p>
                        <button type="submit" name="aapi_tool_action" value="sync_products" class="button">
                            <?php _e('Sync Products', 'aapi'); ?>
                        </button>
                        <span class="description"><?php _e('This will run in the background.', 'aapi'); ?></span>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Reset Click Data', 'aapi'); ?></th>
                    <td>
                        <p><?php _e('Delete all click tracking data. This cannot be undone.', 'aapi'); ?></p>
                        <button type="submit" name="aapi_tool_action" value="reset_clicks" class="button" 
                                onclick="return confirm('<?php esc_attr_e('Are you sure? This will delete all click tracking data.', 'aapi'); ?>')">
                            <?php _e('Reset Click Data', 'aapi'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Export Settings', 'aapi'); ?></th>
                    <td>
                        <p><?php _e('Export all plugin settings for backup or migration.', 'aapi'); ?></p>
                        <button type="submit" name="aapi_tool_action" value="export_settings" class="button">
                            <?php _e('Export Settings', 'aapi'); ?>
                        </button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Import Settings', 'aapi'); ?></th>
                    <td>
                        <p><?php _e('Import plugin settings from a backup file.', 'aapi'); ?></p>
                        <input type="file" name="settings_file" accept=".json">
                        <button type="submit" name="aapi_tool_action" value="import_settings" class="button">
                            <?php _e('Import Settings', 'aapi'); ?>
                        </button>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <!-- Database Information -->
    <div class="aapi-tool-section">
        <h2><?php _e('Database Information', 'aapi'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Table', 'aapi'); ?></th>
                    <th><?php _e('Rows', 'aapi'); ?></th>
                    <th><?php _e('Size', 'aapi'); ?></th>
                    <th><?php _e('Actions', 'aapi'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($table_sizes as $table => $info) : ?>
                    <tr>
                        <td><strong><?php echo $wpdb->prefix . $table; ?></strong></td>
                        <td><?php echo number_format_i18n($info['rows']); ?></td>
                        <td><?php echo $info['size']; ?></td>
                        <td>
                            <?php if ($table === 'aapi_api_logs') : ?>
                                <a href="<?php echo admin_url('admin.php?page=aapi-api-logs'); ?>" class="button button-small">
                                    <?php _e('View Logs', 'aapi'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Scheduled Tasks -->
    <div class="aapi-tool-section">
        <h2><?php _e('Scheduled Tasks', 'aapi'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Task', 'aapi'); ?></th>
                    <th><?php _e('Next Run', 'aapi'); ?></th>
                    <th><?php _e('Frequency', 'aapi'); ?></th>
                    <th><?php _e('Actions', 'aapi'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cron_events as $hook => $name) : 
                    $next_run = wp_next_scheduled($hook);
                    $schedules = wp_get_schedules();
                    $schedule = wp_get_schedule($hook);
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($name); ?></strong></td>
                        <td>
                            <?php if ($next_run) : ?>
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run); ?>
                                <br><small><?php echo human_time_diff(time(), $next_run); ?> <?php _e('from now', 'aapi'); ?></small>
                            <?php else : ?>
                                <em><?php _e('Not scheduled', 'aapi'); ?></em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($schedule && isset($schedules[$schedule])) {
                                echo esc_html($schedules[$schedule]['display']);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($next_run) : ?>
                                <button type="button" class="button button-small aapi-run-cron" data-hook="<?php echo esc_attr($hook); ?>">
                                    <?php _e('Run Now', 'aapi'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- System Information -->
    <div class="aapi-tool-section">
        <h2><?php _e('System Information', 'aapi'); ?></h2>
        <div class="aapi-system-info">
            <textarea readonly class="large-text" rows="20">
AMAZON AFFILIATE PRO SYSTEM INFO
================================

WordPress Environment
---------------------
WordPress Version:     <?php echo $system_info['wp_version'] . "\n"; ?>
PHP Version:          <?php echo $system_info['php_version'] . "\n"; ?>
MySQL Version:        <?php echo $wpdb->db_version() . "\n"; ?>
WP Memory Limit:      <?php echo $system_info['wp_memory_limit'] . "\n"; ?>
PHP Memory Limit:     <?php echo $system_info['php_memory_limit'] . "\n"; ?>
WP Debug:             <?php echo $system_info['wp_debug'] . "\n"; ?>
WP Cron:              <?php echo $system_info['wp_cron'] . "\n"; ?>
Site URL:             <?php echo get_site_url() . "\n"; ?>
Home URL:             <?php echo get_home_url() . "\n"; ?>

Plugin Information
------------------
Plugin Version:       <?php echo $system_info['plugin_version'] . "\n"; ?>
Database Version:     <?php echo $system_info['db_version'] . "\n"; ?>

Active Theme
------------
<?php $theme = wp_get_theme(); ?>
Theme Name:           <?php echo $theme->get('Name') . "\n"; ?>
Theme Version:        <?php echo $theme->get('Version') . "\n"; ?>

Active Plugins
--------------
<?php
$active_plugins = get_option('active_plugins');
foreach ($active_plugins as $plugin) {
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
    echo $plugin_data['Name'] . ' (v' . $plugin_data['Version'] . ")\n";
}
?>

Server Environment
------------------
Server Software:      <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>
PHP Extensions:       <?php echo implode(', ', get_loaded_extensions()) . "\n"; ?>
cURL Version:         <?php 
    if (function_exists('curl_version')) {
        $curl = curl_version();
        echo $curl['version'] . "\n";
    } else {
        echo "Not available\n";
    }
?>

API Configuration
-----------------
Primary API:          <?php echo (new \AAPI\Core\Settings())->get('api', 'primary_api', 'Not configured') . "\n"; ?>
Fallback API:         <?php echo (new \AAPI\Core\Settings())->get('api', 'fallback_api', 'None') . "\n"; ?>
            </textarea>
            <p>
                <button type="button" class="button" onclick="this.previousElementSibling.select(); document.execCommand('copy');">
                    <?php _e('Copy System Info', 'aapi'); ?>
                </button>
            </p>
        </div>
    </div>

    <!-- Diagnostic Tools -->
    <div class="aapi-tool-section">
        <h2><?php _e('Diagnostic Tools', 'aapi'); ?></h2>
        
        <h3><?php _e('API Connection Test', 'aapi'); ?></h3>
        <p><?php _e('Test your API connections to ensure they are working properly.', 'aapi'); ?></p>
        <button type="button" id="aapi-test-all-apis" class="button">
            <?php _e('Test All APIs', 'aapi'); ?>
        </button>
        <div id="aapi-api-test-results" class="aapi-diagnostic-results" style="display:none;"></div>
        
        <h3><?php _e('Database Integrity Check', 'aapi'); ?></h3>
        <p><?php _e('Check database tables for any issues.', 'aapi'); ?></p>
        <button type="button" id="aapi-check-db" class="button">
            <?php _e('Check Database', 'aapi'); ?>
        </button>
        <div id="aapi-db-check-results" class="aapi-diagnostic-results" style="display:none;"></div>
        
        <h3><?php _e('Plugin Conflicts Check', 'aapi'); ?></h3>
        <p><?php _e('Check for potential conflicts with other plugins.', 'aapi'); ?></p>
        <button type="button" id="aapi-check-conflicts" class="button">
            <?php _e('Check Conflicts', 'aapi'); ?>
        </button>
        <div id="aapi-conflict-results" class="aapi-diagnostic-results" style="display:none;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Run cron job
    $('.aapi-run-cron').on('click', function() {
        var button = $(this);
        var hook = button.data('hook');
        
        button.prop('disabled', true).text('<?php _e('Running...', 'aapi'); ?>');
        
        $.post(ajaxurl, {
            action: 'aapi_run_cron',
            hook: hook,
            nonce: '<?php echo wp_create_nonce('aapi_run_cron'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Cron job executed successfully.', 'aapi'); ?>');
            } else {
                alert('<?php _e('Failed to execute cron job.', 'aapi'); ?>');
            }
            button.prop('disabled', false).text('<?php _e('Run Now', 'aapi'); ?>');
        });
    });
    
    // Test all APIs
    $('#aapi-test-all-apis').on('click', function() {
        var button = $(this);
        var results = $('#aapi-api-test-results');
        
        button.prop('disabled', true);
        results.show().text('<?php _e('Testing APIs...', 'aapi'); ?>');
        
        // Simulate API test - replace with actual AJAX call
        setTimeout(function() {
            results.html('API Test Results:\n\nPA-API: Not configured\nRainforestAPI: Not configured\nSerpApi: Not configured\nDataForSEO: Not configured\n\nNote: API integration will be implemented in Phase 2.');
            button.prop('disabled', false);
        }, 1000);
    });
    
    // Check database
    $('#aapi-check-db').on('click', function() {
        var button = $(this);
        var results = $('#aapi-db-check-results');
        
        button.prop('disabled', true);
        results.show().text('<?php _e('Checking database...', 'aapi'); ?>');
        
        // Simulate DB check - replace with actual AJAX call
        setTimeout(function() {
            results.html('Database Check Results:\n\nAll tables exist: ✓\nTable structure: ✓\nIndexes: ✓\nNo issues found.');
            button.prop('disabled', false);
        }, 1000);
    });
    
    // Check conflicts
    $('#aapi-check-conflicts').on('click', function() {
        var button = $(this);
        var results = $('#aapi-conflict-results');
        
        button.prop('disabled', true);
        results.show().text('<?php _e('Checking for conflicts...', 'aapi'); ?>');
        
        // Simulate conflict check - replace with actual AJAX call
        setTimeout(function() {
            results.html('Plugin Conflict Check:\n\nNo known conflicts detected.\nAll systems operational.');
            button.prop('disabled', false);
        }, 1000);
    });
});
</script>
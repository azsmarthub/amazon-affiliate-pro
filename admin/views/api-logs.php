<?php
/**
 * Admin API logs view
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

// Get filter parameters
$filter_provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$filter_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Pagination
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;
$offset = ($paged - 1) * $per_page;

// Build query
global $wpdb;
$table_name = $wpdb->prefix . 'aapi_api_logs';
$where_clauses = array('1=1');
$where_values = array();

if ($filter_provider) {
    $where_clauses[] = 'api_provider = %s';
    $where_values[] = $filter_provider;
}

if ($filter_status) {
    if ($filter_status === 'success') {
        $where_clauses[] = 'response_code BETWEEN 200 AND 299';
    } else {
        $where_clauses[] = '(response_code < 200 OR response_code >= 300)';
    }
}

if ($filter_date) {
    $where_clauses[] = 'DATE(created_at) = %s';
    $where_values[] = $filter_date;
}

if ($search) {
    $where_clauses[] = '(endpoint LIKE %s OR request_data LIKE %s OR response_message LIKE %s)';
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $where_values[] = $search_like;
    $where_values[] = $search_like;
    $where_values[] = $search_like;
}

$where_sql = implode(' AND ', $where_clauses);

// Get total count
$total_query = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";
if (!empty($where_values)) {
    $total_query = $wpdb->prepare($total_query, $where_values);
}
$total_items = $wpdb->get_var($total_query);
$total_pages = ceil($total_items / $per_page);

// Get logs
$query = "SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
$query_values = array_merge($where_values, array($per_page, $offset));
if (!empty($query_values)) {
    $query = $wpdb->prepare($query, $query_values);
}
$logs = $wpdb->get_results($query);

// Get summary stats
$today_calls = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE()");
$today_credits = $wpdb->get_var("SELECT SUM(credits_used) FROM $table_name WHERE DATE(created_at) = CURDATE()") ?: 0;
$today_errors = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE() AND (response_code < 200 OR response_code >= 300)");
$avg_response_time = $wpdb->get_var("SELECT AVG(execution_time) FROM $table_name WHERE DATE(created_at) = CURDATE()") ?: 0;

// Get unique providers
$providers = $wpdb->get_col("SELECT DISTINCT api_provider FROM $table_name ORDER BY api_provider");
?>

<div class="wrap">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
        <a href="<?php echo admin_url('admin.php?page=aapi-tools'); ?>" class="page-title-action">
            <?php _e('Tools', 'aapi'); ?>
        </a>
    </h1>

    <!-- Summary Stats -->
    <div class="aapi-stats-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="aapi-stat-card">
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($today_calls); ?></h3>
                <p><?php _e('API Calls Today', 'aapi'); ?></p>
            </div>
        </div>
        
        <div class="aapi-stat-card">
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($today_credits); ?></h3>
                <p><?php _e('Credits Used Today', 'aapi'); ?></p>
            </div>
        </div>
        
        <div class="aapi-stat-card">
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($today_errors); ?></h3>
                <p><?php _e('Errors Today', 'aapi'); ?></p>
            </div>
        </div>
        
        <div class="aapi-stat-card">
            <div class="aapi-stat-content">
                <h3><?php echo number_format($avg_response_time, 2); ?>s</h3>
                <p><?php _e('Avg Response Time', 'aapi'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="tablenav top">
        <form method="get" action="">
            <input type="hidden" name="page" value="aapi-api-logs">
            
            <div class="alignleft actions">
                <select name="provider">
                    <option value=""><?php _e('All Providers', 'aapi'); ?></option>
                    <?php foreach ($providers as $provider) : ?>
                        <option value="<?php echo esc_attr($provider); ?>" <?php selected($filter_provider, $provider); ?>>
                            <?php echo esc_html(ucfirst($provider)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status">
                    <option value=""><?php _e('All Statuses', 'aapi'); ?></option>
                    <option value="success" <?php selected($filter_status, 'success'); ?>><?php _e('Success', 'aapi'); ?></option>
                    <option value="error" <?php selected($filter_status, 'error'); ?>><?php _e('Error', 'aapi'); ?></option>
                </select>
                
                <input type="date" name="date" value="<?php echo esc_attr($filter_date); ?>" placeholder="<?php esc_attr_e('Filter by date', 'aapi'); ?>">
                
                <?php submit_button(__('Filter', 'aapi'), 'secondary', 'filter_action', false); ?>
                
                <?php if ($filter_provider || $filter_status || $filter_date || $search) : ?>
                    <a href="<?php echo admin_url('admin.php?page=aapi-api-logs'); ?>" class="button">
                        <?php _e('Clear Filters', 'aapi'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="alignright">
                <p class="search-box">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search logs...', 'aapi'); ?>">
                    <?php submit_button(__('Search', 'aapi'), 'button', false, false); ?>
                </p>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php _e('ID', 'aapi'); ?></th>
                <th style="width: 120px;"><?php _e('Provider', 'aapi'); ?></th>
                <th><?php _e('Endpoint', 'aapi'); ?></th>
                <th style="width: 80px;"><?php _e('Method', 'aapi'); ?></th>
                <th style="width: 80px;"><?php _e('Status', 'aapi'); ?></th>
                <th style="width: 80px;"><?php _e('Credits', 'aapi'); ?></th>
                <th style="width: 100px;"><?php _e('Response Time', 'aapi'); ?></th>
                <th style="width: 150px;"><?php _e('Date', 'aapi'); ?></th>
                <th style="width: 100px;"><?php _e('Actions', 'aapi'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($logs) : ?>
                <?php foreach ($logs as $log) : 
                    $is_success = $log->response_code >= 200 && $log->response_code < 300;
                    $status_class = $is_success ? 'aapi-status-success' : 'aapi-status-error';
                ?>
                    <tr>
                        <td><?php echo $log->id; ?></td>
                        <td><strong><?php echo esc_html(ucfirst($log->api_provider)); ?></strong></td>
                        <td>
                            <code><?php echo esc_html($log->endpoint); ?></code>
                            <?php if ($log->response_message) : ?>
                                <br><small class="description"><?php echo esc_html(wp_trim_words($log->response_message, 10)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($log->request_type); ?></td>
                        <td>
                            <span class="<?php echo $status_class; ?>">
                                <?php echo $log->response_code ?: '-'; ?>
                            </span>
                        </td>
                        <td><?php echo number_format_i18n($log->credits_used); ?></td>
                        <td><?php echo number_format($log->execution_time, 3); ?>s</td>
                        <td>
                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at)); ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small aapi-view-log-details" 
                                    data-log-id="<?php echo $log->id; ?>">
                                <?php _e('Details', 'aapi'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9"><?php _e('No API logs found.', 'aapi'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        _n('%s item', '%s items', $total_items, 'aapi'),
                        number_format_i18n($total_items)
                    ); ?>
                </span>
                
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $paged,
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Clear Logs -->
    <div class="aapi-log-actions">
        <form method="post" action="" style="display: inline;">
            <?php wp_nonce_field('aapi_clear_logs', 'aapi_logs_nonce'); ?>
            <input type="hidden" name="action" value="clear_old_logs">
            <button type="submit" class="button" onclick="return confirm('<?php esc_attr_e('Delete logs older than 30 days?', 'aapi'); ?>')">
                <?php _e('Clear Old Logs (30+ days)', 'aapi'); ?>
            </button>
        </form>
        
        <form method="post" action="" style="display: inline;">
            <?php wp_nonce_field('aapi_clear_logs', 'aapi_logs_nonce'); ?>
            <input type="hidden" name="action" value="clear_all_logs">
            <button type="submit" class="button" onclick="return confirm('<?php esc_attr_e('Delete ALL logs? This cannot be undone.', 'aapi'); ?>')">
                <?php _e('Clear All Logs', 'aapi'); ?>
            </button>
        </form>
    </div>
</div>

<!-- Log Details Modal -->
<div id="aapi-log-details-modal" class="aapi-modal" style="display: none;">
    <div class="aapi-modal-content">
        <span class="aapi-modal-close">&times;</span>
        <h2><?php _e('API Log Details', 'aapi'); ?></h2>
        <div id="aapi-log-details-content">
            <p><?php _e('Loading...', 'aapi'); ?></p>
        </div>
    </div>
</div>

<style>
.aapi-log-actions {
    margin-top: 20px;
    padding: 15px;
    background: #f1f1f1;
    border: 1px solid #ccd0d4;
}

.aapi-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.aapi-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    position: relative;
}

.aapi-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.aapi-modal-close:hover,
.aapi-modal-close:focus {
    color: black;
}

#aapi-log-details-content {
    margin-top: 20px;
}

#aapi-log-details-content pre {
    background: #f5f5f5;
    padding: 10px;
    border: 1px solid #ddd;
    overflow-x: auto;
    max-height: 400px;
}

#aapi-log-details-content table {
    width: 100%;
    border-collapse: collapse;
}

#aapi-log-details-content table th,
#aapi-log-details-content table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

#aapi-log-details-content table th {
    background: #f5f5f5;
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    // View log details
    $('.aapi-view-log-details').on('click', function() {
        var logId = $(this).data('log-id');
        var modal = $('#aapi-log-details-modal');
        var content = $('#aapi-log-details-content');
        
        modal.show();
        content.html('<p><?php _e('Loading...', 'aapi'); ?></p>');
        
        // In production, this would be an AJAX call
        // For now, show a placeholder
        setTimeout(function() {
            content.html(`
                <table>
                    <tr><th>Log ID:</th><td>${logId}</td></tr>
                    <tr><th>Request Data:</th><td><pre>{sample request data}</pre></td></tr>
                    <tr><th>Response:</th><td><pre>{sample response data}</pre></td></tr>
                    <tr><th>Headers:</th><td><pre>{sample headers}</pre></td></tr>
                </table>
                <p><em>Note: Detailed log viewing will be fully implemented in Phase 2.</em></p>
            `);
        }, 500);
    });
    
    // Close modal
    $('.aapi-modal-close, .aapi-modal').on('click', function(e) {
        if (e.target === this) {
            $('.aapi-modal').hide();
        }
    });
});
</script>

<?php
// Handle log clearing
if (isset($_POST['action']) && isset($_POST['aapi_logs_nonce']) && wp_verify_nonce($_POST['aapi_logs_nonce'], 'aapi_clear_logs')) {
    $action = sanitize_text_field($_POST['action']);
    
    if ($action === 'clear_old_logs') {
        $deleted = $wpdb->query("DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(__('Deleted %d old log entries.', 'aapi'), $deleted) . '</p></div>';
    } elseif ($action === 'clear_all_logs') {
        $wpdb->query("TRUNCATE TABLE $table_name");
        echo '<div class="notice notice-success is-dismissible"><p>' . __('All logs have been cleared.', 'aapi') . '</p></div>';
    }
}
?>
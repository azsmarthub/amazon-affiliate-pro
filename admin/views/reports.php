<?php
/**
 * Admin reports view
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

// Get date range
$date_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '7days';
$start_date = '';
$end_date = '';

switch ($date_range) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'custom':
        $start_date = isset($_GET['start']) ? sanitize_text_field($_GET['start']) : date('Y-m-d', strtotime('-7 days'));
        $end_date = isset($_GET['end']) ? sanitize_text_field($_GET['end']) : date('Y-m-d');
        break;
}

// Get statistics
global $wpdb;
$clicks_table = $wpdb->prefix . 'aapi_clicks';
$products_table = $wpdb->prefix . 'aapi_products';

// Total clicks for period
$total_clicks = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $clicks_table 
     WHERE DATE(created_at) BETWEEN %s AND %s",
    $start_date, $end_date
));

// Unique visitors
$unique_visitors = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT ip_address) FROM $clicks_table 
     WHERE DATE(created_at) BETWEEN %s AND %s",
    $start_date, $end_date
));

// Top products
$top_products = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, pr.asin, COUNT(c.id) as clicks,
            COUNT(DISTINCT c.ip_address) as unique_clicks
     FROM {$wpdb->posts} p
     JOIN $products_table pr ON p.ID = pr.post_id
     LEFT JOIN $clicks_table c ON pr.id = c.product_id
     WHERE p.post_type = 'amazon_product' 
     AND p.post_status = 'publish'
     AND DATE(c.created_at) BETWEEN %s AND %s
     GROUP BY p.ID
     ORDER BY clicks DESC
     LIMIT 10",
    $start_date, $end_date
));

// Click distribution by day
$daily_clicks = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(created_at) as click_date, COUNT(*) as clicks
     FROM $clicks_table
     WHERE DATE(created_at) BETWEEN %s AND %s
     GROUP BY DATE(created_at)
     ORDER BY click_date ASC",
    $start_date, $end_date
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Date Range Filter -->
    <div class="aapi-reports-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="aapi-reports">
            
            <label for="date-range"><?php _e('Date Range:', 'aapi'); ?></label>
            <select name="range" id="date-range" onchange="this.form.submit()">
                <option value="today" <?php selected($date_range, 'today'); ?>><?php _e('Today', 'aapi'); ?></option>
                <option value="7days" <?php selected($date_range, '7days'); ?>><?php _e('Last 7 Days', 'aapi'); ?></option>
                <option value="30days" <?php selected($date_range, '30days'); ?>><?php _e('Last 30 Days', 'aapi'); ?></option>
                <option value="month" <?php selected($date_range, 'month'); ?>><?php _e('This Month', 'aapi'); ?></option>
                <option value="custom" <?php selected($date_range, 'custom'); ?>><?php _e('Custom Range', 'aapi'); ?></option>
            </select>
            
            <span id="custom-range" style="<?php echo $date_range === 'custom' ? '' : 'display:none;'; ?>">
                <input type="date" name="start" value="<?php echo esc_attr($start_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                <?php _e('to', 'aapi'); ?>
                <input type="date" name="end" value="<?php echo esc_attr($end_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                <button type="submit" class="button"><?php _e('Apply', 'aapi'); ?></button>
            </span>
            
            <span class="aapi-report-period">
                <?php printf(
                    __('Showing data from %s to %s', 'aapi'),
                    '<strong>' . date_i18n(get_option('date_format'), strtotime($start_date)) . '</strong>',
                    '<strong>' . date_i18n(get_option('date_format'), strtotime($end_date)) . '</strong>'
                ); ?>
            </span>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="aapi-stats-grid">
        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-chart-line"></div>
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($total_clicks); ?></h3>
                <p><?php _e('Total Clicks', 'aapi'); ?></p>
                <small><?php 
                    $avg_daily = $total_clicks / max(1, (strtotime($end_date) - strtotime($start_date)) / 86400 + 1);
                    printf(__('Average: %s per day', 'aapi'), number_format_i18n($avg_daily, 1)); 
                ?></small>
            </div>
        </div>

        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-groups"></div>
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($unique_visitors); ?></h3>
                <p><?php _e('Unique Visitors', 'aapi'); ?></p>
                <small><?php 
                    $ctr = $total_clicks > 0 ? ($unique_visitors / $total_clicks) * 100 : 0;
                    printf(__('%s%% unique rate', 'aapi'), number_format_i18n($ctr, 1)); 
                ?></small>
            </div>
        </div>

        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-cart"></div>
            <div class="aapi-stat-content">
                <h3><?php echo count($top_products); ?></h3>
                <p><?php _e('Active Products', 'aapi'); ?></p>
                <small><?php _e('With clicks in period', 'aapi'); ?></small>
            </div>
        </div>

        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-performance"></div>
            <div class="aapi-stat-content">
                <h3><?php 
                    $best_product_clicks = isset($top_products[0]) ? $top_products[0]->clicks : 0;
                    echo number_format_i18n($best_product_clicks); 
                ?></h3>
                <p><?php _e('Top Product Clicks', 'aapi'); ?></p>
                <small><?php 
                    if (isset($top_products[0])) {
                        echo esc_html(wp_trim_words($top_products[0]->post_title, 5));
                    } else {
                        _e('No data', 'aapi');
                    }
                ?></small>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="aapi-report-section">
        <h2><?php _e('Click Trends', 'aapi'); ?></h2>
        <div class="aapi-chart-container">
            <canvas id="aapi-clicks-chart"></canvas>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="aapi-report-section">
        <h2><?php _e('Top Performing Products', 'aapi'); ?></h2>
        <?php if ($top_products) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('Rank', 'aapi'); ?></th>
                        <th><?php _e('Product', 'aapi'); ?></th>
                        <th style="width: 120px;"><?php _e('ASIN', 'aapi'); ?></th>
                        <th style="width: 100px;"><?php _e('Total Clicks', 'aapi'); ?></th>
                        <th style="width: 100px;"><?php _e('Unique Clicks', 'aapi'); ?></th>
                        <th style="width: 100px;"><?php _e('CTR', 'aapi'); ?></th>
                        <th style="width: 120px;"><?php _e('Actions', 'aapi'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($top_products as $product) : 
                        $ctr = $product->clicks > 0 ? ($product->unique_clicks / $product->clicks) * 100 : 0;
                    ?>
                        <tr>
                            <td><strong>#<?php echo $rank++; ?></strong></td>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($product->ID); ?>">
                                        <?php echo esc_html($product->post_title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><code><?php echo esc_html($product->asin); ?></code></td>
                            <td><?php echo number_format_i18n($product->clicks); ?></td>
                            <td><?php echo number_format_i18n($product->unique_clicks); ?></td>
                            <td><?php echo number_format_i18n($ctr, 1); ?>%</td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=aapi-reports&product=' . $product->ID); ?>" 
                                   class="button button-small"><?php _e('Details', 'aapi'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No click data available for the selected period.', 'aapi'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Click Sources -->
    <div class="aapi-report-section">
        <h2><?php _e('Traffic Sources', 'aapi'); ?></h2>
        <?php
        $referrers = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer, COUNT(*) as clicks
             FROM $clicks_table
             WHERE DATE(created_at) BETWEEN %s AND %s
             AND referrer IS NOT NULL AND referrer != ''
             GROUP BY referrer
             ORDER BY clicks DESC
             LIMIT 10",
            $start_date, $end_date
        ));
        
        if ($referrers) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Referrer', 'aapi'); ?></th>
                        <th style="width: 150px;"><?php _e('Clicks', 'aapi'); ?></th>
                        <th style="width: 150px;"><?php _e('Percentage', 'aapi'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrers as $referrer) : 
                        $percentage = $total_clicks > 0 ? ($referrer->clicks / $total_clicks) * 100 : 0;
                        $domain = parse_url($referrer->referrer, PHP_URL_HOST) ?: $referrer->referrer;
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($referrer->referrer); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html($domain); ?>
                                </a>
                            </td>
                            <td><?php echo number_format_i18n($referrer->clicks); ?></td>
                            <td>
                                <div class="aapi-percentage-bar">
                                    <div class="aapi-percentage-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    <span><?php echo number_format_i18n($percentage, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No referrer data available for the selected period.', 'aapi'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Device Types -->
    <div class="aapi-report-section">
        <h2><?php _e('Device Types', 'aapi'); ?></h2>
        <?php
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as clicks
             FROM $clicks_table
             WHERE DATE(created_at) BETWEEN %s AND %s
             AND device_type IS NOT NULL
             GROUP BY device_type
             ORDER BY clicks DESC",
            $start_date, $end_date
        ));
        
        if ($devices) : ?>
            <div class="aapi-device-stats">
                <?php foreach ($devices as $device) : 
                    $percentage = $total_clicks > 0 ? ($device->clicks / $total_clicks) * 100 : 0;
                    $icon = 'dashicons-desktop';
                    if ($device->device_type === 'mobile') $icon = 'dashicons-smartphone';
                    elseif ($device->device_type === 'tablet') $icon = 'dashicons-tablet';
                ?>
                    <div class="aapi-device-card">
                        <span class="dashicons <?php echo $icon; ?>"></span>
                        <h4><?php echo ucfirst($device->device_type); ?></h4>
                        <p class="clicks"><?php echo number_format_i18n($device->clicks); ?> clicks</p>
                        <p class="percentage"><?php echo number_format_i18n($percentage, 1); ?>%</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Export Options -->
    <div class="aapi-report-section">
        <h2><?php _e('Export Reports', 'aapi'); ?></h2>
        <p><?php _e('Export your click data and analytics reports.', 'aapi'); ?></p>
        <div class="aapi-export-buttons">
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aapi-reports&export=csv&range=' . $date_range), 'aapi_export_csv'); ?>" 
               class="button">
                <span class="dashicons dashicons-media-spreadsheet"></span>
                <?php _e('Export as CSV', 'aapi'); ?>
            </a>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=aapi-reports&export=pdf&range=' . $date_range), 'aapi_export_pdf'); ?>" 
               class="button">
                <span class="dashicons dashicons-pdf"></span>
                <?php _e('Export as PDF', 'aapi'); ?>
            </a>
            <button type="button" class="button" onclick="window.print()">
                <span class="dashicons dashicons-printer"></span>
                <?php _e('Print Report', 'aapi'); ?>
            </button>
        </div>
    </div>
</div>

<script>
// Prepare chart data
var chartLabels = [<?php 
    $labels = array();
    foreach ($daily_clicks as $day) {
        $labels[] = "'" . date('M j', strtotime($day->click_date)) . "'";
    }
    echo implode(',', $labels);
?>];

var chartData = [<?php 
    $data = array();
    foreach ($daily_clicks as $day) {
        $data[] = $day->clicks;
    }
    echo implode(',', $data);
?>];

// Date range selector
document.getElementById('date-range').addEventListener('change', function() {
    document.getElementById('custom-range').style.display = 
        this.value === 'custom' ? 'inline' : 'none';
});
</script>

<style>
.aapi-reports-filter {
    background: #fff;
    padding: 15px;
    margin: 20px 0;
    border: 1px solid #ccd0d4;
}

.aapi-reports-filter form {
    display: flex;
    align-items: center;
    gap: 15px;
}

.aapi-report-period {
    margin-left: auto;
    color: #666;
}

.aapi-percentage-bar {
    position: relative;
    background: #f0f0f1;
    height: 20px;
    border-radius: 3px;
    overflow: hidden;
}

.aapi-percentage-fill {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    background: #0073aa;
    transition: width 0.3s ease;
}

.aapi-percentage-bar span {
    position: relative;
    display: block;
    text-align: center;
    line-height: 20px;
    font-size: 12px;
    font-weight: 600;
}

.aapi-device-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.aapi-device-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    padding: 20px;
    text-align: center;
}

.aapi-device-card .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #0073aa;
}

.aapi-device-card h4 {
    margin: 10px 0 5px;
}

.aapi-device-card .clicks {
    font-size: 18px;
    font-weight: bold;
    margin: 5px 0;
}

.aapi-device-card .percentage {
    color: #666;
}

.aapi-export-buttons {
    display: flex;
    gap: 10px;
}

.aapi-export-buttons .dashicons {
    vertical-align: middle;
    margin-right: 5px;
}

@media print {
    .aapi-reports-filter,
    .aapi-export-buttons,
    #adminmenumain,
    #wpadminbar,
    .aapi-report-section:last-child {
        display: none !important;
    }
}
</style>
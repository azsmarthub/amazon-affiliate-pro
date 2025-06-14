<?php
/**
 * Admin dashboard view
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

// Get statistics
global $wpdb;
$products_table = $wpdb->prefix . 'aapi_products';
$clicks_table = $wpdb->prefix . 'aapi_clicks';
$api_logs_table = $wpdb->prefix . 'aapi_api_logs';

// Product stats
$total_products = wp_count_posts('amazon_product')->publish;
$products_updated_today = $wpdb->get_var(
    "SELECT COUNT(*) FROM $products_table WHERE DATE(last_updated) = CURDATE()"
);

// Click stats
$clicks_today = $wpdb->get_var(
    "SELECT COUNT(*) FROM $clicks_table WHERE DATE(created_at) = CURDATE()"
);
$clicks_this_month = $wpdb->get_var(
    "SELECT COUNT(*) FROM $clicks_table WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
);

// API stats
$api_calls_today = $wpdb->get_var(
    "SELECT COUNT(*) FROM $api_logs_table WHERE DATE(created_at) = CURDATE()"
);
$api_quota_used = $wpdb->get_var(
    "SELECT SUM(credits_used) FROM $api_logs_table WHERE DATE(created_at) = CURDATE()"
) ?: 0;

// Recent products
$recent_products = get_posts(array(
    'post_type' => 'amazon_product',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
));

// Top performing products
$top_products = $wpdb->get_results(
    "SELECT p.ID, p.post_title, COUNT(c.id) as click_count
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->prefix}aapi_products ap ON p.ID = ap.post_id
     LEFT JOIN $clicks_table c ON ap.id = c.product_id
     WHERE p.post_type = 'amazon_product' 
     AND p.post_status = 'publish'
     AND c.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY p.ID
     ORDER BY click_count DESC
     LIMIT 5"
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aapi-dashboard-welcome">
        <h2><?php _e('Welcome to Amazon Affiliate Pro', 'aapi'); ?></h2>
        <p><?php _e('Manage your Amazon affiliate products, track performance, and optimize your earnings.', 'aapi'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="aapi-stats-grid">
        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-cart"></div>
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($total_products); ?></h3>
                <p><?php _e('Total Products', 'aapi'); ?></p>
                <small><?php printf(__('%s updated today', 'aapi'), number_format_i18n($products_updated_today)); ?></small>
            </div>
        </div>

        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-chart-area"></div>
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($clicks_today); ?></h3>
                <p><?php _e('Clicks Today', 'aapi'); ?></p>
                <small><?php printf(__('%s this month', 'aapi'), number_format_i18n($clicks_this_month)); ?></small>
            </div>
        </div>

        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-cloud"></div>
            <div class="aapi-stat-content">
                <h3><?php echo number_format_i18n($api_calls_today); ?></h3>
                <p><?php _e('API Calls Today', 'aapi'); ?></p>
                <small><?php printf(__('%s credits used', 'aapi'), number_format_i18n($api_quota_used)); ?></small>
            </div>
        </div>

        <div class="aapi-stat-card">
            <div class="aapi-stat-icon dashicons dashicons-admin-settings"></div>
            <div class="aapi-stat-content">
                <h3><?php _e('Status', 'aapi'); ?></h3>
                <p class="aapi-status-active"><?php _e('Active', 'aapi'); ?></p>
                <small><?php _e('All systems operational', 'aapi'); ?></small>
            </div>
        </div>
    </div>

    <div class="aapi-dashboard-columns">
        <!-- Recent Products -->
        <div class="aapi-dashboard-column">
            <div class="aapi-dashboard-widget">
                <h2><?php _e('Recent Products', 'aapi'); ?></h2>
                <?php if ($recent_products) : ?>
                    <ul class="aapi-product-list">
                        <?php foreach ($recent_products as $product) : 
                            $asin = get_post_meta($product->ID, '_aapi_asin', true);
                            $price = get_post_meta($product->ID, '_aapi_price', true);
                            $currency = get_post_meta($product->ID, '_aapi_currency', true);
                        ?>
                            <li>
                                <div class="aapi-product-info">
                                    <a href="<?php echo get_edit_post_link($product->ID); ?>">
                                        <?php echo esc_html($product->post_title); ?>
                                    </a>
                                    <span class="aapi-product-meta">
                                        <?php if ($asin) : ?>
                                            <code><?php echo esc_html($asin); ?></code>
                                        <?php endif; ?>
                                        <?php if ($price) : ?>
                                            <span class="aapi-price"><?php echo esc_html($currency . $price); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="aapi-view-all">
                        <a href="<?php echo admin_url('edit.php?post_type=amazon_product'); ?>">
                            <?php _e('View all products →', 'aapi'); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <p><?php _e('No products yet.', 'aapi'); ?> 
                        <a href="<?php echo admin_url('admin.php?page=aapi-import'); ?>">
                            <?php _e('Import your first product', 'aapi'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Performing Products -->
        <div class="aapi-dashboard-column">
            <div class="aapi-dashboard-widget">
                <h2><?php _e('Top Performing Products', 'aapi'); ?></h2>
                <?php if ($top_products) : ?>
                    <ul class="aapi-product-list">
                        <?php foreach ($top_products as $product) : ?>
                            <li>
                                <div class="aapi-product-info">
                                    <a href="<?php echo get_edit_post_link($product->ID); ?>">
                                        <?php echo esc_html($product->post_title); ?>
                                    </a>
                                    <span class="aapi-product-meta">
                                        <span class="aapi-clicks">
                                            <?php printf(
                                                _n('%s click', '%s clicks', $product->click_count, 'aapi'),
                                                number_format_i18n($product->click_count)
                                            ); ?>
                                        </span>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="aapi-view-all">
                        <a href="<?php echo admin_url('admin.php?page=aapi-reports'); ?>">
                            <?php _e('View detailed reports →', 'aapi'); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <p><?php _e('No click data available yet.', 'aapi'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="aapi-quick-actions">
        <h2><?php _e('Quick Actions', 'aapi'); ?></h2>
        <div class="aapi-action-buttons">
            <a href="<?php echo admin_url('admin.php?page=aapi-import'); ?>" class="button button-primary">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Import Products', 'aapi'); ?>
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=amazon_product'); ?>" class="button">
                <span class="dashicons dashicons-products"></span>
                <?php _e('Manage Products', 'aapi'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=aapi-settings'); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Settings', 'aapi'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=aapi-reports'); ?>" class="button">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php _e('View Reports', 'aapi'); ?>
            </a>
        </div>
    </div>

    <!-- System Status -->
    <div class="aapi-system-status">
        <h2><?php _e('System Status', 'aapi'); ?></h2>
        <table class="aapi-status-table">
            <tr>
                <td><?php _e('Plugin Version', 'aapi'); ?></td>
                <td><?php echo AAPI_VERSION; ?></td>
            </tr>
            <tr>
                <td><?php _e('Database Version', 'aapi'); ?></td>
                <td><?php echo get_option('aapi_db_version', '1.0.0'); ?></td>
            </tr>
            <tr>
                <td><?php _e('Primary API', 'aapi'); ?></td>
                <td><?php 
                    $settings = new \AAPI\Core\Settings();
                    echo esc_html(ucfirst($settings->get('api', 'primary_api', 'Not configured')));
                ?></td>
            </tr>
            <tr>
                <td><?php _e('Auto Updates', 'aapi'); ?></td>
                <td><?php 
                    echo $settings->get('general', 'auto_update', false) 
                        ? '<span class="aapi-status-active">' . __('Enabled', 'aapi') . '</span>' 
                        : '<span class="aapi-status-inactive">' . __('Disabled', 'aapi') . '</span>';
                ?></td>
            </tr>
            <tr>
                <td><?php _e('Next Scheduled Update', 'aapi'); ?></td>
                <td><?php 
                    $next_update = wp_next_scheduled('aapi_update_products');
                    echo $next_update 
                        ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update)
                        : __('Not scheduled', 'aapi');
                ?></td>
            </tr>
        </table>
    </div>
</div>

<style>
.aapi-dashboard-welcome {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
}

.aapi-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.aapi-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    display: flex;
    align-items: center;
}

.aapi-stat-icon {
    font-size: 40px;
    width: 60px;
    color: #0073aa;
}

.aapi-stat-content h3 {
    margin: 0;
    font-size: 28px;
}

.aapi-stat-content p {
    margin: 5px 0;
    color: #666;
}

.aapi-stat-content small {
    color: #999;
}

.aapi-dashboard-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.aapi-dashboard-widget {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
}

.aapi-dashboard-widget h2 {
    margin-top: 0;
}

.aapi-product-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.aapi-product-list li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.aapi-product-list li:last-child {
    border-bottom: none;
}

.aapi-product-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aapi-product-meta {
    font-size: 13px;
    color: #666;
}

.aapi-product-meta code {
    margin-right: 10px;
}

.aapi-price {
    font-weight: bold;
    color: #0073aa;
}

.aapi-clicks {
    background: #f0f0f1;
    padding: 2px 8px;
    border-radius: 3px;
}

.aapi-view-all {
    margin-top: 15px;
    margin-bottom: 0;
}

.aapi-quick-actions {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
}

.aapi-action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.aapi-action-buttons .dashicons {
    vertical-align: middle;
    margin-right: 5px;
}

.aapi-system-status {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
}

.aapi-status-table {
    width: 100%;
    border-collapse: collapse;
}

.aapi-status-table td {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.aapi-status-table td:first-child {
    font-weight: bold;
    width: 200px;
}

.aapi-status-active {
    color: #46b450;
}

.aapi-status-inactive {
    color: #dc3232;
}

@media (max-width: 782px) {
    .aapi-dashboard-columns {
        grid-template-columns: 1fr;
    }
}
</style>
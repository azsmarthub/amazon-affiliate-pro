<?php
/**
 * Admin import view
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
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Single Product Import -->
    <div class="aapi-import-section">
        <h2><?php _e('Import Single Product', 'aapi'); ?></h2>
        <p><?php _e('Import a product by entering its ASIN (Amazon Standard Identification Number).', 'aapi'); ?></p>
        
        <form id="aapi-import-form" class="aapi-import-form" method="post">
            <div class="form-field">
                <label for="aapi-import-asin"><?php _e('ASIN', 'aapi'); ?></label>
                <input type="text" 
                       id="aapi-import-asin" 
                       name="asin" 
                       class="regular-text" 
                       placeholder="B08N5WRWNW" 
                       required 
                       pattern="[A-Z0-9]{10}"
                       title="<?php esc_attr_e('Please enter a valid 10-character ASIN', 'aapi'); ?>">
                <p class="description"><?php _e('Enter the product ASIN (e.g., B08N5WRWNW)', 'aapi'); ?></p>
            </div>
            
            <div class="form-field">
                <label for="aapi-import-marketplace"><?php _e('Marketplace', 'aapi'); ?></label>
                <select id="aapi-import-marketplace" name="marketplace">
                    <?php
                    $api_manager = new \AAPI\API\API_Manager();
                    $marketplaces = $api_manager->get_supported_marketplaces();
                    $default_marketplace = (new \AAPI\Core\Settings())->get('general', 'default_marketplace', 'US');
                    
                    foreach ($marketplaces as $code => $name) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($code),
                            selected($default_marketplace, $code, false),
                            esc_html($name)
                        );
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-field">
                <label>
                    <input type="checkbox" name="auto_publish" value="1" checked>
                    <?php _e('Publish immediately after import', 'aapi'); ?>
                </label>
            </div>
            
            <p class="submit">
                <button type="submit" class="button button-primary" data-original-text="<?php esc_attr_e('Import Product', 'aapi'); ?>">
                    <?php _e('Import Product', 'aapi'); ?>
                </button>
                <span class="spinner"></span>
            </p>
        </form>
        
        <div id="aapi-import-results" class="aapi-import-results"></div>
    </div>

    <!-- Search and Import -->
    <div class="aapi-import-section">
        <h2><?php _e('Search and Import', 'aapi'); ?></h2>
        <p><?php _e('Search for products on Amazon and import them to your site.', 'aapi'); ?></p>
        
        <form id="aapi-search-form" class="aapi-import-form" method="post">
            <div class="form-field">
                <label for="aapi-search-keyword"><?php _e('Search Keyword', 'aapi'); ?></label>
                <input type="text" 
                       id="aapi-search-keyword" 
                       name="keyword" 
                       class="regular-text" 
                       placeholder="<?php esc_attr_e('e.g., wireless headphones', 'aapi'); ?>" 
                       required>
                <p class="description"><?php _e('Enter keywords to search for products', 'aapi'); ?></p>
            </div>
            
            <div class="form-field">
                <label for="aapi-search-category"><?php _e('Category (Optional)', 'aapi'); ?></label>
                <select id="aapi-search-category" name="category">
                    <option value=""><?php _e('All Categories', 'aapi'); ?></option>
                    <option value="Electronics"><?php _e('Electronics', 'aapi'); ?></option>
                    <option value="Books"><?php _e('Books', 'aapi'); ?></option>
                    <option value="HomeAndKitchen"><?php _e('Home & Kitchen', 'aapi'); ?></option>
                    <option value="Fashion"><?php _e('Fashion', 'aapi'); ?></option>
                    <option value="Sports"><?php _e('Sports & Outdoors', 'aapi'); ?></option>
                    <option value="Toys"><?php _e('Toys & Games', 'aapi'); ?></option>
                    <option value="Beauty"><?php _e('Beauty & Personal Care', 'aapi'); ?></option>
                    <option value="HealthAndHousehold"><?php _e('Health & Household', 'aapi'); ?></option>
                </select>
            </div>
            
            <div class="form-field">
                <label for="aapi-search-sort"><?php _e('Sort By', 'aapi'); ?></label>
                <select id="aapi-search-sort" name="sort">
                    <option value="relevance"><?php _e('Relevance', 'aapi'); ?></option>
                    <option value="price-asc"><?php _e('Price: Low to High', 'aapi'); ?></option>
                    <option value="price-desc"><?php _e('Price: High to Low', 'aapi'); ?></option>
                    <option value="reviews"><?php _e('Average Customer Review', 'aapi'); ?></option>
                    <option value="newest"><?php _e('Newest Arrivals', 'aapi'); ?></option>
                </select>
            </div>
            
            <p class="submit">
                <button type="submit" class="button button-secondary">
                    <?php _e('Search Products', 'aapi'); ?>
                </button>
                <span class="spinner"></span>
            </p>
        </form>
        
        <div id="aapi-search-results" class="aapi-search-results"></div>
    </div>

    <!-- Bulk Import -->
    <div class="aapi-import-section">
        <h2><?php _e('Bulk Import', 'aapi'); ?></h2>
        <p><?php _e('Import multiple products at once by entering their ASINs.', 'aapi'); ?></p>
        
        <form id="aapi-bulk-form" class="aapi-import-form" method="post">
            <div class="form-field">
                <label for="aapi-bulk-asins"><?php _e('ASINs (one per line)', 'aapi'); ?></label>
                <textarea id="aapi-bulk-asins" 
                          name="asins" 
                          rows="10" 
                          class="large-text" 
                          placeholder="B08N5WRWNW&#10;B07FZ8S74R&#10;B07HZNRWN8"
                          required></textarea>
                <p class="description"><?php _e('Enter multiple ASINs, one per line. Maximum 50 ASINs per import.', 'aapi'); ?></p>
            </div>
            
            <p class="submit">
                <button type="button" id="aapi-bulk-import" class="button button-secondary">
                    <?php _e('Start Bulk Import', 'aapi'); ?>
                </button>
                <span class="spinner"></span>
            </p>
            
            <div id="aapi-bulk-progress" class="aapi-bulk-progress" style="display:none;">
                <p><?php _e('Import Progress:', 'aapi'); ?> <span class="progress">0 / 0</span></p>
                <p><?php _e('Current:', 'aapi'); ?> <span class="current">-</span></p>
                <div class="aapi-progress-bar">
                    <div class="aapi-progress-bar-fill" style="width: 0%;"></div>
                </div>
            </div>
        </form>
    </div>

    <!-- Import from CSV -->
    <div class="aapi-import-section">
        <h2><?php _e('Import from CSV', 'aapi'); ?></h2>
        <p><?php _e('Import products from a CSV file containing ASINs and product data.', 'aapi'); ?></p>
        
        <form id="aapi-csv-form" class="aapi-import-form" method="post" enctype="multipart/form-data">
            <div class="form-field">
                <label for="aapi-csv-file"><?php _e('CSV File', 'aapi'); ?></label>
                <input type="file" 
                       id="aapi-csv-file" 
                       name="csv_file" 
                       accept=".csv" 
                       required>
                <p class="description">
                    <?php _e('CSV format: ASIN, Title (optional), Category (optional)', 'aapi'); ?>
                    <br>
                    <a href="#" id="aapi-download-sample-csv"><?php _e('Download sample CSV', 'aapi'); ?></a>
                </p>
            </div>
            
            <p class="submit">
                <button type="submit" class="button button-secondary">
                    <?php _e('Upload and Import', 'aapi'); ?>
                </button>
                <span class="spinner"></span>
            </p>
        </form>
    </div>

    <!-- Import History -->
    <div class="aapi-import-section">
        <h2><?php _e('Recent Imports', 'aapi'); ?></h2>
        <?php
        // Get recent imports
        $recent_products = get_posts(array(
            'post_type' => 'amazon_product',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '_aapi_import_date',
            'meta_compare' => 'EXISTS'
        ));
        
        if ($recent_products) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'aapi'); ?></th>
                        <th><?php _e('ASIN', 'aapi'); ?></th>
                        <th><?php _e('Import Date', 'aapi'); ?></th>
                        <th><?php _e('Status', 'aapi'); ?></th>
                        <th><?php _e('Actions', 'aapi'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_products as $product) : 
                        $asin = get_post_meta($product->ID, '_aapi_asin', true);
                        $import_date = get_post_meta($product->ID, '_aapi_import_date', true);
                    ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($product->ID); ?>">
                                        <?php echo esc_html($product->post_title); ?>
                                    </a>
                                </strong>
                            </td>
                            <td><code><?php echo esc_html($asin); ?></code></td>
                            <td><?php echo $import_date ? esc_html(date_i18n(get_option('date_format'), strtotime($import_date))) : '-'; ?></td>
                            <td><span class="aapi-status-success"><?php _e('Imported', 'aapi'); ?></span></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($product->ID); ?>" class="button button-small">
                                    <?php _e('Edit', 'aapi'); ?>
                                </a>
                                <a href="<?php echo get_permalink($product->ID); ?>" class="button button-small" target="_blank">
                                    <?php _e('View', 'aapi'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No products imported yet.', 'aapi'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.aapi-bulk-progress {
    background: #f1f1f1;
    padding: 15px;
    border-radius: 3px;
    margin-top: 20px;
}

.aapi-progress-bar {
    background: #ddd;
    height: 20px;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.aapi-progress-bar-fill {
    background: #0073aa;
    height: 100%;
    transition: width 0.3s ease;
}

.aapi-search-results {
    margin-top: 20px;
}

.aapi-product-preview {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    margin-bottom: 10px;
}

.aapi-product-preview img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-right: 15px;
}

.aapi-product-preview-info {
    flex: 1;
}

.aapi-product-preview-actions {
    margin-left: 15px;
}
</style>
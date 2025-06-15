<?php
/**
 * Custom post type functionality
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/core
 */

namespace AAPI\Core;

/**
 * Custom post type class.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/core
 * @author     Your Name <email@example.com>
 */
class Post_Type {

    /**
     * Post type name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $post_type    Post type name.
     */
    private $post_type = 'amazon_product';

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Constructor
    }

    /**
     * Register custom post type.
     *
     * @since    1.0.0
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Amazon Products', 'Post type general name', 'aapi'),
            'singular_name'         => _x('Amazon Product', 'Post type singular name', 'aapi'),
            'menu_name'             => _x('Amazon Products', 'Admin Menu text', 'aapi'),
            'name_admin_bar'        => _x('Amazon Product', 'Add New on Toolbar', 'aapi'),
            'add_new'               => __('Add New', 'aapi'),
            'add_new_item'          => __('Add New Product', 'aapi'),
            'new_item'              => __('New Product', 'aapi'),
            'edit_item'             => __('Edit Product', 'aapi'),
            'view_item'             => __('View Product', 'aapi'),
            'all_items'             => __('All Products', 'aapi'),
            'search_items'          => __('Search Products', 'aapi'),
            'parent_item_colon'     => __('Parent Products:', 'aapi'),
            'not_found'             => __('No products found.', 'aapi'),
            'not_found_in_trash'    => __('No products found in Trash.', 'aapi'),
            'featured_image'        => _x('Product Image', 'Overrides the "Featured Image" phrase', 'aapi'),
            'set_featured_image'    => _x('Set product image', 'Overrides the "Set featured image" phrase', 'aapi'),
            'remove_featured_image' => _x('Remove product image', 'Overrides the "Remove featured image" phrase', 'aapi'),
            'use_featured_image'    => _x('Use as product image', 'Overrides the "Use as featured image" phrase', 'aapi'),
            'archives'              => _x('Product Archives', 'The post type archive label', 'aapi'),
            'insert_into_item'      => _x('Insert into product', 'Overrides the "Insert into post" phrase', 'aapi'),
            'uploaded_to_this_item' => _x('Uploaded to this product', 'Overrides the "Uploaded to this post" phrase', 'aapi'),
            'filter_items_list'     => _x('Filter products list', 'Screen reader text', 'aapi'),
            'items_list_navigation' => _x('Products list navigation', 'Screen reader text', 'aapi'),
            'items_list'            => _x('Products list', 'Screen reader text', 'aapi'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'aapi', // Show under our main plugin menu
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'capabilities'       => array(
                'edit_post'          => 'aapi_manage_products',
                'read_post'          => 'aapi_manage_products',
                'delete_post'        => 'aapi_manage_products',
                'edit_posts'         => 'aapi_manage_products',
                'edit_others_posts'  => 'aapi_manage_products',
                'publish_posts'      => 'aapi_manage_products',
                'read_private_posts' => 'aapi_manage_products',
                'create_posts'       => 'aapi_import_products',
            ),
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'       => true,
            'rest_base'          => 'aapi-products',
        );

        register_post_type($this->post_type, $args);
    }

    /**
     * Register taxonomies.
     *
     * @since    1.0.0
     */
    public function register_taxonomies() {
        // Product Category
        $category_labels = array(
            'name'              => _x('Product Categories', 'taxonomy general name', 'aapi'),
            'singular_name'     => _x('Product Category', 'taxonomy singular name', 'aapi'),
            'search_items'      => __('Search Categories', 'aapi'),
            'all_items'         => __('All Categories', 'aapi'),
            'parent_item'       => __('Parent Category', 'aapi'),
            'parent_item_colon' => __('Parent Category:', 'aapi'),
            'edit_item'         => __('Edit Category', 'aapi'),
            'update_item'       => __('Update Category', 'aapi'),
            'add_new_item'      => __('Add New Category', 'aapi'),
            'new_item_name'     => __('New Category Name', 'aapi'),
            'menu_name'         => __('Categories', 'aapi'),
        );

        $category_args = array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'aapi-categories',
        );

        register_taxonomy('amazon_product_cat', array($this->post_type), $category_args);

        // Product Tag
        $tag_labels = array(
            'name'                       => _x('Product Tags', 'taxonomy general name', 'aapi'),
            'singular_name'              => _x('Product Tag', 'taxonomy singular name', 'aapi'),
            'search_items'               => __('Search Tags', 'aapi'),
            'popular_items'              => __('Popular Tags', 'aapi'),
            'all_items'                  => __('All Tags', 'aapi'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Tag', 'aapi'),
            'update_item'                => __('Update Tag', 'aapi'),
            'add_new_item'               => __('Add New Tag', 'aapi'),
            'new_item_name'              => __('New Tag Name', 'aapi'),
            'separate_items_with_commas' => __('Separate tags with commas', 'aapi'),
            'add_or_remove_items'        => __('Add or remove tags', 'aapi'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'aapi'),
            'not_found'                  => __('No tags found.', 'aapi'),
            'menu_name'                  => __('Tags', 'aapi'),
        );

        $tag_args = array(
            'hierarchical'          => false,
            'labels'                => $tag_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => false,
            'rewrite'               => false,
            'show_in_rest'          => true,
            'rest_base'             => 'aapi-tags',
        );

        register_taxonomy('amazon_product_tag', array($this->post_type), $tag_args);

        // Brand Taxonomy
        $brand_labels = array(
            'name'              => _x('Brands', 'taxonomy general name', 'aapi'),
            'singular_name'     => _x('Brand', 'taxonomy singular name', 'aapi'),
            'search_items'      => __('Search Brands', 'aapi'),
            'all_items'         => __('All Brands', 'aapi'),
            'edit_item'         => __('Edit Brand', 'aapi'),
            'update_item'       => __('Update Brand', 'aapi'),
            'add_new_item'      => __('Add New Brand', 'aapi'),
            'new_item_name'     => __('New Brand Name', 'aapi'),
            'menu_name'         => __('Brands', 'aapi'),
        );

        $brand_args = array(
            'hierarchical'      => false,
            'labels'            => $brand_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
            'rest_base'         => 'aapi-brands',
        );

        register_taxonomy('amazon_product_brand', array($this->post_type), $brand_args);
    }

    /**
     * Add meta boxes.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        // Product Information
        add_meta_box(
            'aapi_product_info',
            __('Product Information', 'aapi'),
            array($this, 'render_product_info_metabox'),
            $this->post_type,
            'normal',
            'high'
        );

        // Pricing Information
        add_meta_box(
            'aapi_pricing_info',
            __('Pricing Information', 'aapi'),
            array($this, 'render_pricing_metabox'),
            $this->post_type,
            'side',
            'high'
        );

        // API Settings
        add_meta_box(
            'aapi_api_settings',
            __('API Settings', 'aapi'),
            array($this, 'render_api_settings_metabox'),
            $this->post_type,
            'side',
            'default'
        );

        // Product Images
        add_meta_box(
            'aapi_product_images',
            __('Product Images', 'aapi'),
            array($this, 'render_images_metabox'),
            $this->post_type,
            'normal',
            'default'
        );

        // Update Status
        add_meta_box(
            'aapi_update_status',
            __('Update Status', 'aapi'),
            array($this, 'render_update_status_metabox'),
            $this->post_type,
            'side',
            'low'
        );
    }

    /**
     * Render product info metabox.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_product_info_metabox($post) {
        wp_nonce_field('aapi_save_product_meta', 'aapi_product_nonce');
        
        $asin = get_post_meta($post->ID, '_aapi_asin', true);
        $marketplace = get_post_meta($post->ID, '_aapi_marketplace', true);
        $brand = get_post_meta($post->ID, '_aapi_brand', true);
        $features = get_post_meta($post->ID, '_aapi_features', true);
        $url = get_post_meta($post->ID, '_aapi_url', true);
        ?>
        <div class="aapi-metabox">
            <p>
                <label for="aapi_asin"><strong><?php _e('ASIN:', 'aapi'); ?></strong></label>
                <input type="text" id="aapi_asin" name="aapi_asin" value="<?php echo esc_attr($asin); ?>" class="regular-text" />
                <span class="description"><?php _e('Amazon Standard Identification Number', 'aapi'); ?></span>
            </p>
            
            <p>
                <label for="aapi_marketplace"><strong><?php _e('Marketplace:', 'aapi'); ?></strong></label>
                <select id="aapi_marketplace" name="aapi_marketplace">
                    <?php
                    $settings = new Settings();
                    $marketplaces = $settings->get_fields('general')['default_marketplace']['options'];
                    foreach ($marketplaces as $code => $name) {
                        printf(
                            '<option value="%s" %s>%s</option>',
                            esc_attr($code),
                            selected($marketplace, $code, false),
                            esc_html($name)
                        );
                    }
                    ?>
                </select>
            </p>
            
            <p>
                <label for="aapi_brand"><strong><?php _e('Brand:', 'aapi'); ?></strong></label>
                <input type="text" id="aapi_brand" name="aapi_brand" value="<?php echo esc_attr($brand); ?>" class="regular-text" />
            </p>
            
            <p>
                <label for="aapi_features"><strong><?php _e('Key Features:', 'aapi'); ?></strong></label>
                <textarea id="aapi_features" name="aapi_features" rows="5" class="large-text"><?php echo esc_textarea($features); ?></textarea>
                <span class="description"><?php _e('One feature per line', 'aapi'); ?></span>
            </p>
            
            <p>
                <label for="aapi_url"><strong><?php _e('Amazon URL:', 'aapi'); ?></strong></label>
                <input type="url" id="aapi_url" name="aapi_url" value="<?php echo esc_url($url); ?>" class="large-text" />
            </p>
            
            <?php if ($asin) : ?>
            <p>
                <button type="button" class="button button-secondary" id="aapi-refresh-product" data-asin="<?php echo esc_attr($asin); ?>">
                    <?php _e('Refresh Product Data', 'aapi'); ?>
                </button>
                <span class="spinner"></span>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render pricing metabox.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_pricing_metabox($post) {
        $price = get_post_meta($post->ID, '_aapi_price', true);
        $currency = get_post_meta($post->ID, '_aapi_currency', true);
        $sale_price = get_post_meta($post->ID, '_aapi_sale_price', true);
        $availability = get_post_meta($post->ID, '_aapi_availability', true);
        $prime = get_post_meta($post->ID, '_aapi_prime_eligible', true);
        ?>
        <div class="aapi-metabox">
            <p>
                <label for="aapi_price"><strong><?php _e('Price:', 'aapi'); ?></strong></label>
                <input type="text" id="aapi_price" name="aapi_price" value="<?php echo esc_attr($price); ?>" class="small-text" />
                <select name="aapi_currency" class="small-text">
                    <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                    <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                    <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                    <option value="JPY" <?php selected($currency, 'JPY'); ?>>JPY</option>
                    <option value="CAD" <?php selected($currency, 'CAD'); ?>>CAD</option>
                </select>
            </p>
            
            <p>
                <label for="aapi_sale_price"><strong><?php _e('Sale Price:', 'aapi'); ?></strong></label>
                <input type="text" id="aapi_sale_price" name="aapi_sale_price" value="<?php echo esc_attr($sale_price); ?>" class="small-text" />
            </p>
            
            <p>
                <label for="aapi_availability"><strong><?php _e('Availability:', 'aapi'); ?></strong></label>
                <select id="aapi_availability" name="aapi_availability" class="widefat">
                    <option value="InStock" <?php selected($availability, 'InStock'); ?>><?php _e('In Stock', 'aapi'); ?></option>
                    <option value="OutOfStock" <?php selected($availability, 'OutOfStock'); ?>><?php _e('Out of Stock', 'aapi'); ?></option>
                    <option value="PreOrder" <?php selected($availability, 'PreOrder'); ?>><?php _e('Pre-Order', 'aapi'); ?></option>
                    <option value="LimitedAvailability" <?php selected($availability, 'LimitedAvailability'); ?>><?php _e('Limited Availability', 'aapi'); ?></option>
                </select>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="aapi_prime_eligible" value="1" <?php checked($prime, '1'); ?> />
                    <strong><?php _e('Prime Eligible', 'aapi'); ?></strong>
                </label>
            </p>
        </div>
        <?php
    }

    /**
     * Render API settings metabox.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_api_settings_metabox($post) {
        $source_override = get_post_meta($post->ID, '_aapi_source_override', true);
        $last_source = get_post_meta($post->ID, '_aapi_last_source', true);
        ?>
        <div class="aapi-metabox">
            <p>
                <label for="aapi_source_override"><strong><?php _e('API Source Override:', 'aapi'); ?></strong></label>
                <select id="aapi_source_override" name="aapi_source_override" class="widefat">
                    <option value=""><?php _e('Use Default', 'aapi'); ?></option>
                    <option value="paapi" <?php selected($source_override, 'paapi'); ?>>Amazon PA-API</option>
                    <option value="rainforest" <?php selected($source_override, 'rainforest'); ?>>RainforestAPI</option>
                    <option value="serpapi" <?php selected($source_override, 'serpapi'); ?>>SerpApi</option>
                    <option value="dataforseo" <?php selected($source_override, 'dataforseo'); ?>>DataForSEO</option>
                </select>
                <span class="description"><?php _e('Override the default API source for this product', 'aapi'); ?></span>
            </p>
            
            <?php if ($last_source) : ?>
            <p>
                <strong><?php _e('Last Updated Via:', 'aapi'); ?></strong><br>
                <?php echo esc_html(ucfirst($last_source)); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render images metabox.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_images_metabox($post) {
        $gallery_images = get_post_meta($post->ID, '_aapi_gallery_images', true);
        ?>
        <div class="aapi-metabox">
            <p>
                <strong><?php _e('Gallery Images:', 'aapi'); ?></strong><br>
                <span class="description"><?php _e('Additional product images from Amazon', 'aapi'); ?></span>
            </p>
            
            <div id="aapi-gallery-images" class="aapi-gallery-grid">
                <?php
                if (!empty($gallery_images) && is_array($gallery_images)) {
                    foreach ($gallery_images as $image_url) {
                        printf(
                            '<div class="aapi-gallery-item"><img src="%s" alt="" /></div>',
                            esc_url($image_url)
                        );
                    }
                }
                ?>
            </div>
            
            <input type="hidden" id="aapi_gallery_images" name="aapi_gallery_images" value="<?php echo esc_attr(json_encode($gallery_images)); ?>" />
        </div>
        <?php
    }

    /**
     * Render update status metabox.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_update_status_metabox($post) {
        $last_updated = get_post_meta($post->ID, '_aapi_last_updated', true);
        $update_status = get_post_meta($post->ID, '_aapi_update_status', true);
        $rating = get_post_meta($post->ID, '_aapi_rating', true);
        $review_count = get_post_meta($post->ID, '_aapi_review_count', true);
        ?>
        <div class="aapi-metabox">
            <?php if ($last_updated) : ?>
            <p>
                <strong><?php _e('Last Updated:', 'aapi'); ?></strong><br>
                <?php echo esc_html(human_time_diff(strtotime($last_updated), current_time('timestamp')) . ' ' . __('ago', 'aapi')); ?>
                <br>
                <small><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_updated))); ?></small>
            </p>
            <?php endif; ?>
            
            <?php if ($update_status) : ?>
            <p>
                <strong><?php _e('Update Status:', 'aapi'); ?></strong><br>
                <span class="aapi-status-<?php echo esc_attr($update_status); ?>">
                    <?php echo esc_html(ucfirst($update_status)); ?>
                </span>
            </p>
            <?php endif; ?>
            
            <?php if ($rating) : ?>
            <p>
                <strong><?php _e('Rating:', 'aapi'); ?></strong><br>
                <?php echo esc_html($rating); ?>/5.0
                <?php if ($review_count) : ?>
                    (<?php echo esc_html(number_format_i18n($review_count)); ?> <?php _e('reviews', 'aapi'); ?>)
                <?php endif; ?>
            </p>
            <?php endif; ?>
            
            <p>
                <label>
                    <input type="checkbox" name="aapi_exclude_from_updates" value="1" <?php checked(get_post_meta($post->ID, '_aapi_exclude_from_updates', true), '1'); ?> />
                    <?php _e('Exclude from automatic updates', 'aapi'); ?>
                </label>
            </p>
        </div>
        <?php
    }

    /**
     * Save meta boxes.
     *
     * @since    1.0.0
     * @param    int        $post_id    The post ID.
     * @param    WP_Post    $post       The post object.
     * @param    bool       $update     Whether this is an update.
     */
    public function save_meta_boxes($post_id, $post, $update) {
        // Check nonce
        if (!isset($_POST['aapi_product_nonce']) || !wp_verify_nonce($_POST['aapi_product_nonce'], 'aapi_save_product_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('aapi_manage_products', $post_id)) {
            return;
        }

        // Save fields
        $fields = array(
            'aapi_asin' => '_aapi_asin',
            'aapi_marketplace' => '_aapi_marketplace',
            'aapi_brand' => '_aapi_brand',
            'aapi_features' => '_aapi_features',
            'aapi_url' => '_aapi_url',
            'aapi_price' => '_aapi_price',
            'aapi_currency' => '_aapi_currency',
            'aapi_sale_price' => '_aapi_sale_price',
            'aapi_availability' => '_aapi_availability',
            'aapi_source_override' => '_aapi_source_override',
            'aapi_gallery_images' => '_aapi_gallery_images',
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                
                // Special handling for certain fields
                if ($field === 'aapi_url') {
                    $value = esc_url_raw($value);
                } elseif ($field === 'aapi_features') {
                    $value = sanitize_textarea_field($value);
                } elseif ($field === 'aapi_gallery_images') {
                    $value = json_decode(stripslashes($value), true);
                } else {
                    $value = sanitize_text_field($value);
                }
                
                update_post_meta($post_id, $meta_key, $value);
            }
        }

        // Handle checkboxes
        $checkboxes = array(
            'aapi_prime_eligible' => '_aapi_prime_eligible',
            'aapi_exclude_from_updates' => '_aapi_exclude_from_updates',
        );

        foreach ($checkboxes as $field => $meta_key) {
            $value = isset($_POST[$field]) && $_POST[$field] === '1' ? '1' : '0';
            update_post_meta($post_id, $meta_key, $value);
        }

        // Update product in database table if ASIN is set
        if (!empty($_POST['aapi_asin'])) {
            $this->update_product_table($post_id, $_POST['aapi_asin']);
        }
    }

    /**
     * Update product in database table.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    string    $asin       The ASIN.
     */
    private function update_product_table($post_id, $asin) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aapi_products';
        
        // Check if product exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d",
            $post_id
        ));
        
        $data = array(
            'post_id' => $post_id,
            'asin' => sanitize_text_field($asin),
            'marketplace' => sanitize_text_field($_POST['aapi_marketplace'] ?? 'US'),
            'title' => get_the_title($post_id),
            'last_updated' => current_time('mysql'),
        );
        
        if ($exists) {
            $wpdb->update($table_name, $data, array('post_id' => $post_id));
        } else {
            $wpdb->insert($table_name, $data);
        }
    }

    /**
     * Add custom columns.
     *
     * @since    1.0.0
     * @param    array    $columns    Existing columns.
     * @return   array                Modified columns.
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['asin'] = __('ASIN', 'aapi');
                $new_columns['price'] = __('Price', 'aapi');
                $new_columns['availability'] = __('Availability', 'aapi');
                $new_columns['last_updated'] = __('Last Updated', 'aapi');
            }
        }
        
        return $new_columns;
    }

    /**
     * Render custom columns.
     *
     * @since    1.0.0
     * @param    string    $column     Column name.
     * @param    int       $post_id    Post ID.
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'asin':
                $asin = get_post_meta($post_id, '_aapi_asin', true);
                $marketplace = get_post_meta($post_id, '_aapi_marketplace', true);
                if ($asin) {
                    echo '<code>' . esc_html($asin) . '</code>';
                    if ($marketplace && $marketplace !== 'US') {
                        echo ' <span class="description">(' . esc_html($marketplace) . ')</span>';
                    }
                }
                break;
                
            case 'price':
                $price = get_post_meta($post_id, '_aapi_price', true);
                $currency = get_post_meta($post_id, '_aapi_currency', true);
                $sale_price = get_post_meta($post_id, '_aapi_sale_price', true);
                
                if ($price) {
                    $currency_symbol = $this->get_currency_symbol($currency);
                    
                    if ($sale_price && $sale_price < $price) {
                        echo '<del>' . esc_html($currency_symbol . $price) . '</del> ';
                        echo '<strong class="aapi-sale-price">' . esc_html($currency_symbol . $sale_price) . '</strong>';
                    } else {
                        echo esc_html($currency_symbol . $price);
                    }
                }
                break;
                
            case 'availability':
                $availability = get_post_meta($post_id, '_aapi_availability', true);
                $prime = get_post_meta($post_id, '_aapi_prime_eligible', true);
                
                if ($availability) {
                    $status_class = $availability === 'InStock' ? 'in-stock' : 'out-of-stock';
                    echo '<span class="aapi-availability aapi-' . esc_attr($status_class) . '">';
                    
                    switch ($availability) {
                        case 'InStock':
                            _e('In Stock', 'aapi');
                            break;
                        case 'OutOfStock':
                            _e('Out of Stock', 'aapi');
                            break;
                        case 'PreOrder':
                            _e('Pre-Order', 'aapi');
                            break;
                        case 'LimitedAvailability':
                            _e('Limited', 'aapi');
                            break;
                        default:
                            echo esc_html($availability);
                    }
                    
                    echo '</span>';
                    
                    if ($prime === '1') {
                        echo ' <span class="aapi-prime" title="' . esc_attr__('Prime Eligible', 'aapi') . '">✓</span>';
                    }
                }
                break;
                
            case 'last_updated':
                $last_updated = get_post_meta($post_id, '_aapi_last_updated', true);
                if ($last_updated) {
                    $time_diff = human_time_diff(strtotime($last_updated), current_time('timestamp'));
                    printf(
                        '<span title="%s">%s %s</span>',
                        esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_updated))),
                        esc_html($time_diff),
                        esc_html__('ago', 'aapi')
                    );
                }
                break;
        }
    }

    /**
     * Make columns sortable.
     *
     * @since    1.0.0
     * @param    array    $columns    Sortable columns.
     * @return   array                Modified columns.
     */
    public function sortable_columns($columns) {
        $columns['asin'] = 'asin';
        $columns['price'] = 'price';
        $columns['availability'] = 'availability';
        $columns['last_updated'] = 'last_updated';
        
        return $columns;
    }

    /**
     * Add bulk actions.
     *
     * @since    1.0.0
     * @param    array    $actions    Bulk actions.
     * @return   array                Modified actions.
     */
    public function add_bulk_actions($actions) {
        $actions['aapi_update_products'] = __('Update Product Data', 'aapi');
        $actions['aapi_exclude_updates'] = __('Exclude from Updates', 'aapi');
        $actions['aapi_include_updates'] = __('Include in Updates', 'aapi');
        
        return $actions;
    }

    /**
     * Handle bulk actions.
     *
     * @since    1.0.0
     * @param    string    $redirect_to    Redirect URL.
     * @param    string    $doaction       Action being performed.
     * @param    array     $post_ids       Post IDs.
     * @return   string                    Modified redirect URL.
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if (empty($post_ids)) {
            return $redirect_to;
        }

        switch ($doaction) {
            case 'aapi_update_products':
                // Queue products for update
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_aapi_update_queued', '1');
                }
                
                // Trigger immediate update if possible
                do_action('aapi_bulk_update_products', $post_ids);
                
                $redirect_to = add_query_arg('aapi_updated', count($post_ids), $redirect_to);
                break;
                
            case 'aapi_exclude_updates':
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_aapi_exclude_from_updates', '1');
                }
                $redirect_to = add_query_arg('aapi_excluded', count($post_ids), $redirect_to);
                break;
                
            case 'aapi_include_updates':
                foreach ($post_ids as $post_id) {
                    delete_post_meta($post_id, '_aapi_exclude_from_updates');
                }
                $redirect_to = add_query_arg('aapi_included', count($post_ids), $redirect_to);
                break;
        }

        return $redirect_to;
    }

    /**
     * Get currency symbol.
     *
     * @since    1.0.0
     * @param    string    $currency    Currency code.
     * @return   string                 Currency symbol.
     */
    private function get_currency_symbol($currency) {
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'INR' => '₹',
            'BRL' => 'R$',
            'MXN' => '$',
            'CNY' => '¥',
        );
        
        return isset($symbols[$currency]) ? $symbols[$currency] : $currency . ' ';
    }
}
<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/admin
 */

namespace AAPI\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/admin
 * @author     Your Name <email@example.com>
 */
class Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Admin page hooks.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $page_hooks    Admin page hooks.
     */
    private $page_hooks = array();

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Global admin styles
        wp_enqueue_style(
            $this->plugin_name . '-admin-global',
            AAPI_PLUGIN_URL . 'assets/css/admin-global.css',
            array(),
            $this->version,
            'all'
        );

        // Plugin-specific admin styles
        if ($this->is_plugin_screen($screen)) {
            wp_enqueue_style(
                $this->plugin_name . '-admin',
                AAPI_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                $this->version,
                'all'
            );

            // Page-specific styles
            if (isset($_GET['page'])) {
                $page = sanitize_text_field($_GET['page']);
                
                if ($page === 'aapi-reports') {
                    wp_enqueue_style(
                        $this->plugin_name . '-reports',
                        AAPI_PLUGIN_URL . 'assets/css/admin-reports.css',
                        array(),
                        $this->version,
                        'all'
                    );
                }
            }
        }

        // Product edit screen styles
        if ($screen->post_type === 'amazon_product') {
            wp_enqueue_style('wp-color-picker');
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();

        // Plugin-specific admin scripts
        if ($this->is_plugin_screen($screen)) {
            // Main admin script
            wp_enqueue_script(
                $this->plugin_name . '-admin',
                AAPI_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wp-util'),
                $this->version,
                false
            );

            // Localize script
            wp_localize_script(
                $this->plugin_name . '-admin',
                'aapi_admin',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('aapi_admin_nonce'),
                    'strings' => array(
                        'confirm_delete' => __('Are you sure you want to delete this item?', 'aapi'),
                        'processing' => __('Processing...', 'aapi'),
                        'success' => __('Success!', 'aapi'),
                        'error' => __('An error occurred. Please try again.', 'aapi'),
                        'import_in_progress' => __('Import in progress...', 'aapi'),
                        'api_test_success' => __('API connection successful!', 'aapi'),
                        'api_test_failed' => __('API connection failed. Please check your credentials.', 'aapi'),
                    ),
                    'api_timeout' => AAPI_API_TIMEOUT * 1000, // Convert to milliseconds
                )
            );

            // Page-specific scripts
            if (isset($_GET['page'])) {
                $page = sanitize_text_field($_GET['page']);
                
                switch ($page) {
                    case 'aapi-import':
                        wp_enqueue_script(
                            $this->plugin_name . '-import',
                            AAPI_PLUGIN_URL . 'assets/js/admin-import.js',
                            array('jquery', 'wp-util'),
                            $this->version,
                            false
                        );
                        break;
                        
                    case 'aapi-reports':
                        wp_enqueue_script(
                            $this->plugin_name . '-reports',
                            AAPI_PLUGIN_URL . 'assets/js/admin-reports.js',
                            array('jquery', 'chart-js'),
                            $this->version,
                            false
                        );
                        
                        // Include Chart.js
                        wp_enqueue_script(
                            'chart-js',
                            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                            array(),
                            '3.9.1',
                            false
                        );
                        break;
                }
            }
        }

        // Product edit screen scripts
        if ($screen->post_type === 'amazon_product') {
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();
            
            wp_enqueue_script(
                $this->plugin_name . '-product-edit',
                AAPI_PLUGIN_URL . 'assets/js/admin-product-edit.js',
                array('jquery', 'wp-color-picker'),
                $this->version,
                false
            );
        }
    }

    /**
     * Add plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu
        $this->page_hooks['main'] = add_menu_page(
            __('Amazon Affiliate Pro', 'aapi'),
            __('Amazon Affiliate', 'aapi'),
            'aapi_manage_products',
            'aapi',
            array($this, 'display_admin_dashboard'),
            'dashicons-amazon',
            25
        );

        // Dashboard (same as main)
        $this->page_hooks['dashboard'] = add_submenu_page(
            'aapi',
            __('Dashboard', 'aapi'),
            __('Dashboard', 'aapi'),
            'aapi_manage_products',
            'aapi',
            array($this, 'display_admin_dashboard')
        );

        // Products
        $this->page_hooks['products'] = add_submenu_page(
            'aapi',
            __('Products', 'aapi'),
            __('Products', 'aapi'),
            'aapi_manage_products',
            'edit.php?post_type=amazon_product'
        );

        // Import Products
        $this->page_hooks['import'] = add_submenu_page(
            'aapi',
            __('Import Products', 'aapi'),
            __('Import Products', 'aapi'),
            'aapi_import_products',
            'aapi-import',
            array($this, 'display_import_page')
        );

        // Reports
        $this->page_hooks['reports'] = add_submenu_page(
            'aapi',
            __('Reports & Analytics', 'aapi'),
            __('Reports', 'aapi'),
            'aapi_view_analytics',
            'aapi-reports',
            array($this, 'display_reports_page')
        );

        // Settings
        $this->page_hooks['settings'] = add_submenu_page(
            'aapi',
            __('Settings', 'aapi'),
            __('Settings', 'aapi'),
            'aapi_manage_settings',
            'aapi-settings',
            array($this, 'display_settings_page')
        );

        // Tools
        $this->page_hooks['tools'] = add_submenu_page(
            'aapi',
            __('Tools', 'aapi'),
            __('Tools', 'aapi'),
            'aapi_manage_settings',
            'aapi-tools',
            array($this, 'display_tools_page')
        );

        // Hidden pages
        $this->page_hooks['api_logs'] = add_submenu_page(
            null, // Hidden from menu
            __('API Logs', 'aapi'),
            __('API Logs', 'aapi'),
            'aapi_view_api_logs',
            'aapi-api-logs',
            array($this, 'display_api_logs_page')
        );
    }

    /**
     * Display admin dashboard.
     *
     * @since    1.0.0
     */
    public function display_admin_dashboard() {
        include_once AAPI_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Display import page.
     *
     * @since    1.0.0
     */
    public function display_import_page() {
        include_once AAPI_PLUGIN_DIR . 'admin/views/import.php';
    }

    /**
     * Display reports page.
     *
     * @since    1.0.0
     */
    public function display_reports_page() {
        include_once AAPI_PLUGIN_DIR . 'admin/views/reports.php';
    }

    /**
     * Display settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once AAPI_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Display tools page.
     *
     * @since    1.0.0
     */
    public function display_tools_page() {
        include_once AAPI_PLUGIN_DIR . 'admin/views/tools.php';
    }

    /**
     * Display API logs page.
     *
     * @since    1.0.0
     */
    public function display_api_logs_page() {
        include_once AAPI_PLUGIN_DIR . 'admin/views/api-logs.php';
    }

    /**
     * Display admin notices.
     *
     * @since    1.0.0
     */
    public function display_admin_notices() {
        // Check for activation notice
        if (get_transient('aapi_activated')) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Amazon Affiliate Pro has been activated successfully!', 'aapi'); ?></p>
            </div>
            <?php
            delete_transient('aapi_activated');
        }

        // Check for missing API credentials
        if ($this->is_plugin_screen(get_current_screen())) {
            $api_settings = get_option('aapi_api_settings', array());
            
            if (empty($api_settings['paapi_access_key']) && empty($api_settings['scraper_api_key'])) {
                ?>
                <div class="notice notice-warning">
                    <p>
                        <?php 
                        printf(
                            __('Amazon Affiliate Pro requires API credentials to function. Please <a href="%s">configure your API settings</a>.', 'aapi'),
                            admin_url('admin.php?page=aapi-settings&tab=api')
                        ); 
                        ?>
                    </p>
                </div>
                <?php
            }
        }

        // Display any queued admin notices
        $notices = get_transient('aapi_admin_notices');
        if ($notices && is_array($notices)) {
            foreach ($notices as $notice) {
                $class = isset($notice['type']) ? $notice['type'] : 'info';
                $dismissible = isset($notice['dismissible']) && $notice['dismissible'] ? 'is-dismissible' : '';
                ?>
                <div class="notice notice-<?php echo esc_attr($class); ?> <?php echo esc_attr($dismissible); ?>">
                    <p><?php echo wp_kses_post($notice['message']); ?></p>
                </div>
                <?php
            }
            delete_transient('aapi_admin_notices');
        }
    }

    /**
     * Add plugin action links.
     *
     * @since    1.0.0
     * @param    array    $links    Existing links.
     * @return   array              Modified links.
     */
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=aapi-settings') . '">' . __('Settings', 'aapi') . '</a>',
            '<a href="' . admin_url('admin.php?page=aapi-import') . '">' . __('Import', 'aapi') . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

    /**
     * Add plugin row meta.
     *
     * @since    1.0.0
     * @param    array     $links    Existing links.
     * @param    string    $file     Plugin file.
     * @return   array               Modified links.
     */
    public function add_row_meta($links, $file) {
        if (AAPI_PLUGIN_BASENAME === $file) {
            $row_meta = array(
                'docs' => '<a href="https://docs.example.com/aapi" target="_blank">' . __('Documentation', 'aapi') . '</a>',
                'support' => '<a href="https://support.example.com" target="_blank">' . __('Support', 'aapi') . '</a>',
            );

            return array_merge($links, $row_meta);
        }

        return $links;
    }

    /**
     * Check if current screen is plugin screen.
     *
     * @since    1.0.0
     * @param    WP_Screen    $screen    Current screen object.
     * @return   bool                    True if plugin screen, false otherwise.
     */
    private function is_plugin_screen($screen) {
        if (!$screen) {
            return false;
        }

        // Check if it's our custom post type
        if ($screen->post_type === 'amazon_product') {
            return true;
        }

        // Check if it's one of our admin pages
        if (strpos($screen->id, 'aapi') !== false) {
            return true;
        }

        // Check page parameter
        if (isset($_GET['page']) && strpos($_GET['page'], 'aapi') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Add admin notice.
     *
     * @since    1.0.0
     * @param    string    $message       Notice message.
     * @param    string    $type          Notice type (error, warning, success, info).
     * @param    bool      $dismissible   Whether notice is dismissible.
     */
    public static function add_notice($message, $type = 'info', $dismissible = true) {
        $notices = get_transient('aapi_admin_notices');
        if (!is_array($notices)) {
            $notices = array();
        }

        $notices[] = array(
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
        );

        set_transient('aapi_admin_notices', $notices, 60);
    }
}
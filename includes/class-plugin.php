<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes
 */

namespace AAPI;

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes
 * @author     Your Name <email@example.com>
 */
class Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The plugin instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Plugin    $instance    The plugin instance.
     */
    private static $instance = null;

    /**
     * Plugin components.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $components    Plugin components.
     */
    private $components = array();

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = AAPI_VERSION;
        $this->plugin_name = 'amazon-affiliate-pro';

        $this->load_dependencies();
        $this->set_locale();
        $this->init_components();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_cron_hooks();
    }

    /**
     * Get plugin instance.
     *
     * @since    1.0.0
     * @return   Plugin    The plugin instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters.
         */
        require_once AAPI_PLUGIN_DIR . 'includes/class-loader.php';

        /**
         * The class responsible for defining internationalization functionality.
         */
        require_once AAPI_PLUGIN_DIR . 'includes/class-i18n.php';

        $this->loader = new Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new I18n();
        $plugin_i18n->set_domain('aapi');

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Initialize plugin components.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_components() {
        // Core components - always loaded
        $this->components['settings'] = new Core\Settings();
        $this->components['post_type'] = new Core\Post_Type();
        
        // Conditional components
        if (is_admin()) {
            $this->components['admin'] = new Admin\Admin($this->get_plugin_name(), $this->get_version());
            $this->components['admin_settings'] = new Admin\Settings_Page();
            
            // TODO: Phase 2 - Implement these classes
            // $this->components['product_import'] = new Admin\Product_Import();
            // $this->components['reports'] = new Admin\Reports();
            // $this->components['tools'] = new Admin\Tools();
        } else {
            // TODO: Phase 3 - Implement frontend classes
            // $this->components['frontend'] = new Frontend\Frontend($this->get_plugin_name(), $this->get_version());
            // $this->components['shortcodes'] = new Frontend\Shortcodes();
            // $this->components['tracking'] = new Services\Tracking();
        }
        
        // API components
        $this->components['api_manager'] = new API\API_Manager();
        
        // TODO: Phase 2 - Service components
        // $this->components['cron'] = new Services\Cron();
        // $this->components['cache'] = new Services\Cache();
        
        // Check for CLI
        if (defined('WP_CLI') && WP_CLI) {
            // TODO: Phase 4 - CLI commands
            // $this->components['cli'] = new CLI\Commands();
        }
    }

    /**
     * Register all of the hooks related to the admin area.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        if (!is_admin()) {
            return;
        }

        $admin = $this->components['admin'];

        // Scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');

        // Admin menu
        $this->loader->add_action('admin_menu', $admin, 'add_plugin_admin_menu');
        
        // Admin notices
        $this->loader->add_action('admin_notices', $admin, 'display_admin_notices');
        
        // Plugin action links
        $this->loader->add_filter('plugin_action_links_' . AAPI_PLUGIN_BASENAME, $admin, 'add_action_links');
        $this->loader->add_filter('plugin_row_meta', $admin, 'add_row_meta', 10, 2);

        // Settings
        $settings = $this->components['admin_settings'];
        $this->loader->add_action('admin_init', $settings, 'init_settings');
        
        // AJAX handlers - TODO: Implement in Phase 2
        // $this->loader->add_action('wp_ajax_aapi_import_product', $this->components['product_import'], 'ajax_import_product');
        // $this->loader->add_action('wp_ajax_aapi_search_products', $this->components['product_import'], 'ajax_search_products');
        $this->loader->add_action('wp_ajax_aapi_test_api', $this->components['admin_settings'], 'ajax_test_api');
        
        // Post type customizations
        $post_type = $this->components['post_type'];
        $this->loader->add_action('init', $post_type, 'register_post_type');
        $this->loader->add_action('init', $post_type, 'register_taxonomies');
        $this->loader->add_action('add_meta_boxes', $post_type, 'add_meta_boxes');
        $this->loader->add_action('save_post_amazon_product', $post_type, 'save_meta_boxes', 10, 3);
        
        // Custom columns
        $this->loader->add_filter('manage_amazon_product_posts_columns', $post_type, 'add_custom_columns');
        $this->loader->add_action('manage_amazon_product_posts_custom_column', $post_type, 'render_custom_columns', 10, 2);
        $this->loader->add_filter('manage_edit-amazon_product_sortable_columns', $post_type, 'sortable_columns');
        
        // Bulk actions
        $this->loader->add_filter('bulk_actions-edit-amazon_product', $post_type, 'add_bulk_actions');
        $this->loader->add_filter('handle_bulk_actions-edit-amazon_product', $post_type, 'handle_bulk_actions', 10, 3);
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        // TODO: Phase 3 - Implement frontend functionality
        /*
        $frontend = $this->components['frontend'] ?? null;
        
        if ($frontend) {
            // Scripts and styles
            $this->loader->add_action('wp_enqueue_scripts', $frontend, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $frontend, 'enqueue_scripts');
            
            // Schema markup
            $this->loader->add_action('wp_head', $frontend, 'add_schema_markup');
            
            // Link cloaking
            $this->loader->add_action('init', $frontend, 'add_rewrite_rules');
            $this->loader->add_filter('query_vars', $frontend, 'add_query_vars');
            $this->loader->add_action('template_redirect', $frontend, 'handle_product_redirect');
        }
        
        // Shortcodes (can be used in admin too)
        $shortcodes = $this->components['shortcodes'] ?? null;
        if ($shortcodes) {
            $this->loader->add_action('init', $shortcodes, 'register_shortcodes');
        }
        
        // AJAX handlers for frontend
        $this->loader->add_action('wp_ajax_aapi_track_click', $this->components['tracking'] ?? null, 'ajax_track_click');
        $this->loader->add_action('wp_ajax_nopriv_aapi_track_click', $this->components['tracking'] ?? null, 'ajax_track_click');
        */
        
        // Gutenberg blocks
        $this->loader->add_action('init', $this, 'register_blocks');
    }

    /**
     * Register all cron-related hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_cron_hooks() {
        // TODO: Phase 2 - Implement cron functionality
        /*
        $cron = $this->components['cron'];
        
        // Product updates
        $this->loader->add_action('aapi_update_products', $cron, 'update_products');
        
        // Log cleanup
        $this->loader->add_action('aapi_cleanup_logs', $cron, 'cleanup_logs');
        
        // Price checks
        $this->loader->add_action('aapi_check_prices', $cron, 'check_prices');
        
        // Analytics aggregation
        $this->loader->add_action('aapi_aggregate_analytics', $cron, 'aggregate_analytics');
        
        // Custom cron schedules
        $this->loader->add_filter('cron_schedules', $cron, 'add_cron_schedules');
        */
    }

    /**
     * Register Gutenberg blocks.
     *
     * @since    1.0.0
     */
    public function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }

        // TODO: Phase 3 - Implement Gutenberg blocks
        /*
        // Register block scripts
        wp_register_script(
            'aapi-blocks',
            AAPI_PLUGIN_URL . 'assets/js/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            $this->version,
            true
        );

        // Register block styles
        wp_register_style(
            'aapi-blocks',
            AAPI_PLUGIN_URL . 'assets/css/blocks.css',
            array('wp-edit-blocks'),
            $this->version
        );

        // Register blocks
        register_block_type('aapi/product', array(
            'editor_script' => 'aapi-blocks',
            'editor_style' => 'aapi-blocks',
            'render_callback' => array($this->components['shortcodes'], 'render_product_block'),
            'attributes' => array(
                'asin' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'template' => array(
                    'type' => 'string',
                    'default' => 'default',
                ),
            ),
        ));

        register_block_type('aapi/products-grid', array(
            'editor_script' => 'aapi-blocks',
            'editor_style' => 'aapi-blocks',
            'render_callback' => array($this->components['shortcodes'], 'render_products_grid_block'),
            'attributes' => array(
                'category' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'limit' => array(
                    'type' => 'number',
                    'default' => 4,
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 4,
                ),
            ),
        ));
        */
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks.
     *
     * @since     1.0.0
     * @return    Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get a component instance.
     *
     * @since    1.0.0
     * @param    string    $component    The component name.
     * @return   mixed                   The component instance or null.
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }
}
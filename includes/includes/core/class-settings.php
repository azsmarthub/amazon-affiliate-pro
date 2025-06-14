<?php
/**
 * Core settings functionality
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/core
 */

namespace AAPI\Core;

/**
 * Core settings class.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/core
 * @author     Your Name <email@example.com>
 */
class Settings {

    /**
     * Settings groups.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings_groups    Settings groups.
     */
    private $settings_groups = array();

    /**
     * Settings fields.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings_fields    Settings fields.
     */
    private $settings_fields = array();

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->define_settings();
    }

    /**
     * Define all settings.
     *
     * @since    1.0.0
     */
    private function define_settings() {
        // General settings
        $this->settings_groups['general'] = array(
            'title' => __('General Settings', 'aapi'),
            'description' => __('Configure general plugin settings.', 'aapi'),
            'capability' => 'aapi_manage_settings',
        );

        $this->settings_fields['general'] = array(
            'affiliate_tag' => array(
                'title' => __('Affiliate Tag', 'aapi'),
                'type' => 'text',
                'description' => __('Your Amazon affiliate tracking ID.', 'aapi'),
                'default' => '',
                'sanitize' => 'sanitize_text_field',
            ),
            'default_marketplace' => array(
                'title' => __('Default Marketplace', 'aapi'),
                'type' => 'select',
                'description' => __('Select your default Amazon marketplace.', 'aapi'),
                'options' => $this->get_marketplace_options(),
                'default' => 'US',
                'sanitize' => 'sanitize_text_field',
            ),
            'cache_duration' => array(
                'title' => __('Cache Duration', 'aapi'),
                'type' => 'number',
                'description' => __('How long to cache product data (in seconds).', 'aapi'),
                'default' => 3600,
                'min' => 300,
                'max' => 86400,
                'sanitize' => 'absint',
            ),
            'enable_tracking' => array(
                'title' => __('Enable Click Tracking', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Track clicks on affiliate links.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'enable_schema' => array(
                'title' => __('Enable Schema Markup', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Add structured data markup for better SEO.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'enable_nofollow' => array(
                'title' => __('Add NoFollow to Links', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Add rel="nofollow" to all affiliate links.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'link_cloaking' => array(
                'title' => __('Enable Link Cloaking', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Cloak affiliate links with your domain.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'cloak_prefix' => array(
                'title' => __('Link Cloak Prefix', 'aapi'),
                'type' => 'text',
                'description' => __('URL prefix for cloaked links (e.g., "go" for /go/product-name).', 'aapi'),
                'default' => 'go',
                'sanitize' => 'sanitize_title',
                'condition' => array('link_cloaking', '==', true),
            ),
            'auto_update' => array(
                'title' => __('Auto Update Products', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Automatically update product information.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'update_frequency' => array(
                'title' => __('Update Frequency', 'aapi'),
                'type' => 'select',
                'description' => __('How often to update product information.', 'aapi'),
                'options' => array(
                    'hourly' => __('Hourly', 'aapi'),
                    'twicedaily' => __('Twice Daily', 'aapi'),
                    'daily' => __('Daily', 'aapi'),
                    'weekly' => __('Weekly', 'aapi'),
                ),
                'default' => 'daily',
                'sanitize' => 'sanitize_text_field',
                'condition' => array('auto_update', '==', true),
            ),
        );

        // API settings
        $this->settings_groups['api'] = array(
            'title' => __('API Settings', 'aapi'),
            'description' => __('Configure API credentials and settings.', 'aapi'),
            'capability' => 'aapi_manage_api_keys',
        );

        $this->settings_fields['api'] = array(
            'primary_api' => array(
                'title' => __('Primary API', 'aapi'),
                'type' => 'select',
                'description' => __('Select your primary API provider.', 'aapi'),
                'options' => array(
                    'paapi' => __('Amazon PA-API 5.0', 'aapi'),
                    'rainforest' => __('RainforestAPI', 'aapi'),
                    'serpapi' => __('SerpApi', 'aapi'),
                    'dataforseo' => __('DataForSEO', 'aapi'),
                ),
                'default' => 'paapi',
                'sanitize' => 'sanitize_text_field',
            ),
            'fallback_api' => array(
                'title' => __('Fallback API', 'aapi'),
                'type' => 'select',
                'description' => __('Select a fallback API to use when primary fails.', 'aapi'),
                'options' => array(
                    '' => __('None', 'aapi'),
                    'paapi' => __('Amazon PA-API 5.0', 'aapi'),
                    'rainforest' => __('RainforestAPI', 'aapi'),
                    'serpapi' => __('SerpApi', 'aapi'),
                    'dataforseo' => __('DataForSEO', 'aapi'),
                ),
                'default' => '',
                'sanitize' => 'sanitize_text_field',
            ),
            'paapi_access_key' => array(
                'title' => __('PA-API Access Key', 'aapi'),
                'type' => 'text',
                'description' => __('Your Amazon PA-API access key.', 'aapi'),
                'default' => '',
                'sanitize' => 'sanitize_text_field',
                'encrypted' => true,
            ),
            'paapi_secret_key' => array(
                'title' => __('PA-API Secret Key', 'aapi'),
                'type' => 'password',
                'description' => __('Your Amazon PA-API secret key.', 'aapi'),
                'default' => '',
                'sanitize' => 'sanitize_text_field',
                'encrypted' => true,
            ),
            'paapi_partner_tag' => array(
                'title' => __('PA-API Partner Tag', 'aapi'),
                'type' => 'text',
                'description' => __('Your Amazon partner tag (affiliate ID).', 'aapi'),
                'default' => '',
                'sanitize' => 'sanitize_text_field',
            ),
            'scraper_api_key' => array(
                'title' => __('Scraper API Key', 'aapi'),
                'type' => 'password',
                'description' => __('API key for your selected scraper service.', 'aapi'),
                'default' => '',
                'sanitize' => 'sanitize_text_field',
                'encrypted' => true,
            ),
            'api_timeout' => array(
                'title' => __('API Timeout', 'aapi'),
                'type' => 'number',
                'description' => __('API request timeout in seconds.', 'aapi'),
                'default' => 30,
                'min' => 5,
                'max' => 120,
                'sanitize' => 'absint',
            ),
            'max_retries' => array(
                'title' => __('Max Retries', 'aapi'),
                'type' => 'number',
                'description' => __('Maximum number of API retry attempts.', 'aapi'),
                'default' => 3,
                'min' => 0,
                'max' => 10,
                'sanitize' => 'absint',
            ),
        );

        // Display settings
        $this->settings_groups['display'] = array(
            'title' => __('Display Settings', 'aapi'),
            'description' => __('Configure how products are displayed.', 'aapi'),
            'capability' => 'aapi_manage_settings',
        );

        $this->settings_fields['display'] = array(
            'default_template' => array(
                'title' => __('Default Template', 'aapi'),
                'type' => 'select',
                'description' => __('Default template for displaying products.', 'aapi'),
                'options' => array(
                    'default' => __('Default', 'aapi'),
                    'compact' => __('Compact', 'aapi'),
                    'detailed' => __('Detailed', 'aapi'),
                    'grid' => __('Grid', 'aapi'),
                ),
                'default' => 'default',
                'sanitize' => 'sanitize_text_field',
            ),
            'show_prime_badge' => array(
                'title' => __('Show Prime Badge', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Display Amazon Prime badge on eligible products.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'show_ratings' => array(
                'title' => __('Show Ratings', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Display product ratings and reviews count.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'show_price' => array(
                'title' => __('Show Price', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Display product prices.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'show_savings' => array(
                'title' => __('Show Savings', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Display discount percentage on sale items.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
            'button_text' => array(
                'title' => __('Button Text', 'aapi'),
                'type' => 'text',
                'description' => __('Text for the buy button.', 'aapi'),
                'default' => __('View on Amazon', 'aapi'),
                'sanitize' => 'sanitize_text_field',
            ),
            'out_of_stock_text' => array(
                'title' => __('Out of Stock Text', 'aapi'),
                'type' => 'text',
                'description' => __('Text to display when product is unavailable.', 'aapi'),
                'default' => __('Currently Unavailable', 'aapi'),
                'sanitize' => 'sanitize_text_field',
            ),
            'mobile_responsive' => array(
                'title' => __('Mobile Responsive', 'aapi'),
                'type' => 'checkbox',
                'description' => __('Enable responsive design for mobile devices.', 'aapi'),
                'default' => true,
                'sanitize' => 'rest_sanitize_boolean',
            ),
        );

        // Apply filters
        $this->settings_groups = apply_filters('aapi_settings_groups', $this->settings_groups);
        $this->settings_fields = apply_filters('aapi_settings_fields', $this->settings_fields);
    }

    /**
     * Get marketplace options.
     *
     * @since    1.0.0
     * @return   array    Marketplace options.
     */
    private function get_marketplace_options() {
        return array(
            'US' => __('United States (amazon.com)', 'aapi'),
            'UK' => __('United Kingdom (amazon.co.uk)', 'aapi'),
            'DE' => __('Germany (amazon.de)', 'aapi'),
            'FR' => __('France (amazon.fr)', 'aapi'),
            'JP' => __('Japan (amazon.co.jp)', 'aapi'),
            'CA' => __('Canada (amazon.ca)', 'aapi'),
            'IT' => __('Italy (amazon.it)', 'aapi'),
            'ES' => __('Spain (amazon.es)', 'aapi'),
            'IN' => __('India (amazon.in)', 'aapi'),
            'CN' => __('China (amazon.cn)', 'aapi'),
            'MX' => __('Mexico (amazon.com.mx)', 'aapi'),
            'BR' => __('Brazil (amazon.com.br)', 'aapi'),
            'AU' => __('Australia (amazon.com.au)', 'aapi'),
            'NL' => __('Netherlands (amazon.nl)', 'aapi'),
            'AE' => __('UAE (amazon.ae)', 'aapi'),
            'SG' => __('Singapore (amazon.sg)', 'aapi'),
            'TR' => __('Turkey (amazon.com.tr)', 'aapi'),
            'SA' => __('Saudi Arabia (amazon.sa)', 'aapi'),
            'SE' => __('Sweden (amazon.se)', 'aapi'),
            'PL' => __('Poland (amazon.pl)', 'aapi'),
        );
    }

    /**
     * Get setting value.
     *
     * @since    1.0.0
     * @param    string    $group      Settings group.
     * @param    string    $key        Setting key.
     * @param    mixed     $default    Default value.
     * @return   mixed                 Setting value.
     */
    public function get($group, $key, $default = null) {
        $settings = get_option('aapi_' . $group . '_settings', array());
        
        if (isset($settings[$key])) {
            // Decrypt if needed
            if ($this->is_encrypted_field($group, $key) && !empty($settings[$key])) {
                return $this->decrypt_value($settings[$key]);
            }
            return $settings[$key];
        }

        // Return field default if no custom default provided
        if ($default === null && isset($this->settings_fields[$group][$key]['default'])) {
            return $this->settings_fields[$group][$key]['default'];
        }

        return $default;
    }

    /**
     * Set setting value.
     *
     * @since    1.0.0
     * @param    string    $group    Settings group.
     * @param    string    $key      Setting key.
     * @param    mixed     $value    Setting value.
     * @return   bool                True on success, false on failure.
     */
    public function set($group, $key, $value) {
        $settings = get_option('aapi_' . $group . '_settings', array());
        
        // Sanitize value
        if (isset($this->settings_fields[$group][$key]['sanitize'])) {
            $sanitize_callback = $this->settings_fields[$group][$key]['sanitize'];
            if (is_callable($sanitize_callback)) {
                $value = call_user_func($sanitize_callback, $value);
            }
        }
        
        // Encrypt if needed
        if ($this->is_encrypted_field($group, $key) && !empty($value)) {
            $value = $this->encrypt_value($value);
        }
        
        $settings[$key] = $value;
        
        return update_option('aapi_' . $group . '_settings', $settings);
    }

    /**
     * Get all settings for a group.
     *
     * @since    1.0.0
     * @param    string    $group    Settings group.
     * @return   array               Settings array.
     */
    public function get_group($group) {
        $settings = get_option('aapi_' . $group . '_settings', array());
        
        // Decrypt encrypted fields
        foreach ($settings as $key => $value) {
            if ($this->is_encrypted_field($group, $key) && !empty($value)) {
                $settings[$key] = $this->decrypt_value($value);
            }
        }
        
        // Add defaults for missing values
        if (isset($this->settings_fields[$group])) {
            foreach ($this->settings_fields[$group] as $key => $field) {
                if (!isset($settings[$key]) && isset($field['default'])) {
                    $settings[$key] = $field['default'];
                }
            }
        }
        
        return $settings;
    }

    /**
     * Reset settings group to defaults.
     *
     * @since    1.0.0
     * @param    string    $group    Settings group.
     * @return   bool                True on success, false on failure.
     */
    public function reset_group($group) {
        $defaults = array();
        
        if (isset($this->settings_fields[$group])) {
            foreach ($this->settings_fields[$group] as $key => $field) {
                if (isset($field['default'])) {
                    $defaults[$key] = $field['default'];
                }
            }
        }
        
        return update_option('aapi_' . $group . '_settings', $defaults);
    }

    /**
     * Check if field should be encrypted.
     *
     * @since    1.0.0
     * @param    string    $group    Settings group.
     * @param    string    $key      Setting key.
     * @return   bool                True if encrypted, false otherwise.
     */
    private function is_encrypted_field($group, $key) {
        return isset($this->settings_fields[$group][$key]['encrypted']) 
            && $this->settings_fields[$group][$key]['encrypted'] === true;
    }

    /**
     * Encrypt a value.
     *
     * @since    1.0.0
     * @param    string    $value    Value to encrypt.
     * @return   string              Encrypted value.
     */
    private function encrypt_value($value) {
        if (!defined('LOGGED_IN_KEY') || empty(LOGGED_IN_KEY)) {
            return $value;
        }
        
        $key = substr(hash('sha256', LOGGED_IN_KEY), 0, 32);
        $iv = substr(hash('sha256', LOGGED_IN_SALT), 0, 16);
        
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($encrypted);
    }

    /**
     * Decrypt a value.
     *
     * @since    1.0.0
     * @param    string    $value    Value to decrypt.
     * @return   string              Decrypted value.
     */
    private function decrypt_value($value) {
        if (!defined('LOGGED_IN_KEY') || empty(LOGGED_IN_KEY)) {
            return $value;
        }
        
        $key = substr(hash('sha256', LOGGED_IN_KEY), 0, 32);
        $iv = substr(hash('sha256', LOGGED_IN_SALT), 0, 16);
        
        $decoded = base64_decode($value);
        if ($decoded === false) {
            return $value;
        }
        
        $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);
        
        return $decrypted !== false ? $decrypted : $value;
    }

    /**
     * Get settings groups.
     *
     * @since    1.0.0
     * @return   array    Settings groups.
     */
    public function get_groups() {
        return $this->settings_groups;
    }

    /**
     * Get settings fields.
     *
     * @since    1.0.0
     * @param    string    $group    Settings group.
     * @return   array               Settings fields.
     */
    public function get_fields($group = null) {
        if ($group) {
            return isset($this->settings_fields[$group]) ? $this->settings_fields[$group] : array();
        }
        return $this->settings_fields;
    }
}
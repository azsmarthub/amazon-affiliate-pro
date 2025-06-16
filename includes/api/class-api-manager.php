<?php
/**
 * API Manager - Orchestrates API providers
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/api
 */

namespace AAPI\API;

use AAPI\Core\Settings;

/**
 * API Manager class.
 *
 * Manages multiple API providers, handles fallback logic, load balancing,
 * and provides a unified interface for product data retrieval.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api
 * @author     Your Name <email@example.com>
 */
class API_Manager {

    /**
     * Registered API providers.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $providers    Registered API provider instances.
     */
    private $providers = array();

    /**
     * Provider classes registry.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $provider_classes    Available provider classes.
     */
    private $provider_classes = array();

    /**
     * Active provider instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      API_Provider|null    $active_provider    Active provider instance.
     */
    private $active_provider = null;

    /**
     * Fallback provider instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      API_Provider|null    $fallback_provider    Fallback provider instance.
     */
    private $fallback_provider = null;

    /**
     * Settings instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Settings    $settings    Settings instance.
     */
    private $settings;

    /**
     * Provider statistics.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $statistics    Usage statistics per provider.
     */
    private $statistics = array();

    /**
     * Load balancer mode.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $balancer_mode    Load balancing mode.
     */
    private $balancer_mode = 'priority'; // priority | round-robin | least-used | random

    /**
     * Current round-robin index.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $round_robin_index    Current index for round-robin.
     */
    private $round_robin_index = 0;

    /**
     * Manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      API_Manager    $instance    Singleton instance.
     */
    private static $instance = null;

    /**
     * Get instance.
     *
     * @since    1.0.0
     * @return   API_Manager    Manager instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->settings = new Settings();
        $this->init_provider_registry();
        $this->load_statistics();
        $this->init_providers();
    }

    /**
     * Initialize provider registry.
     *
     * @since    1.0.0
     */
    private function init_provider_registry() {
        // Default provider classes
        $this->provider_classes = array(
            'paapi' => array(
                'class' => 'AAPI\API\Providers\PA_API',
                'name' => 'Amazon PA-API 5.0',
                'priority' => 10,
                'capabilities' => array('product', 'search', 'variations', 'categories'),
            ),
            'rainforest' => array(
                'class' => 'AAPI\API\Providers\Rainforest_API',
                'name' => 'RainforestAPI',
                'priority' => 20,
                'capabilities' => array('product', 'search', 'offers', 'reviews'),
            ),
            'serpapi' => array(
                'class' => 'AAPI\API\Providers\Serp_API',
                'name' => 'SerpApi',
                'priority' => 30,
                'capabilities' => array('product', 'search'),
            ),
            'dataforseo' => array(
                'class' => 'AAPI\API\Providers\DataForSEO_API',
                'name' => 'DataForSEO',
                'priority' => 40,
                'capabilities' => array('product', 'search', 'reviews'),
            ),
        );

        // Allow plugins/themes to register additional providers
        $this->provider_classes = apply_filters('aapi_register_api_providers', $this->provider_classes);
    }

    /**
     * Initialize configured providers.
     *
     * @since    1.0.0
     */
    private function init_providers() {
        // Get primary provider
        $primary = $this->settings->get('api', 'primary_api', 'paapi');
        if ($primary && isset($this->provider_classes[$primary])) {
            $this->active_provider = $this->load_provider($primary);
        }

        // Get fallback provider
        $fallback = $this->settings->get('api', 'fallback_api', '');
        if ($fallback && isset($this->provider_classes[$fallback])) {
            $this->fallback_provider = $this->load_provider($fallback);
        }

        // Load balancer mode
        $this->balancer_mode = $this->settings->get('api', 'load_balancer_mode', 'priority');

        // Load all configured providers for load balancing
        if ($this->balancer_mode !== 'priority') {
            $this->load_all_configured_providers();
        }
    }

    /**
     * Load a provider instance.
     *
     * @since    1.0.0
     * @param    string    $provider_key    Provider key.
     * @return   API_Provider|null          Provider instance or null.
     */
    private function load_provider(string $provider_key): ?API_Provider {
        if (!isset($this->provider_classes[$provider_key])) {
            return null;
        }

        $provider_info = $this->provider_classes[$provider_key];
        $class_name = $provider_info['class'];

        // Check if class exists
        if (!class_exists($class_name)) {
            // Try to load provider file
            $this->load_provider_class($provider_key);
            
            if (!class_exists($class_name)) {
                $this->log_error("Provider class not found: $class_name");
                return null;
            }
        }

        // Get credentials
        $credentials = $this->get_provider_credentials($provider_key);
        
        try {
            $provider = new $class_name($credentials);
            
            // Store in providers array
            $this->providers[$provider_key] = $provider;
            
            return $provider;
            
        } catch (\Exception $e) {
            $this->log_error("Failed to initialize provider $provider_key: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Load provider class file.
     *
     * @since    1.0.0
     * @param    string    $provider_key    Provider key.
     */
    private function load_provider_class(string $provider_key) {
        $file_name = 'class-' . str_replace('_', '-', $provider_key) . '.php';
        $file_path = AAPI_PLUGIN_DIR . 'includes/api/providers/' . $file_name;
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }

    /**
     * Get provider credentials.
     *
     * @since    1.0.0
     * @param    string    $provider_key    Provider key.
     * @return   array                      Credentials array.
     */
    private function get_provider_credentials(string $provider_key): array {
        $credentials = array();
        
        switch ($provider_key) {
            case 'paapi':
                $credentials = array(
                    'api_key' => $this->settings->get('api', 'paapi_access_key', ''),
                    'api_secret' => $this->settings->get('api', 'paapi_secret_key', ''),
                    'partner_tag' => $this->settings->get('api', 'paapi_partner_tag', ''),
                    'marketplace' => $this->settings->get('general', 'default_marketplace', 'US'),
                );
                break;
                
            case 'rainforest':
            case 'serpapi':
            case 'dataforseo':
                $credentials = array(
                    'api_key' => $this->settings->get('api', $provider_key . '_api_key', ''),
                );
                break;
        }
        
        return apply_filters('aapi_provider_credentials_' . $provider_key, $credentials);
    }

    /**
     * Load all configured providers.
     *
     * @since    1.0.0
     */
    private function load_all_configured_providers() {
        foreach ($this->provider_classes as $key => $info) {
            if (!isset($this->providers[$key])) {
                $credentials = $this->get_provider_credentials($key);
                
                // Only load if credentials exist
                if (!empty(array_filter($credentials))) {
                    $this->load_provider($key);
                }
            }
        }
    }

    /**
     * Get product by ASIN.
     *
     * @since    1.0.0
     * @param    string    $asin         Product ASIN.
     * @param    array     $options      Request options.
     * @return   array|null              Product data or null.
     */
    public function get_product(string $asin, array $options = []): ?array {
        return $this->execute_with_fallback('get_product', array($asin, $options));
    }

    /**
     * Search products.
     *
     * @since    1.0.0
     * @param    string    $keyword      Search keyword.
     * @param    array     $options      Search options.
     * @return   array                   Search results.
     */
    public function search_products(string $keyword, array $options = []): array {
        $result = $this->execute_with_fallback('search_products', array($keyword, $options));
        
        if ($result === null) {
            return array(
                'success' => false,
                'products' => array(),
                'error' => 'All API providers failed',
            );
        }
        
        return $result;
    }

    /**
     * Get multiple products.
     *
     * @since    1.0.0
     * @param    array    $asins        Array of ASINs.
     * @param    array    $options      Request options.
     * @return   array                  Products data.
     */
    public function get_multiple_products(array $asins, array $options = []): array {
        // Chunk ASINs if needed (most APIs have limits)
        $chunks = array_chunk($asins, 50);
        $all_products = array();
        $failed = array();
        
        foreach ($chunks as $chunk) {
            $result = $this->execute_with_fallback('get_multiple_products', array($chunk, $options));
            
            if ($result && is_array($result)) {
                // Merge successful products
                foreach ($result as $asin => $product) {
                    if ($asin !== 'failed' && $product !== null) {
                        $all_products[$asin] = $product;
                    }
                }
                
                // Track failed ASINs
                if (isset($result['failed'])) {
                    $failed = array_merge($failed, $result['failed']);
                }
            } else {
                // All ASINs in this chunk failed
                $failed = array_merge($failed, $chunk);
            }
        }
        
        // Add failed ASINs
        if (!empty($failed)) {
            $all_products['failed'] = array_unique($failed);
        }
        
        return $all_products;
    }

    /**
     * Execute method with fallback.
     *
     * @since    1.0.0
     * @param    string    $method       Method name.
     * @param    array     $args         Method arguments.
     * @return   mixed                   Method result.
     */
    private function execute_with_fallback(string $method, array $args) {
        $provider = $this->get_active_provider_for_method($method);
        
        if (!$provider) {
            $this->log_error("No provider available for method: $method");
            return null;
        }
        
        // Try primary provider
        try {
            $start_time = microtime(true);
            $result = call_user_func_array(array($provider, $method), $args);
            
            // Update statistics
            $this->update_statistics($this->get_provider_key($provider), true, microtime(true) - $start_time);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->log_error("Primary provider failed: " . $e->getMessage());
            
            // Update statistics
            $this->update_statistics($this->get_provider_key($provider), false);
            
            // Try fallback if available
            if ($this->fallback_provider && $this->fallback_provider !== $provider) {
                try {
                    $this->log_info("Attempting fallback provider");
                    
                    $start_time = microtime(true);
                    $result = call_user_func_array(array($this->fallback_provider, $method), $args);
                    
                    // Update statistics
                    $this->update_statistics($this->get_provider_key($this->fallback_provider), true, microtime(true) - $start_time);
                    
                    return $result;
                    
                } catch (\Exception $fallback_e) {
                    $this->log_error("Fallback provider also failed: " . $fallback_e->getMessage());
                    $this->update_statistics($this->get_provider_key($this->fallback_provider), false);
                }
            }
            
            // Try any other available providers
            return $this->try_remaining_providers($method, $args, array($provider, $this->fallback_provider));
        }
    }

    /**
     * Get active provider for method based on load balancing.
     *
     * @since    1.0.0
     * @param    string    $method    Method name.
     * @return   API_Provider|null     Provider instance.
     */
    private function get_active_provider_for_method(string $method): ?API_Provider {
        switch ($this->balancer_mode) {
            case 'round-robin':
                return $this->get_round_robin_provider($method);
                
            case 'least-used':
                return $this->get_least_used_provider($method);
                
            case 'random':
                return $this->get_random_provider($method);
                
            case 'priority':
            default:
                return $this->active_provider;
        }
    }

    /**
     * Get provider using round-robin.
     *
     * @since    1.0.0
     * @param    string    $method    Method name.
     * @return   API_Provider|null     Provider instance.
     */
    private function get_round_robin_provider(string $method): ?API_Provider {
        $available = $this->get_providers_supporting_method($method);
        
        if (empty($available)) {
            return null;
        }
        
        $providers = array_values($available);
        $provider = $providers[$this->round_robin_index % count($providers)];
        
        $this->round_robin_index++;
        
        return $provider;
    }

    /**
     * Get least used provider.
     *
     * @since    1.0.0
     * @param    string    $method    Method name.
     * @return   API_Provider|null     Provider instance.
     */
    private function get_least_used_provider(string $method): ?API_Provider {
        $available = $this->get_providers_supporting_method($method);
        
        if (empty($available)) {
            return null;
        }
        
        $least_used_key = null;
        $min_usage = PHP_INT_MAX;
        
        foreach ($available as $key => $provider) {
            $usage = $this->statistics[$key]['total_requests'] ?? 0;
            if ($usage < $min_usage) {
                $min_usage = $usage;
                $least_used_key = $key;
            }
        }
        
        return $least_used_key ? $available[$least_used_key] : null;
    }

    /**
     * Get random provider.
     *
     * @since    1.0.0
     * @param    string    $method    Method name.
     * @return   API_Provider|null     Provider instance.
     */
    private function get_random_provider(string $method): ?API_Provider {
        $available = $this->get_providers_supporting_method($method);
        
        if (empty($available)) {
            return null;
        }
        
        $keys = array_keys($available);
        $random_key = $keys[array_rand($keys)];
        
        return $available[$random_key];
    }

    /**
     * Get providers supporting a method.
     *
     * @since    1.0.0
     * @param    string    $method    Method name.
     * @return   array                Providers supporting the method.
     */
    private function get_providers_supporting_method(string $method): array {
        $supporting = array();
        
        foreach ($this->providers as $key => $provider) {
            if (method_exists($provider, $method)) {
                $supporting[$key] = $provider;
            }
        }
        
        return $supporting;
    }

    /**
     * Try remaining providers.
     *
     * @since    1.0.0
     * @param    string    $method          Method name.
     * @param    array     $args            Method arguments.
     * @param    array     $tried_providers Already tried providers.
     * @return   mixed                      Method result or null.
     */
    private function try_remaining_providers(string $method, array $args, array $tried_providers) {
        foreach ($this->providers as $key => $provider) {
            // Skip already tried providers
            if (in_array($provider, $tried_providers, true)) {
                continue;
            }
            
            // Check if provider supports the method
            if (!method_exists($provider, $method)) {
                continue;
            }
            
            try {
                $this->log_info("Trying provider: $key");
                
                $start_time = microtime(true);
                $result = call_user_func_array(array($provider, $method), $args);
                
                // Update statistics
                $this->update_statistics($key, true, microtime(true) - $start_time);
                
                return $result;
                
            } catch (\Exception $e) {
                $this->log_error("Provider $key failed: " . $e->getMessage());
                $this->update_statistics($key, false);
            }
        }
        
        return null;
    }

    /**
     * Get provider key.
     *
     * @since    1.0.0
     * @param    API_Provider    $provider    Provider instance.
     * @return   string|null                  Provider key or null.
     */
    private function get_provider_key(API_Provider $provider): ?string {
        foreach ($this->providers as $key => $instance) {
            if ($instance === $provider) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Update provider statistics.
     *
     * @since    1.0.0
     * @param    string    $provider_key    Provider key.
     * @param    bool      $success         Success status.
     * @param    float     $response_time   Response time in seconds.
     */
    private function update_statistics(string $provider_key, bool $success, float $response_time = 0) {
        if (!isset($this->statistics[$provider_key])) {
            $this->statistics[$provider_key] = array(
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_response_time' => 0,
                'last_used' => null,
            );
        }
        
        $this->statistics[$provider_key]['total_requests']++;
        
        if ($success) {
            $this->statistics[$provider_key]['successful_requests']++;
        } else {
            $this->statistics[$provider_key]['failed_requests']++;
        }
        
        $this->statistics[$provider_key]['total_response_time'] += $response_time;
        $this->statistics[$provider_key]['last_used'] = current_time('mysql');
        
        // Save statistics periodically
        if ($this->statistics[$provider_key]['total_requests'] % 10 === 0) {
            $this->save_statistics();
        }
    }

    /**
     * Load statistics from database.
     *
     * @since    1.0.0
     */
    private function load_statistics() {
        $this->statistics = get_option('aapi_provider_statistics', array());
    }

    /**
     * Save statistics to database.
     *
     * @since    1.0.0
     */
    private function save_statistics() {
        update_option('aapi_provider_statistics', $this->statistics);
    }

    /**
     * Test API connection.
     *
     * @since    1.0.0
     * @param    string    $provider    Provider to test (optional).
     * @return   array                  Test results.
     */
    public function test_connection(string $provider = ''): array {
        $results = array();
        
        if ($provider && isset($this->providers[$provider])) {
            // Test specific provider
            $results[$provider] = $this->test_provider_connection($this->providers[$provider]);
        } else {
            // Test all configured providers
            foreach ($this->providers as $key => $instance) {
                $results[$key] = $this->test_provider_connection($instance);
            }
        }
        
        return $results;
    }

    /**
     * Test provider connection.
     *
     * @since    1.0.0
     * @param    API_Provider    $provider    Provider instance.
     * @return   array                        Test result.
     */
    private function test_provider_connection(API_Provider $provider): array {
        try {
            $result = $provider->test_connection();
            return array_merge(array('provider' => get_class($provider)), $result);
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'provider' => get_class($provider),
                'message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            );
        }
    }

    /**
     * Get quota information.
     *
     * @since    1.0.0
     * @return   array    Quota information for all providers.
     */
    public function get_quota_info(): array {
        $quota_info = array();
        
        foreach ($this->providers as $key => $provider) {
            try {
                $quota_info[$key] = $provider->get_quota_info();
            } catch (\Exception $e) {
                $quota_info[$key] = array(
                    'error' => $e->getMessage(),
                );
            }
        }
        
        return $quota_info;
    }

    /**
     * Get supported marketplaces.
     *
     * @since    1.0.0
     * @return   array    Supported marketplaces.
     */
    public function get_supported_marketplaces(): array {
        // Get from primary provider or use defaults
        if ($this->active_provider) {
            try {
                return $this->active_provider->get_supported_marketplaces();
            } catch (\Exception $e) {
                // Fall through to defaults
            }
        }
        
        // Default marketplaces
        return array(
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'JP' => 'Japan',
            'CA' => 'Canada',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'IN' => 'India',
            'MX' => 'Mexico',
            'BR' => 'Brazil',
            'AU' => 'Australia',
            'NL' => 'Netherlands',
            'AE' => 'UAE',
            'SG' => 'Singapore',
            'TR' => 'Turkey',
            'SA' => 'Saudi Arabia',
            'SE' => 'Sweden',
            'PL' => 'Poland',
        );
    }

    /**
     * Get provider information.
     *
     * @since    1.0.0
     * @return   array    Information about all providers.
     */
    public function get_providers_info(): array {
        $info = array();
        
        foreach ($this->provider_classes as $key => $class_info) {
            $info[$key] = array(
                'name' => $class_info['name'],
                'priority' => $class_info['priority'],
                'capabilities' => $class_info['capabilities'],
                'configured' => isset($this->providers[$key]),
                'active' => $this->get_provider_key($this->active_provider) === $key,
                'fallback' => $this->get_provider_key($this->fallback_provider) === $key,
            );
            
            // Add statistics if available
            if (isset($this->statistics[$key])) {
                $stats = $this->statistics[$key];
                $info[$key]['statistics'] = array(
                    'total_requests' => $stats['total_requests'],
                    'success_rate' => $stats['total_requests'] > 0 
                        ? round(($stats['successful_requests'] / $stats['total_requests']) * 100, 2) 
                        : 0,
                    'avg_response_time' => $stats['total_requests'] > 0
                        ? round($stats['total_response_time'] / $stats['total_requests'], 3)
                        : 0,
                    'last_used' => $stats['last_used'],
                );
            }
        }
        
        return $info;
    }

    /**
     * Clear provider cache.
     *
     * @since    1.0.0
     * @param    string|null    $provider    Provider key or null for all.
     * @return   bool                        Success status.
     */
    public function clear_cache(?string $provider = null): bool {
        if ($provider && isset($this->providers[$provider])) {
            return $this->providers[$provider]->clear_cache();
        }
        
        // Clear all provider caches
        $success = true;
        foreach ($this->providers as $instance) {
            if (!$instance->clear_cache()) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Register a new provider.
     *
     * @since    1.0.0
     * @param    string    $key           Provider key.
     * @param    array     $provider_info Provider information.
     * @return   bool                     Success status.
     */
    public function register_provider(string $key, array $provider_info): bool {
        if (isset($this->provider_classes[$key])) {
            return false; // Already registered
        }
        
        $required = array('class', 'name', 'capabilities');
        foreach ($required as $field) {
            if (!isset($provider_info[$field])) {
                return false;
            }
        }
        
        $this->provider_classes[$key] = array_merge(
            array(
                'priority' => 50,
            ),
            $provider_info
        );
        
        return true;
    }

    /**
     * Log error message.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    private function log_error(string $message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AAPI API Manager] ERROR: ' . $message);
        }
    }

    /**
     * Log info message.
     *
     * @since    1.0.0
     * @param    string    $message    Info message.
     */
    private function log_info(string $message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AAPI API Manager] INFO: ' . $message);
        }
    }
}
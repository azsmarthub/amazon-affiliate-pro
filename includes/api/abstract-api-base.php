<?php
/**
 * Abstract Base API Class
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
 * Abstract base class for API providers.
 *
 * This class provides common functionality that all API providers can inherit,
 * including rate limiting, caching, retry logic, and error handling.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api
 * @author     Your Name <email@example.com>
 */
abstract class API_Base implements API_Provider {

    /**
     * API credentials.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $credentials    API credentials.
     */
    protected $credentials = array();

    /**
     * Provider settings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $settings    Provider-specific settings.
     */
    protected $settings = array();

    /**
     * Last error information.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array|null    $last_error    Last error details.
     */
    protected $last_error = null;

    /**
     * Rate limiter data.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $rate_limits    Rate limiting information.
     */
    protected $rate_limits = array();

    /**
     * Cache prefix.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $cache_prefix    Cache key prefix.
     */
    protected $cache_prefix = 'aapi_';

    /**
     * Provider name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $provider_name    Provider identifier.
     */
    protected $provider_name = '';

    /**
     * API version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_version    API version.
     */
    protected $api_version = '1.0';

    /**
     * Default request timeout.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $timeout    Request timeout in seconds.
     */
    protected $timeout = 30;

    /**
     * Maximum retry attempts.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $max_retries    Maximum number of retries.
     */
    protected $max_retries = 3;

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      object    $logger    Logger instance.
     */
    protected $logger;

    /**
     * Settings instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Settings    $plugin_settings    Plugin settings instance.
     */
    protected $plugin_settings;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    array    $credentials    API credentials.
     */
    public function __construct(array $credentials = array()) {
        $this->credentials = $credentials;
        $this->plugin_settings = new Settings();
        $this->initialize();
    }

    /**
     * Initialize the provider.
     *
     * @since    1.0.0
     */
    protected function initialize() {
        // Load provider settings
        $this->load_settings();
        
        // Set cache prefix
        $this->cache_prefix = 'aapi_' . $this->provider_name . '_';
        
        // Initialize rate limiter
        $this->init_rate_limiter();
        
        // Set timeout from settings
        $this->timeout = $this->plugin_settings->get('api', 'api_timeout', 30);
        $this->max_retries = $this->plugin_settings->get('api', 'max_retries', 3);
    }

    /**
     * Make an API request with retry logic.
     *
     * @since    1.0.0
     * @param    string    $endpoint      API endpoint.
     * @param    array     $params        Request parameters.
     * @param    string    $method        HTTP method (GET, POST, etc.).
     * @param    array     $headers       Additional headers.
     * @return   array                    Response data.
     * @throws   API_Exception           If request fails after retries.
     */
    protected function make_request(string $endpoint, array $params = array(), string $method = 'GET', array $headers = array()): array {
        $attempt = 0;
        $last_exception = null;
        
        // Check rate limits before making request
        if (!$this->check_rate_limit($endpoint)) {
            throw new Quota_Exception('Rate limit exceeded. Please try again later.');
        }
        
        while ($attempt < $this->max_retries) {
            $attempt++;
            
            try {
                // Log request
                $this->log_request($endpoint, $params, $method);
                
                // Make the actual request (to be implemented by child classes)
                $response = $this->execute_request($endpoint, $params, $method, $headers);
                
                // Update rate limiter
                $this->update_rate_limit($endpoint);
                
                // Log successful response
                $this->log_response($endpoint, $response, true);
                
                // Clear any previous errors
                $this->last_error = null;
                
                return $response;
                
            } catch (\Exception $e) {
                $last_exception = $e;
                
                // Log failed attempt
                $this->log_response($endpoint, array('error' => $e->getMessage()), false);
                
                // Check if we should retry
                if (!$this->should_retry($e, $attempt)) {
                    break;
                }
                
                // Wait before retrying (exponential backoff)
                $this->wait_before_retry($attempt);
            }
        }
        
        // All retries failed
        $this->set_last_error($last_exception);
        
        throw new API_Exception(
            sprintf('API request failed after %d attempts: %s', $attempt, $last_exception->getMessage()),
            $last_exception->getCode(),
            $last_exception
        );
    }

    /**
     * Execute the actual HTTP request.
     * Must be implemented by child classes.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     * @param    array     $params      Request parameters.
     * @param    string    $method      HTTP method.
     * @param    array     $headers     Request headers.
     * @return   array                  Response data.
     */
    abstract protected function execute_request(string $endpoint, array $params, string $method, array $headers): array;

    /**
     * Parse API response.
     * Can be overridden by child classes for custom parsing.
     *
     * @since    1.0.0
     * @param    mixed     $response    Raw response data.
     * @return   array                  Parsed response.
     */
    protected function parse_response($response): array {
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return (array) $response;
    }

    /**
     * Get data from cache.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   mixed              Cached data or false.
     */
    protected function get_cached(string $key) {
        $cache_key = $this->cache_prefix . $key;
        return get_transient($cache_key);
    }

    /**
     * Set data in cache.
     *
     * @since    1.0.0
     * @param    string    $key       Cache key.
     * @param    mixed     $data      Data to cache.
     * @param    int       $expiry    Expiration time in seconds.
     * @return   bool                 Success status.
     */
    protected function set_cache(string $key, $data, int $expiry = 3600): bool {
        $cache_key = $this->cache_prefix . $key;
        $cache_duration = $this->plugin_settings->get('general', 'cache_duration', $expiry);
        
        return set_transient($cache_key, $data, $cache_duration);
    }

    /**
     * Generate cache key for a request.
     *
     * @since    1.0.0
     * @param    string    $type      Request type.
     * @param    array     $params    Request parameters.
     * @return   string               Cache key.
     */
    protected function generate_cache_key(string $type, array $params): string {
        // Sort params for consistent keys
        ksort($params);
        return $type . '_' . md5(serialize($params));
    }

    /**
     * Check rate limits.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     * @return   bool                   True if within limits.
     */
    protected function check_rate_limit(string $endpoint): bool {
        $limit_key = $this->cache_prefix . 'rate_limit_' . md5($endpoint);
        $current = get_transient($limit_key);
        
        if ($current === false) {
            return true;
        }
        
        $limit = $this->get_endpoint_rate_limit($endpoint);
        return $current < $limit['requests'];
    }

    /**
     * Update rate limit counter.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     */
    protected function update_rate_limit(string $endpoint): void {
        $limit_key = $this->cache_prefix . 'rate_limit_' . md5($endpoint);
        $current = get_transient($limit_key);
        
        if ($current === false) {
            $current = 0;
        }
        
        $current++;
        $limit = $this->get_endpoint_rate_limit($endpoint);
        
        set_transient($limit_key, $current, $limit['window']);
    }

    /**
     * Get rate limit for endpoint.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     * @return   array                  Rate limit info.
     */
    protected function get_endpoint_rate_limit(string $endpoint): array {
        // Default rate limit (can be overridden by child classes)
        return array(
            'requests' => 10,
            'window' => 60, // 1 minute
        );
    }

    /**
     * Initialize rate limiter.
     *
     * @since    1.0.0
     */
    protected function init_rate_limiter(): void {
        // Initialize rate limits from settings or defaults
        $this->rate_limits = apply_filters(
            'aapi_' . $this->provider_name . '_rate_limits',
            $this->get_default_rate_limits()
        );
    }

    /**
     * Get default rate limits.
     *
     * @since    1.0.0
     * @return   array    Default rate limits.
     */
    protected function get_default_rate_limits(): array {
        return array(
            'global' => array(
                'requests' => 100,
                'window' => 3600, // 1 hour
            ),
            'search' => array(
                'requests' => 20,
                'window' => 60, // 1 minute
            ),
            'product' => array(
                'requests' => 50,
                'window' => 60, // 1 minute
            ),
        );
    }

    /**
     * Check if we should retry after an error.
     *
     * @since    1.0.0
     * @param    \Exception    $exception    The exception.
     * @param    int           $attempt      Current attempt number.
     * @return   bool                        Whether to retry.
     */
    protected function should_retry(\Exception $exception, int $attempt): bool {
        // Don't retry if max attempts reached
        if ($attempt >= $this->max_retries) {
            return false;
        }
        
        // Retry on network errors
        if ($exception instanceof \RuntimeException) {
            return true;
        }
        
        // Retry on specific HTTP codes
        if ($exception instanceof API_Exception) {
            $code = $exception->getCode();
            $retryable_codes = array(429, 500, 502, 503, 504);
            return in_array($code, $retryable_codes);
        }
        
        return false;
    }

    /**
     * Wait before retrying (exponential backoff).
     *
     * @since    1.0.0
     * @param    int    $attempt    Attempt number.
     */
    protected function wait_before_retry(int $attempt): void {
        $wait_time = min(pow(2, $attempt - 1), 30); // Max 30 seconds
        sleep($wait_time);
    }

    /**
     * Log API request.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     * @param    array     $params      Request parameters.
     * @param    string    $method      HTTP method.
     */
    protected function log_request(string $endpoint, array $params, string $method): void {
        if (!$this->should_log()) {
            return;
        }
        
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'aapi_api_logs',
            array(
                'api_provider' => $this->provider_name,
                'endpoint' => $endpoint,
                'request_type' => $method,
                'request_data' => json_encode($params),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Log API response.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     * @param    array     $response    Response data.
     * @param    bool      $success     Success status.
     */
    protected function log_response(string $endpoint, array $response, bool $success): void {
        if (!$this->should_log()) {
            return;
        }
        
        global $wpdb;
        
        // Update the last log entry
        $wpdb->update(
            $wpdb->prefix . 'aapi_api_logs',
            array(
                'response_code' => $success ? 200 : 500,
                'response_message' => $success ? 'Success' : ($response['error'] ?? 'Unknown error'),
                'credits_used' => $this->calculate_credits_used($endpoint),
                'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            ),
            array(
                'api_provider' => $this->provider_name,
                'endpoint' => $endpoint,
            ),
            array('%d', '%s', '%d', '%f'),
            array('%s', '%s')
        );
    }

    /**
     * Check if logging is enabled.
     *
     * @since    1.0.0
     * @return   bool    Whether to log.
     */
    protected function should_log(): bool {
        return $this->plugin_settings->get('api', 'enable_api_logging', true);
    }

    /**
     * Calculate credits used for a request.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     * @return   int                    Credits used.
     */
    protected function calculate_credits_used(string $endpoint): int {
        // Default implementation (can be overridden)
        return 1;
    }

    /**
     * Load provider settings.
     *
     * @since    1.0.0
     */
    protected function load_settings(): void {
        $settings_key = 'aapi_provider_' . $this->provider_name . '_settings';
        $this->settings = get_option($settings_key, array());
    }

    /**
     * Normalize product data to standard format.
     *
     * @since    1.0.0
     * @param    array    $raw_data    Raw product data.
     * @return   array                 Normalized data.
     */
    protected function normalize_product_data(array $raw_data): array {
        // Base normalization (child classes should extend this)
        return array(
            'asin' => $raw_data['asin'] ?? '',
            'title' => $raw_data['title'] ?? '',
            'description' => $raw_data['description'] ?? '',
            'price' => floatval($raw_data['price'] ?? 0),
            'currency' => $raw_data['currency'] ?? 'USD',
            'availability' => $raw_data['availability'] ?? 'Unknown',
            'url' => $raw_data['url'] ?? '',
            'image_url' => $raw_data['image_url'] ?? '',
            'rating' => floatval($raw_data['rating'] ?? 0),
            'reviews_count' => intval($raw_data['reviews_count'] ?? 0),
            'is_prime' => (bool) ($raw_data['is_prime'] ?? false),
            'updated_at' => current_time('mysql'),
        );
    }

    /**
     * Set last error.
     *
     * @since    1.0.0
     * @param    \Exception    $exception    The exception.
     */
    protected function set_last_error(\Exception $exception): void {
        $this->last_error = array(
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'details' => method_exists($exception, 'getDetails') ? $exception->getDetails() : null,
            'timestamp' => current_time('mysql'),
        );
    }

    /**
     * Get last error information.
     *
     * @since    1.0.0
     * @return   array|null    Error information or null.
     */
    public function get_last_error(): ?array {
        return $this->last_error;
    }

    /**
     * Clear cache.
     *
     * @since    1.0.0
     * @param    string|null    $cache_key    Specific cache key or null for all.
     * @return   bool                         Success status.
     */
    public function clear_cache(?string $cache_key = null): bool {
        global $wpdb;
        
        if ($cache_key !== null) {
            return delete_transient($this->cache_prefix . $cache_key);
        }
        
        // Clear all cache for this provider
        $pattern = '_transient_' . $this->cache_prefix . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            )
        );
        
        return true;
    }

    /**
     * Get provider settings.
     *
     * @since    1.0.0
     * @return   array    Provider settings.
     */
    public function get_settings(): array {
        return $this->settings;
    }

    /**
     * Update provider settings.
     *
     * @since    1.0.0
     * @param    array    $settings    New settings.
     * @return   bool                  Success status.
     */
    public function update_settings(array $settings): bool {
        $this->settings = array_merge($this->settings, $settings);
        $settings_key = 'aapi_provider_' . $this->provider_name . '_settings';
        
        return update_option($settings_key, $this->settings);
    }

    /**
     * Set API credentials.
     *
     * @since    1.0.0
     * @param    array    $credentials    API credentials.
     * @throws   Auth_Exception            If credentials are invalid.
     */
    public function set_credentials(array $credentials): void {
        // Validate credentials (child classes should extend this)
        if (!$this->validate_credentials($credentials)) {
            throw new Auth_Exception('Invalid API credentials provided.');
        }
        
        $this->credentials = $credentials;
    }

    /**
     * Validate API credentials.
     *
     * @since    1.0.0
     * @param    array    $credentials    Credentials to validate.
     * @return   bool                     Valid status.
     */
    protected function validate_credentials(array $credentials): bool {
        // Basic validation (child classes should implement specific validation)
        return !empty($credentials);
    }

    /**
     * Get API endpoint URL.
     * Must be implemented by child classes.
     *
     * @since    1.0.0
     * @param    string    $endpoint       Endpoint path.
     * @param    array     $params         Query parameters.
     * @return   string                    Full endpoint URL.
     */
    abstract protected function get_endpoint_url(string $endpoint, array $params = array()): string;

    /**
     * Get request headers.
     * Can be overridden by child classes.
     *
     * @since    1.0.0
     * @return   array    Request headers.
     */
    protected function get_request_headers(): array {
        return array(
            'User-Agent' => 'Amazon Affiliate Pro/' . AAPI_VERSION,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );
    }
}
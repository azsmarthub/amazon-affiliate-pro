<?php
/**
 * API Response Object
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/api
 */

namespace AAPI\API;

/**
 * API Response class.
 *
 * Standardizes API responses across different providers, providing
 * a consistent interface for accessing data and metadata.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api
 * @author     Your Name <email@example.com>
 */
class API_Response implements \ArrayAccess, \JsonSerializable {

    /**
     * Response data.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $data    Response data.
     */
    protected $data = array();

    /**
     * Response metadata.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $meta    Response metadata.
     */
    protected $meta = array();

    /**
     * Response type.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $type    Response type (product|search|error).
     */
    protected $type = 'unknown';

    /**
     * Success status.
     *
     * @since    1.0.0
     * @access   protected
     * @var      bool    $success    Whether request was successful.
     */
    protected $success = true;

    /**
     * Error information.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array|null    $error    Error details if any.
     */
    protected $error = null;

    /**
     * Original raw response.
     *
     * @since    1.0.0
     * @access   protected
     * @var      mixed    $raw_response    Original response from API.
     */
    protected $raw_response = null;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    array     $data           Response data.
     * @param    array     $meta           Response metadata.
     * @param    string    $type           Response type.
     */
    public function __construct(array $data = array(), array $meta = array(), string $type = 'unknown') {
        $this->data = $data;
        $this->meta = $this->normalize_metadata($meta);
        $this->type = $type;
        
        // Check for error response
        if (isset($data['error']) || isset($data['success']) && $data['success'] === false) {
            $this->success = false;
            $this->extract_error_info($data);
        }
    }

    /**
     * Create product response.
     *
     * @since    1.0.0
     * @param    array    $product_data    Product data.
     * @param    array    $meta            Metadata.
     * @return   self                      Response instance.
     */
    public static function product(array $product_data, array $meta = array()): self {
        return new self($product_data, $meta, 'product');
    }

    /**
     * Create search response.
     *
     * @since    1.0.0
     * @param    array    $results    Search results.
     * @param    array    $meta       Metadata including pagination.
     * @return   self                 Response instance.
     */
    public static function search(array $results, array $meta = array()): self {
        $data = array(
            'products' => $results['products'] ?? $results,
            'total_results' => $results['total_results'] ?? count($results),
            'current_page' => $results['current_page'] ?? 1,
            'total_pages' => $results['total_pages'] ?? 1,
        );
        
        return new self($data, $meta, 'search');
    }

    /**
     * Create error response.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     * @param    int       $code       Error code.
     * @param    mixed     $details    Additional error details.
     * @return   self                  Response instance.
     */
    public static function error(string $message, int $code = 0, $details = null): self {
        $response = new self(
            array(
                'error' => true,
                'message' => $message,
                'code' => $code,
                'details' => $details,
            ),
            array(),
            'error'
        );
        
        $response->success = false;
        
        return $response;
    }

    /**
     * Create from raw API response.
     *
     * @since    1.0.0
     * @param    mixed     $raw_response    Raw API response.
     * @param    string    $provider        Provider name.
     * @param    string    $type            Response type.
     * @return   self                       Response instance.
     */
    public static function from_raw($raw_response, string $provider, string $type = 'unknown'): self {
        $response = new self(array(), array('provider' => $provider), $type);
        $response->raw_response = $raw_response;
        
        // Parse based on provider
        $response->parse_provider_response($provider, $raw_response);
        
        return $response;
    }

    /**
     * Check if response is successful.
     *
     * @since    1.0.0
     * @return   bool    Success status.
     */
    public function is_success(): bool {
        return $this->success;
    }

    /**
     * Check if response is error.
     *
     * @since    1.0.0
     * @return   bool    Error status.
     */
    public function is_error(): bool {
        return !$this->success;
    }

    /**
     * Get response type.
     *
     * @since    1.0.0
     * @return   string    Response type.
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Get response data.
     *
     * @since    1.0.0
     * @return   array    Response data.
     */
    public function get_data(): array {
        return $this->data;
    }

    /**
     * Get specific data field.
     *
     * @since    1.0.0
     * @param    string    $key        Field key.
     * @param    mixed     $default    Default value.
     * @return   mixed                 Field value or default.
     */
    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set data field.
     *
     * @since    1.0.0
     * @param    string    $key      Field key.
     * @param    mixed     $value    Field value.
     * @return   self                Self for chaining.
     */
    public function set(string $key, $value): self {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Check if data field exists.
     *
     * @since    1.0.0
     * @param    string    $key    Field key.
     * @return   bool              Exists status.
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    /**
     * Get metadata.
     *
     * @since    1.0.0
     * @param    string|null    $key        Metadata key or null for all.
     * @param    mixed          $default    Default value.
     * @return   mixed                      Metadata value or all metadata.
     */
    public function get_meta(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->meta;
        }
        
        return $this->meta[$key] ?? $default;
    }

    /**
     * Set metadata.
     *
     * @since    1.0.0
     * @param    string    $key      Metadata key.
     * @param    mixed     $value    Metadata value.
     * @return   self                Self for chaining.
     */
    public function set_meta(string $key, $value): self {
        $this->meta[$key] = $value;
        return $this;
    }

    /**
     * Get error information.
     *
     * @since    1.0.0
     * @return   array|null    Error information or null.
     */
    public function get_error(): ?array {
        return $this->error;
    }

    /**
     * Get error message.
     *
     * @since    1.0.0
     * @return   string    Error message or empty string.
     */
    public function get_error_message(): string {
        return $this->error['message'] ?? '';
    }

    /**
     * Get error code.
     *
     * @since    1.0.0
     * @return   int    Error code or 0.
     */
    public function get_error_code(): int {
        return $this->error['code'] ?? 0;
    }

    /**
     * Get raw response.
     *
     * @since    1.0.0
     * @return   mixed    Raw response data.
     */
    public function get_raw_response() {
        return $this->raw_response;
    }

    /**
     * Convert to array.
     *
     * @since    1.0.0
     * @return   array    Response as array.
     */
    public function to_array(): array {
        return array(
            'success' => $this->success,
            'type' => $this->type,
            'data' => $this->data,
            'meta' => $this->meta,
            'error' => $this->error,
        );
    }

    /**
     * Convert to JSON.
     *
     * @since    1.0.0
     * @param    int    $options    JSON encode options.
     * @return   string             JSON string.
     */
    public function to_json(int $options = 0): string {
        return json_encode($this->to_array(), $options);
    }

    /**
     * Normalize metadata.
     *
     * @since    1.0.0
     * @param    array    $meta    Raw metadata.
     * @return   array             Normalized metadata.
     */
    protected function normalize_metadata(array $meta): array {
        $normalized = array_merge(
            array(
                'timestamp' => time(),
                'execution_time' => 0,
                'credits_used' => 0,
                'provider' => '',
                'cache_hit' => false,
                'api_version' => '',
            ),
            $meta
        );
        
        return $normalized;
    }

    /**
     * Extract error information from data.
     *
     * @since    1.0.0
     * @param    array    $data    Response data.
     */
    protected function extract_error_info(array $data) {
        $this->error = array(
            'message' => $data['message'] ?? $data['error'] ?? 'Unknown error',
            'code' => $data['code'] ?? $data['error_code'] ?? 0,
            'details' => $data['details'] ?? null,
            'type' => $data['error_type'] ?? 'api_error',
        );
    }

    /**
     * Parse provider-specific response.
     *
     * @since    1.0.0
     * @param    string    $provider        Provider name.
     * @param    mixed     $raw_response    Raw response.
     */
    protected function parse_provider_response(string $provider, $raw_response) {
        // This method would contain provider-specific parsing logic
        // For now, we'll implement a generic parser
        
        if (is_array($raw_response)) {
            $this->data = $raw_response;
            
            // Check for common error patterns
            if (isset($raw_response['error']) || isset($raw_response['errors'])) {
                $this->success = false;
                $this->extract_error_info($raw_response);
            }
        } elseif (is_object($raw_response)) {
            $this->data = json_decode(json_encode($raw_response), true);
        } elseif (is_string($raw_response)) {
            // Try to parse as JSON
            $decoded = json_decode($raw_response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->data = $decoded;
            } else {
                // Treat as error message
                $this->success = false;
                $this->error = array(
                    'message' => $raw_response,
                    'code' => 0,
                );
            }
        }
        
        // Provider-specific parsing would go here
        switch ($provider) {
            case 'paapi':
                $this->parse_paapi_response();
                break;
            case 'rainforest':
                $this->parse_rainforest_response();
                break;
            // Add more providers as needed
        }
    }

    /**
     * Parse PA-API response format.
     *
     * @since    1.0.0
     */
    protected function parse_paapi_response() {
        // PA-API specific parsing logic would go here
        // This is a placeholder for when we implement the actual provider
    }

    /**
     * Parse RainforestAPI response format.
     *
     * @since    1.0.0
     */
    protected function parse_rainforest_response() {
        // RainforestAPI specific parsing logic would go here
        // This is a placeholder for when we implement the actual provider
    }

    /**
     * Get products from search response.
     *
     * @since    1.0.0
     * @return   array    Products array.
     */
    public function get_products(): array {
        if ($this->type === 'search') {
            return $this->data['products'] ?? array();
        }
        
        if ($this->type === 'product') {
            return array($this->data);
        }
        
        return array();
    }

    /**
     * Get pagination info.
     *
     * @since    1.0.0
     * @return   array    Pagination information.
     */
    public function get_pagination(): array {
        return array(
            'current_page' => $this->data['current_page'] ?? 1,
            'total_pages' => $this->data['total_pages'] ?? 1,
            'total_results' => $this->data['total_results'] ?? 0,
            'per_page' => $this->data['per_page'] ?? 10,
            'has_next' => ($this->data['current_page'] ?? 1) < ($this->data['total_pages'] ?? 1),
            'has_previous' => ($this->data['current_page'] ?? 1) > 1,
        );
    }

    /**
     * Merge with another response.
     *
     * @since    1.0.0
     * @param    API_Response    $other    Other response to merge.
     * @return   self                      Self for chaining.
     */
    public function merge(API_Response $other): self {
        // Merge data
        if ($this->type === 'search' && $other->type === 'search') {
            // Merge search results
            $this->data['products'] = array_merge(
                $this->data['products'] ?? array(),
                $other->data['products'] ?? array()
            );
            
            // Update totals
            $this->data['total_results'] = count($this->data['products']);
        } else {
            // Generic merge
            $this->data = array_merge($this->data, $other->data);
        }
        
        // Merge metadata
        $this->meta['execution_time'] += $other->meta['execution_time'] ?? 0;
        $this->meta['credits_used'] += $other->meta['credits_used'] ?? 0;
        
        return $this;
    }

    /**
     * Filter products in response.
     *
     * @since    1.0.0
     * @param    callable    $callback    Filter callback.
     * @return   self                     Self for chaining.
     */
    public function filter_products(callable $callback): self {
        if ($this->type === 'search' && isset($this->data['products'])) {
            $this->data['products'] = array_filter($this->data['products'], $callback);
            $this->data['total_results'] = count($this->data['products']);
        }
        
        return $this;
    }

    /**
     * Map/transform products in response.
     *
     * @since    1.0.0
     * @param    callable    $callback    Map callback.
     * @return   self                     Self for chaining.
     */
    public function map_products(callable $callback): self {
        if ($this->type === 'search' && isset($this->data['products'])) {
            $this->data['products'] = array_map($callback, $this->data['products']);
        } elseif ($this->type === 'product') {
            $this->data = $callback($this->data);
        }
        
        return $this;
    }

    /**
     * Sort products in response.
     *
     * @since    1.0.0
     * @param    string    $field       Field to sort by.
     * @param    string    $direction   Sort direction (asc|desc).
     * @return   self                   Self for chaining.
     */
    public function sort_products(string $field, string $direction = 'asc'): self {
        if ($this->type === 'search' && isset($this->data['products'])) {
            usort($this->data['products'], function($a, $b) use ($field, $direction) {
                $val_a = $a[$field] ?? 0;
                $val_b = $b[$field] ?? 0;
                
                if ($direction === 'desc') {
                    return $val_b <=> $val_a;
                }
                
                return $val_a <=> $val_b;
            });
        }
        
        return $this;
    }

    /**
     * Paginate products.
     *
     * @since    1.0.0
     * @param    int    $page        Page number.
     * @param    int    $per_page    Items per page.
     * @return   self                Self for chaining.
     */
    public function paginate(int $page, int $per_page = 10): self {
        if ($this->type === 'search' && isset($this->data['products'])) {
            $total = count($this->data['products']);
            $offset = ($page - 1) * $per_page;
            
            $this->data['products'] = array_slice($this->data['products'], $offset, $per_page);
            $this->data['current_page'] = $page;
            $this->data['per_page'] = $per_page;
            $this->data['total_pages'] = ceil($total / $per_page);
            $this->data['total_results'] = $total;
        }
        
        return $this;
    }

    /**
     * Cache the response.
     *
     * @since    1.0.0
     * @param    string    $key         Cache key.
     * @param    int       $expiration  Expiration time in seconds.
     * @return   bool                   Success status.
     */
    public function cache(string $key, int $expiration = 3600): bool {
        return set_transient('aapi_response_' . $key, $this->to_array(), $expiration);
    }

    /**
     * Load from cache.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   self|null         Cached response or null.
     */
    public static function from_cache(string $key): ?self {
        $cached = get_transient('aapi_response_' . $key);
        
        if ($cached === false) {
            return null;
        }
        
        $response = new self(
            $cached['data'] ?? array(),
            $cached['meta'] ?? array(),
            $cached['type'] ?? 'unknown'
        );
        
        $response->success = $cached['success'] ?? true;
        $response->error = $cached['error'] ?? null;
        $response->meta['cache_hit'] = true;
        
        return $response;
    }

    // ArrayAccess implementation

    /**
     * Whether offset exists.
     *
     * @since    1.0.0
     * @param    mixed    $offset    Offset to check.
     * @return   bool                Exists status.
     */
    public function offsetExists($offset): bool {
        return isset($this->data[$offset]);
    }

    /**
     * Get offset value.
     *
     * @since    1.0.0
     * @param    mixed    $offset    Offset to get.
     * @return   mixed               Offset value.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset) {
        return $this->data[$offset] ?? null;
    }

    /**
     * Set offset value.
     *
     * @since    1.0.0
     * @param    mixed    $offset    Offset to set.
     * @param    mixed    $value     Value to set.
     */
    public function offsetSet($offset, $value): void {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Unset offset.
     *
     * @since    1.0.0
     * @param    mixed    $offset    Offset to unset.
     */
    public function offsetUnset($offset): void {
        unset($this->data[$offset]);
    }

    // JsonSerializable implementation

    /**
     * Specify data for JSON serialization.
     *
     * @since    1.0.0
     * @return   array    Data to serialize.
     */
    public function jsonSerialize(): array {
        return $this->to_array();
    }
}
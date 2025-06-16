<?php
/**
 * API Cache System
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
 * API Cache class.
 *
 * Provides a sophisticated caching layer for API responses with support for
 * TTL management, cache warming, invalidation strategies, and statistics.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api
 * @author     Your Name <email@example.com>
 */
class API_Cache {

    /**
     * Cache prefix.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $prefix    Cache key prefix.
     */
    private $prefix = 'aapi_cache_';

    /**
     * Default TTL in seconds.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $default_ttl    Default time-to-live.
     */
    private $default_ttl = 3600; // 1 hour

    /**
     * TTL configuration per cache type.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $ttl_config    TTL settings by type.
     */
    private $ttl_config = array();

    /**
     * Cache statistics.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $stats    Cache statistics.
     */
    private $stats = array(
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
    );

    /**
     * Settings instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Settings    $settings    Settings instance.
     */
    private $settings;

    /**
     * Cache enabled status.
     *
     * @since    1.0.0
     * @access   private
     * @var      bool    $enabled    Whether caching is enabled.
     */
    private $enabled = true;

    /**
     * Cache storage backend.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $backend    Storage backend (transient|object|file).
     */
    private $backend = 'transient';

    /**
     * Object cache instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      object|null    $object_cache    Object cache if available.
     */
    private $object_cache = null;

    /**
     * Cache tags registry.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $tags    Cache tags for group invalidation.
     */
    private $tags = array();

    /**
     * Memory cache for current request.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $memory_cache    In-memory cache.
     */
    private $memory_cache = array();

    /**
     * Cache instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      API_Cache    $instance    Singleton instance.
     */
    private static $instance = null;

    /**
     * Get instance.
     *
     * @since    1.0.0
     * @return   API_Cache    Cache instance.
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->settings = new Settings();
        $this->init();
    }

    /**
     * Initialize cache system.
     *
     * @since    1.0.0
     */
    private function init() {
        // Load configuration
        $this->enabled = $this->settings->get('general', 'enable_api_cache', true);
        $this->default_ttl = $this->settings->get('general', 'cache_duration', 3600);
        
        // Configure TTLs per type
        $this->ttl_config = array(
            'product' => $this->settings->get('cache', 'product_ttl', 3600),         // 1 hour
            'search' => $this->settings->get('cache', 'search_ttl', 1800),          // 30 minutes
            'variations' => $this->settings->get('cache', 'variations_ttl', 7200),  // 2 hours
            'categories' => $this->settings->get('cache', 'categories_ttl', 86400), // 24 hours
            'bestsellers' => $this->settings->get('cache', 'bestsellers_ttl', 3600),// 1 hour
            'offers' => $this->settings->get('cache', 'offers_ttl', 900),           // 15 minutes
            'reviews' => $this->settings->get('cache', 'reviews_ttl', 21600),       // 6 hours
        );
        
        // Detect best cache backend
        $this->detect_cache_backend();
        
        // Load cache statistics
        $this->load_statistics();
        
        // Register shutdown function to save stats
        register_shutdown_function(array($this, 'save_statistics'));
    }

    /**
     * Get cached data.
     *
     * @since    1.0.0
     * @param    string    $key     Cache key.
     * @param    mixed     $default Default value if not found.
     * @return   mixed               Cached data or default.
     */
    public function get(string $key, $default = null) {
        if (!$this->enabled) {
            $this->stats['misses']++;
            return $default;
        }
        
        // Check memory cache first
        if (isset($this->memory_cache[$key])) {
            $this->stats['hits']++;
            return $this->memory_cache[$key]['data'];
        }
        
        // Get from storage
        $cache_key = $this->prefix . $key;
        $cached = $this->get_from_backend($cache_key);
        
        if ($cached !== false) {
            // Validate cache entry
            if ($this->is_valid_cache($cached)) {
                $this->stats['hits']++;
                
                // Store in memory cache
                $this->memory_cache[$key] = $cached;
                
                return $cached['data'];
            } else {
                // Invalid cache, delete it
                $this->delete($key);
            }
        }
        
        $this->stats['misses']++;
        return $default;
    }

    /**
     * Set cache data.
     *
     * @since    1.0.0
     * @param    string    $key       Cache key.
     * @param    mixed     $data      Data to cache.
     * @param    int|null  $ttl       Time-to-live in seconds.
     * @param    array     $tags      Cache tags for group invalidation.
     * @param    array     $metadata  Additional metadata.
     * @return   bool                 Success status.
     */
    public function set(string $key, $data, ?int $ttl = null, array $tags = array(), array $metadata = array()): bool {
        if (!$this->enabled) {
            return false;
        }
        
        // Determine TTL
        if ($ttl === null) {
            $ttl = $this->get_ttl_for_key($key);
        }
        
        // Prepare cache entry
        $entry = array(
            'data' => $data,
            'created' => time(),
            'expires' => time() + $ttl,
            'ttl' => $ttl,
            'tags' => $tags,
            'metadata' => array_merge(
                array(
                    'key' => $key,
                    'size' => $this->calculate_size($data),
                    'type' => $this->detect_cache_type($key),
                ),
                $metadata
            ),
        );
        
        // Store in memory cache
        $this->memory_cache[$key] = $entry;
        
        // Store in backend
        $cache_key = $this->prefix . $key;
        $result = $this->set_in_backend($cache_key, $entry, $ttl);
        
        if ($result) {
            $this->stats['writes']++;
            
            // Update tags registry
            $this->update_tags($key, $tags);
        }
        
        return $result;
    }

    /**
     * Delete cache entry.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   bool              Success status.
     */
    public function delete(string $key): bool {
        // Remove from memory cache
        unset($this->memory_cache[$key]);
        
        // Remove from backend
        $cache_key = $this->prefix . $key;
        $result = $this->delete_from_backend($cache_key);
        
        if ($result) {
            $this->stats['deletes']++;
            
            // Remove from tags registry
            $this->remove_from_tags($key);
        }
        
        return $result;
    }

    /**
     * Delete cache entries by tag.
     *
     * @since    1.0.0
     * @param    string    $tag    Cache tag.
     * @return   int               Number of entries deleted.
     */
    public function delete_by_tag(string $tag): int {
        $deleted = 0;
        
        // Get all keys with this tag
        $keys = $this->get_keys_by_tag($tag);
        
        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Clear all cache.
     *
     * @since    1.0.0
     * @return   bool    Success status.
     */
    public function clear_all(): bool {
        // Clear memory cache
        $this->memory_cache = array();
        
        // Clear backend
        $result = $this->clear_backend();
        
        // Reset tags
        $this->tags = array();
        $this->save_tags();
        
        // Reset statistics
        $this->reset_statistics();
        
        return $result;
    }

    /**
     * Generate cache key.
     *
     * @since    1.0.0
     * @param    string    $type      Cache type (product, search, etc.).
     * @param    array     $params    Parameters for key generation.
     * @param    string    $provider  API provider.
     * @return   string               Generated cache key.
     */
    public function generate_key(string $type, array $params, string $provider = ''): string {
        // Sort params for consistent keys
        ksort($params);
        
        // Remove empty values
        $params = array_filter($params, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Build key components
        $components = array($type);
        
        if ($provider) {
            $components[] = $provider;
        }
        
        // Add marketplace if present
        if (isset($params['marketplace'])) {
            $components[] = $params['marketplace'];
            unset($params['marketplace']);
        }
        
        // Special handling for different types
        switch ($type) {
            case 'product':
                if (isset($params['asin'])) {
                    $components[] = $params['asin'];
                    unset($params['asin']);
                }
                break;
                
            case 'search':
                if (isset($params['keyword'])) {
                    $components[] = sanitize_title($params['keyword']);
                    unset($params['keyword']);
                }
                break;
        }
        
        // Add remaining params as hash
        if (!empty($params)) {
            $components[] = md5(serialize($params));
        }
        
        return implode('_', $components);
    }

    /**
     * Warm cache with preloaded data.
     *
     * @since    1.0.0
     * @param    array    $entries    Array of cache entries to preload.
     * @return   int                  Number of entries warmed.
     */
    public function warm(array $entries): int {
        $warmed = 0;
        
        foreach ($entries as $entry) {
            if (!isset($entry['key']) || !isset($entry['data'])) {
                continue;
            }
            
            $ttl = $entry['ttl'] ?? null;
            $tags = $entry['tags'] ?? array();
            $metadata = $entry['metadata'] ?? array();
            
            if ($this->set($entry['key'], $entry['data'], $ttl, $tags, $metadata)) {
                $warmed++;
            }
        }
        
        return $warmed;
    }

    /**
     * Get cache statistics.
     *
     * @since    1.0.0
     * @return   array    Cache statistics.
     */
    public function get_statistics(): array {
        $total_requests = $this->stats['hits'] + $this->stats['misses'];
        
        return array(
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'writes' => $this->stats['writes'],
            'deletes' => $this->stats['deletes'],
            'hit_rate' => $total_requests > 0 ? round(($this->stats['hits'] / $total_requests) * 100, 2) : 0,
            'total_requests' => $total_requests,
            'backend' => $this->backend,
            'enabled' => $this->enabled,
        );
    }

    /**
     * Check if cache key exists.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   bool              Exists status.
     */
    public function exists(string $key): bool {
        if (!$this->enabled) {
            return false;
        }
        
        // Check memory cache
        if (isset($this->memory_cache[$key])) {
            return $this->is_valid_cache($this->memory_cache[$key]);
        }
        
        // Check backend
        $cache_key = $this->prefix . $key;
        $cached = $this->get_from_backend($cache_key);
        
        return $cached !== false && $this->is_valid_cache($cached);
    }

    /**
     * Get remaining TTL for cache key.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   int|null          Remaining seconds or null if not found.
     */
    public function get_ttl(string $key): ?int {
        $cached = $this->get_raw($key);
        
        if ($cached && isset($cached['expires'])) {
            $remaining = $cached['expires'] - time();
            return max(0, $remaining);
        }
        
        return null;
    }

    /**
     * Touch cache entry to extend TTL.
     *
     * @since    1.0.0
     * @param    string    $key        Cache key.
     * @param    int|null  $extra_ttl  Additional TTL to add.
     * @return   bool                  Success status.
     */
    public function touch(string $key, ?int $extra_ttl = null): bool {
        $cached = $this->get_raw($key);
        
        if (!$cached) {
            return false;
        }
        
        // Calculate new expiry
        if ($extra_ttl !== null) {
            $cached['expires'] = time() + $extra_ttl;
        } else {
            $cached['expires'] = time() + $cached['ttl'];
        }
        
        // Update cache
        return $this->set(
            $key,
            $cached['data'],
            $cached['expires'] - time(),
            $cached['tags'] ?? array(),
            $cached['metadata'] ?? array()
        );
    }

    /**
     * Get raw cache entry.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   array|null        Raw cache entry or null.
     */
    private function get_raw(string $key): ?array {
        // Check memory cache
        if (isset($this->memory_cache[$key])) {
            return $this->memory_cache[$key];
        }
        
        // Get from backend
        $cache_key = $this->prefix . $key;
        $cached = $this->get_from_backend($cache_key);
        
        return $cached !== false ? $cached : null;
    }

    /**
     * Check if cache entry is valid.
     *
     * @since    1.0.0
     * @param    array    $entry    Cache entry.
     * @return   bool               Valid status.
     */
    private function is_valid_cache(array $entry): bool {
        // Check structure
        if (!isset($entry['data']) || !isset($entry['expires'])) {
            return false;
        }
        
        // Check expiry
        if ($entry['expires'] < time()) {
            return false;
        }
        
        return true;
    }

    /**
     * Get TTL for cache key based on type.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   int               TTL in seconds.
     */
    private function get_ttl_for_key(string $key): int {
        // Try to detect type from key
        foreach ($this->ttl_config as $type => $ttl) {
            if (strpos($key, $type) === 0) {
                return $ttl;
            }
        }
        
        return $this->default_ttl;
    }

    /**
     * Detect cache type from key.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   string            Cache type.
     */
    private function detect_cache_type(string $key): string {
        $types = array('product', 'search', 'variations', 'categories', 'bestsellers', 'offers', 'reviews');
        
        foreach ($types as $type) {
            if (strpos($key, $type) === 0) {
                return $type;
            }
        }
        
        return 'unknown';
    }

    /**
     * Calculate data size.
     *
     * @since    1.0.0
     * @param    mixed    $data    Data to measure.
     * @return   int               Size in bytes.
     */
    private function calculate_size($data): int {
        return strlen(serialize($data));
    }

    /**
     * Detect best cache backend.
     *
     * @since    1.0.0
     */
    private function detect_cache_backend() {
        // Check for object cache
        if (wp_using_ext_object_cache()) {
            $this->backend = 'object';
            $this->object_cache = $GLOBALS['wp_object_cache'] ?? null;
        } else {
            // Use transients (database)
            $this->backend = 'transient';
        }
        
        // Allow override
        $this->backend = apply_filters('aapi_cache_backend', $this->backend);
    }

    /**
     * Get from cache backend.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   mixed              Cached data or false.
     */
    private function get_from_backend(string $key) {
        switch ($this->backend) {
            case 'object':
                return wp_cache_get($key, 'aapi');
                
            case 'transient':
            default:
                return get_transient($key);
        }
    }

    /**
     * Set in cache backend.
     *
     * @since    1.0.0
     * @param    string    $key     Cache key.
     * @param    mixed     $data    Data to cache.
     * @param    int       $ttl     TTL in seconds.
     * @return   bool               Success status.
     */
    private function set_in_backend(string $key, $data, int $ttl): bool {
        switch ($this->backend) {
            case 'object':
                return wp_cache_set($key, $data, 'aapi', $ttl);
                
            case 'transient':
            default:
                return set_transient($key, $data, $ttl);
        }
    }

    /**
     * Delete from cache backend.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     * @return   bool              Success status.
     */
    private function delete_from_backend(string $key): bool {
        switch ($this->backend) {
            case 'object':
                return wp_cache_delete($key, 'aapi');
                
            case 'transient':
            default:
                return delete_transient($key);
        }
    }

    /**
     * Clear cache backend.
     *
     * @since    1.0.0
     * @return   bool    Success status.
     */
    private function clear_backend(): bool {
        global $wpdb;
        
        switch ($this->backend) {
            case 'object':
                return wp_cache_flush();
                
            case 'transient':
            default:
                // Delete all transients with our prefix
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} 
                         WHERE option_name LIKE %s 
                         OR option_name LIKE %s",
                        '_transient_' . $this->prefix . '%',
                        '_transient_timeout_' . $this->prefix . '%'
                    )
                );
                return true;
        }
    }

    /**
     * Update tags registry.
     *
     * @since    1.0.0
     * @param    string    $key     Cache key.
     * @param    array     $tags    Tags to associate.
     */
    private function update_tags(string $key, array $tags) {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = array();
            }
            
            if (!in_array($key, $this->tags[$tag])) {
                $this->tags[$tag][] = $key;
            }
        }
        
        $this->save_tags();
    }

    /**
     * Remove key from tags registry.
     *
     * @since    1.0.0
     * @param    string    $key    Cache key.
     */
    private function remove_from_tags(string $key) {
        foreach ($this->tags as $tag => &$keys) {
            $index = array_search($key, $keys);
            if ($index !== false) {
                unset($keys[$index]);
                $keys = array_values($keys); // Re-index
            }
            
            // Remove empty tags
            if (empty($keys)) {
                unset($this->tags[$tag]);
            }
        }
        
        $this->save_tags();
    }

    /**
     * Get keys by tag.
     *
     * @since    1.0.0
     * @param    string    $tag    Cache tag.
     * @return   array             Array of cache keys.
     */
    private function get_keys_by_tag(string $tag): array {
        return $this->tags[$tag] ?? array();
    }

    /**
     * Save tags registry.
     *
     * @since    1.0.0
     */
    private function save_tags() {
        update_option('aapi_cache_tags', $this->tags);
    }

    /**
     * Load tags registry.
     *
     * @since    1.0.0
     */
    private function load_tags() {
        $this->tags = get_option('aapi_cache_tags', array());
    }

    /**
     * Load statistics.
     *
     * @since    1.0.0
     */
    private function load_statistics() {
        $stats = get_option('aapi_cache_statistics', array());
        
        if (!empty($stats)) {
            $this->stats = array_merge($this->stats, $stats);
        }
    }

    /**
     * Save statistics.
     *
     * @since    1.0.0
     */
    public function save_statistics() {
        update_option('aapi_cache_statistics', $this->stats);
    }

    /**
     * Reset statistics.
     *
     * @since    1.0.0
     */
    public function reset_statistics() {
        $this->stats = array(
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
        );
        
        $this->save_statistics();
    }

    /**
     * Get cache size information.
     *
     * @since    1.0.0
     * @return   array    Size information.
     */
    public function get_size_info(): array {
        global $wpdb;
        
        $info = array(
            'total_entries' => 0,
            'total_size' => 0,
            'by_type' => array(),
        );
        
        if ($this->backend === 'transient') {
            // Count transients
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_' . $this->prefix . '%'
                )
            );
            
            $info['total_entries'] = intval($count);
            
            // Estimate size (this is approximate)
            $size = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_' . $this->prefix . '%'
                )
            );
            
            $info['total_size'] = intval($size);
        }
        
        return $info;
    }
}
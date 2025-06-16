<?php
/**
 * API Provider Interface
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/api
 */

namespace AAPI\API;

/**
 * Interface for all API providers.
 *
 * This interface defines the contract that all API providers must implement
 * to ensure consistency across different Amazon data sources.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api
 * @author     Your Name <email@example.com>
 */
interface API_Provider {

    /**
     * Search for products.
     *
     * @since    1.0.0
     * @param    string    $keyword      Search keyword.
     * @param    array     $options      Optional search parameters.
     *                                   [
     *                                     'marketplace'   => (string) Marketplace code (default: 'US')
     *                                     'category'      => (string) Category to search in
     *                                     'min_price'     => (float) Minimum price
     *                                     'max_price'     => (float) Maximum price
     *                                     'brand'         => (string) Brand name
     *                                     'sort'          => (string) Sort order (relevance|price_asc|price_desc|rating|newest)
     *                                     'page'          => (int) Page number (default: 1)
     *                                     'per_page'      => (int) Results per page (default: 10, max: 50)
     *                                     'prime_only'    => (bool) Prime eligible only
     *                                     'min_rating'    => (float) Minimum rating (1-5)
     *                                     'condition'     => (string) new|used|refurbished|all
     *                                   ]
     * @return   array                   Search results
     *                                   [
     *                                     'success'       => (bool) Operation status
     *                                     'products'      => (array) Array of product data
     *                                     'total_results' => (int) Total number of results
     *                                     'total_pages'   => (int) Total pages available
     *                                     'current_page'  => (int) Current page number
     *                                     'credits_used'  => (int) API credits consumed
     *                                     'error'         => (string) Error message if any
     *                                   ]
     * @throws   API_Exception           If the API request fails
     */
    public function search_products(string $keyword, array $options = []): array;

    /**
     * Get a single product by ASIN.
     *
     * @since    1.0.0
     * @param    string    $asin         Amazon Standard Identification Number.
     * @param    array     $options      Optional parameters.
     *                                   [
     *                                     'marketplace'      => (string) Marketplace code (default: 'US')
     *                                     'include_reviews'  => (bool) Include review summary
     *                                     'include_offers'   => (bool) Include all offers
     *                                     'include_variations' => (bool) Include product variations
     *                                   ]
     * @return   array|null               Product data or null if not found
     *                                   [
     *                                     'asin'           => (string) Product ASIN
     *                                     'title'          => (string) Product title
     *                                     'description'    => (string) Product description
     *                                     'price'          => (float) Current price
     *                                     'currency'       => (string) Currency code
     *                                     'list_price'     => (float) Original price
     *                                     'savings_amount' => (float) Discount amount
     *                                     'savings_percent'=> (int) Discount percentage
     *                                     'availability'   => (string) Stock status
     *                                     'url'            => (string) Product URL
     *                                     'image_url'      => (string) Main image URL
     *                                     'images'         => (array) All product images
     *                                     'features'       => (array) Product features/bullets
     *                                     'categories'     => (array) Product categories
     *                                     'brand'          => (string) Brand name
     *                                     'model'          => (string) Model number
     *                                     'color'          => (string) Product color
     *                                     'size'           => (string) Product size
     *                                     'weight'         => (string) Product weight
     *                                     'dimensions'     => (array) Product dimensions
     *                                     'rating'         => (float) Average rating
     *                                     'reviews_count'  => (int) Number of reviews
     *                                     'is_prime'       => (bool) Prime eligible
     *                                     'is_bestseller'  => (bool) Bestseller flag
     *                                     'rank'           => (array) Sales rank by category
     *                                     'variations'     => (array) Product variations if requested
     *                                     'offers'         => (array) All offers if requested
     *                                     'updated_at'     => (string) Last update timestamp
     *                                   ]
     * @throws   API_Exception           If the API request fails
     */
    public function get_product(string $asin, array $options = []): ?array;

    /**
     * Get multiple products by ASINs.
     *
     * @since    1.0.0
     * @param    array     $asins        Array of ASINs (max 50).
     * @param    array     $options      Optional parameters (same as get_product).
     * @return   array                   Array of products indexed by ASIN
     *                                   [
     *                                     'ASIN1' => [...product data...],
     *                                     'ASIN2' => [...product data...],
     *                                     'failed' => ['ASIN3', 'ASIN4'] // ASINs that failed
     *                                   ]
     * @throws   API_Exception           If the API request fails
     */
    public function get_multiple_products(array $asins, array $options = []): array;

    /**
     * Get product variations.
     *
     * @since    1.0.0
     * @param    string    $asin         Parent ASIN.
     * @param    array     $options      Optional parameters.
     *                                   [
     *                                     'marketplace' => (string) Marketplace code
     *                                     'dimensions'  => (array) Variation dimensions to retrieve
     *                                   ]
     * @return   array                   Variations data
     *                                   [
     *                                     'parent_asin'    => (string) Parent product ASIN
     *                                     'dimensions'     => (array) Available dimensions (color, size, etc.)
     *                                     'variations'     => (array) Array of variations
     *                                     'total_variations' => (int) Total number of variations
     *                                   ]
     * @throws   API_Exception           If the API request fails
     */
    public function get_variations(string $asin, array $options = []): array;

    /**
     * Get product offers.
     *
     * @since    1.0.0
     * @param    string    $asin         Product ASIN.
     * @param    array     $options      Optional parameters.
     *                                   [
     *                                     'marketplace' => (string) Marketplace code
     *                                     'condition'   => (string) new|used|all
     *                                   ]
     * @return   array                   Offers data
     *                                   [
     *                                     'summary' => [
     *                                       'lowest_price' => (float) Lowest price
     *                                       'total_offers' => (int) Total offers
     *                                     ],
     *                                     'offers' => (array) Array of offers
     *                                   ]
     * @throws   API_Exception           If the API request fails
     */
    public function get_offers(string $asin, array $options = []): array;

    /**
     * Get product reviews summary.
     *
     * @since    1.0.0
     * @param    string    $asin         Product ASIN.
     * @param    array     $options      Optional parameters.
     * @return   array                   Reviews summary
     *                                   [
     *                                     'rating'         => (float) Average rating
     *                                     'total_reviews'  => (int) Total reviews
     *                                     'stars_breakdown'=> (array) Reviews by star rating
     *                                     'top_positive'   => (string) Top positive review excerpt
     *                                     'top_critical'   => (string) Top critical review excerpt
     *                                   ]
     * @throws   API_Exception           If the API request fails
     */
    public function get_reviews_summary(string $asin, array $options = []): array;

    /**
     * Get bestsellers in a category.
     *
     * @since    1.0.0
     * @param    string    $category     Category ID or browse node.
     * @param    array     $options      Optional parameters.
     *                                   [
     *                                     'marketplace' => (string) Marketplace code
     *                                     'limit'       => (int) Number of results (default: 10, max: 100)
     *                                   ]
     * @return   array                   Bestseller products
     * @throws   API_Exception           If the API request fails
     */
    public function get_bestsellers(string $category = '', array $options = []): array;

    /**
     * Get new releases in a category.
     *
     * @since    1.0.0
     * @param    string    $category     Category ID or browse node.
     * @param    array     $options      Optional parameters.
     * @return   array                   New release products
     * @throws   API_Exception           If the API request fails
     */
    public function get_new_releases(string $category = '', array $options = []): array;

    /**
     * Get available categories/browse nodes.
     *
     * @since    1.0.0
     * @param    array     $options      Optional parameters.
     *                                   [
     *                                     'marketplace' => (string) Marketplace code
     *                                     'parent_id'   => (string) Parent category ID
     *                                   ]
     * @return   array                   Categories list
     * @throws   API_Exception           If the API request fails
     */
    public function get_categories(array $options = []): array;

    /**
     * Test API connection.
     *
     * @since    1.0.0
     * @return   array                   Connection test results
     *                                   [
     *                                     'success'        => (bool) Connection status
     *                                     'message'        => (string) Status message
     *                                     'latency'        => (float) Response time in seconds
     *                                     'credits_remaining' => (int) API credits remaining
     *                                     'rate_limit'     => (array) Rate limit information
     *                                   ]
     */
    public function test_connection(): array;

    /**
     * Get API quota information.
     *
     * @since    1.0.0
     * @return   array                   Quota information
     *                                   [
     *                                     'credits_used'      => (int) Credits used
     *                                     'credits_remaining' => (int) Credits remaining
     *                                     'credits_limit'     => (int) Total credits limit
     *                                     'reset_time'        => (string) When quota resets
     *                                     'rate_limits'       => (array) Rate limit details
     *                                   ]
     */
    public function get_quota_info(): array;

    /**
     * Get supported marketplaces.
     *
     * @since    1.0.0
     * @return   array                   Marketplaces array
     *                                   [
     *                                     'US' => 'United States',
     *                                     'UK' => 'United Kingdom',
     *                                     ...
     *                                   ]
     */
    public function get_supported_marketplaces(): array;

    /**
     * Get API provider information.
     *
     * @since    1.0.0
     * @return   array                   Provider information
     *                                   [
     *                                     'name'          => (string) Provider name
     *                                     'version'       => (string) API version
     *                                     'capabilities'  => (array) Supported features
     *                                     'limitations'   => (array) Known limitations
     *                                   ]
     */
    public function get_provider_info(): array;

    /**
     * Set API credentials.
     *
     * @since    1.0.0
     * @param    array     $credentials  API credentials
     *                                   [
     *                                     'api_key'    => (string) API key
     *                                     'api_secret' => (string) API secret (if required)
     *                                     'partner_tag'=> (string) Affiliate tag (if required)
     *                                     ...provider specific credentials...
     *                                   ]
     * @return   void
     * @throws   Auth_Exception          If credentials are invalid
     */
    public function set_credentials(array $credentials): void;

    /**
     * Get last error information.
     *
     * @since    1.0.0
     * @return   array|null              Error information or null
     *                                   [
     *                                     'code'      => (int) Error code
     *                                     'message'   => (string) Error message
     *                                     'details'   => (mixed) Additional error details
     *                                     'timestamp' => (string) When error occurred
     *                                   ]
     */
    public function get_last_error(): ?array;

    /**
     * Clear any cached data for this provider.
     *
     * @since    1.0.0
     * @param    string|null $cache_key  Specific cache key or null for all
     * @return   bool                    Success status
     */
    public function clear_cache(?string $cache_key = null): bool;

    /**
     * Get provider-specific settings.
     *
     * @since    1.0.0
     * @return   array                   Provider settings
     */
    public function get_settings(): array;

    /**
     * Update provider-specific settings.
     *
     * @since    1.0.0
     * @param    array     $settings     New settings
     * @return   bool                    Success status
     */
    public function update_settings(array $settings): bool;
}
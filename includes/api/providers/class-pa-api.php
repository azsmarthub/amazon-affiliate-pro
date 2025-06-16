<?php
/**
 * Amazon PA-API 5.0 Provider
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/api/providers
 */

namespace AAPI\API\Providers;

use AAPI\API\API_Base;
use AAPI\API\API_Response;
use AAPI\API\API_Exception;
use AAPI\API\Quota_Exception;
use AAPI\API\Auth_Exception;

/**
 * PA-API Provider class.
 *
 * Implements Amazon Product Advertising API 5.0 with full authentication,
 * rate limiting, and comprehensive product data retrieval.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api/providers
 * @author     Your Name <email@example.com>
 */
class PA_API extends API_Base {

    /**
     * API version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_version    PA-API version.
     */
    protected $api_version = '5.0';

    /**
     * Provider name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $provider_name    Provider identifier.
     */
    protected $provider_name = 'paapi';

    /**
     * API host by marketplace.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $api_hosts    API endpoints by marketplace.
     */
    private $api_hosts = array(
        'US' => 'webservices.amazon.com',
        'UK' => 'webservices.amazon.co.uk',
        'DE' => 'webservices.amazon.de',
        'FR' => 'webservices.amazon.fr',
        'JP' => 'webservices.amazon.co.jp',
        'CA' => 'webservices.amazon.ca',
        'IT' => 'webservices.amazon.it',
        'ES' => 'webservices.amazon.es',
        'IN' => 'webservices.amazon.in',
        'CN' => 'webservices.amazon.cn',
        'MX' => 'webservices.amazon.com.mx',
        'BR' => 'webservices.amazon.com.br',
        'AU' => 'webservices.amazon.com.au',
        'NL' => 'webservices.amazon.nl',
        'AE' => 'webservices.amazon.ae',
        'SG' => 'webservices.amazon.sg',
        'TR' => 'webservices.amazon.com.tr',
        'SA' => 'webservices.amazon.sa',
        'SE' => 'webservices.amazon.se',
        'PL' => 'webservices.amazon.pl',
    );

    /**
     * Region mapping by marketplace.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $regions    AWS regions by marketplace.
     */
    private $regions = array(
        'US' => 'us-east-1',
        'UK' => 'eu-west-1',
        'DE' => 'eu-west-1',
        'FR' => 'eu-west-1',
        'JP' => 'us-west-2',
        'CA' => 'us-east-1',
        'IT' => 'eu-west-1',
        'ES' => 'eu-west-1',
        'IN' => 'eu-west-1',
        'CN' => 'us-west-2',
        'MX' => 'us-east-1',
        'BR' => 'us-east-1',
        'AU' => 'us-west-2',
        'NL' => 'eu-west-1',
        'AE' => 'eu-west-1',
        'SG' => 'us-west-2',
        'TR' => 'eu-west-1',
        'SA' => 'eu-west-1',
        'SE' => 'eu-west-1',
        'PL' => 'eu-west-1',
    );

    /**
     * Current marketplace.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $marketplace    Current marketplace code.
     */
    private $marketplace = 'US';

    /**
     * Partner tag (Associate ID).
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $partner_tag    Amazon Associate tag.
     */
    private $partner_tag = '';

    /**
     * Authentication handler.
     *
     * @since    1.0.0
     * @access   private
     * @var      PA_API_Auth    $auth    Authentication handler.
     */
    private $auth;

    /**
     * Request builder.
     *
     * @since    1.0.0
     * @access   private
     * @var      PA_API_Request    $request_builder    Request builder.
     */
    private $request_builder;

    /**
     * Response parser.
     *
     * @since    1.0.0
     * @access   private
     * @var      PA_API_Response    $response_parser    Response parser.
     */
    private $response_parser;

    /**
     * Rate limiter.
     *
     * @since    1.0.0
     * @access   private
     * @var      PA_API_Rate_Limiter    $rate_limiter    Rate limiter.
     */
    private $rate_limiter;

    /**
     * Available resources for GetItems.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $available_resources    Available PA-API resources.
     */
    private $available_resources = array(
        'BrowseNodeInfo.BrowseNodes',
        'BrowseNodeInfo.BrowseNodes.Ancestor',
        'BrowseNodeInfo.BrowseNodes.SalesRank',
        'BrowseNodeInfo.WebsiteSalesRank',
        'CustomerReviews.Count',
        'CustomerReviews.StarRating',
        'Images.Primary.Small',
        'Images.Primary.Medium',
        'Images.Primary.Large',
        'Images.Variants.Small',
        'Images.Variants.Medium',
        'Images.Variants.Large',
        'ItemInfo.ByLineInfo',
        'ItemInfo.ContentInfo',
        'ItemInfo.ContentRating',
        'ItemInfo.Classifications',
        'ItemInfo.ExternalIds',
        'ItemInfo.Features',
        'ItemInfo.ManufactureInfo',
        'ItemInfo.ProductInfo',
        'ItemInfo.TechnicalInfo',
        'ItemInfo.Title',
        'ItemInfo.TradeInInfo',
        'Offers.Listings.Availability.MaxOrderQuantity',
        'Offers.Listings.Availability.Message',
        'Offers.Listings.Availability.MinOrderQuantity',
        'Offers.Listings.Availability.Type',
        'Offers.Listings.Condition',
        'Offers.Listings.Condition.ConditionNote',
        'Offers.Listings.Condition.SubCondition',
        'Offers.Listings.DeliveryInfo.IsAmazonFulfilled',
        'Offers.Listings.DeliveryInfo.IsFreeShippingEligible',
        'Offers.Listings.DeliveryInfo.IsPrimeEligible',
        'Offers.Listings.DeliveryInfo.ShippingCharges',
        'Offers.Listings.IsBuyBoxWinner',
        'Offers.Listings.LoyaltyPoints.Points',
        'Offers.Listings.MerchantInfo',
        'Offers.Listings.Price',
        'Offers.Listings.ProgramEligibility.IsPrimeExclusive',
        'Offers.Listings.ProgramEligibility.IsPrimePantry',
        'Offers.Listings.Promotions',
        'Offers.Listings.SavingBasis',
        'Offers.Summaries.HighestPrice',
        'Offers.Summaries.LowestPrice',
        'Offers.Summaries.OfferCount',
        'ParentASIN',
        'RentalOffers.Listings.Availability.AvailabilityAttributes',
        'RentalOffers.Listings.BasePrice',
        'RentalOffers.Listings.Condition',
        'RentalOffers.Listings.DeliveryInfo',
        'RentalOffers.Listings.MerchantInfo',
        'VariationSummary.Price.HighestPrice',
        'VariationSummary.Price.LowestPrice',
        'VariationSummary.VariationDimension',
    );

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    array    $credentials    API credentials.
     */
    public function __construct(array $credentials = array()) {
        parent::__construct($credentials);
        $this->init_components();
    }

    /**
     * Initialize PA-API components.
     *
     * @since    1.0.0
     */
    private function init_components() {
        // Validate credentials
        if (!$this->validate_credentials($this->credentials)) {
            return;
        }

        // Set marketplace and partner tag
        $this->marketplace = $this->credentials['marketplace'] ?? 'US';
        $this->partner_tag = $this->credentials['partner_tag'] ?? '';

        // Initialize components
        $this->init_auth();
        $this->init_request_builder();
        $this->init_response_parser();
        $this->init_rate_limiter();
    }

    /**
     * Initialize authentication handler.
     *
     * @since    1.0.0
     */
    private function init_auth() {
        require_once __DIR__ . '/class-pa-api-auth.php';
        
        $this->auth = new PA_API_Auth(
            $this->credentials['api_key'],
            $this->credentials['api_secret'],
            $this->get_region($this->marketplace)
        );
    }

    /**
     * Initialize request builder.
     *
     * @since    1.0.0
     */
    private function init_request_builder() {
        require_once __DIR__ . '/class-pa-api-request.php';
        
        $this->request_builder = new PA_API_Request(
            $this->marketplace,
            $this->partner_tag
        );
    }

    /**
     * Initialize response parser.
     *
     * @since    1.0.0
     */
    private function init_response_parser() {
        require_once __DIR__ . '/class-pa-api-response.php';
        
        $this->response_parser = new PA_API_Response();
    }

    /**
     * Initialize rate limiter.
     *
     * @since    1.0.0
     */
    private function init_rate_limiter() {
        require_once __DIR__ . '/class-pa-api-rate-limiter.php';
        
        $this->rate_limiter = new PA_API_Rate_Limiter();
    }

    /**
     * Search for products.
     *
     * @since    1.0.0
     * @param    string    $keyword    Search keyword.
     * @param    array     $options    Search options.
     * @return   array                 Search results.
     * @throws   API_Exception        If the API request fails.
     */
    public function search_products(string $keyword, array $options = array()): array {
        // Check cache first
        $cache_key = $this->generate_cache_key('search', array_merge(
            array('keyword' => $keyword),
            $options
        ));
        
        $cached = $this->get_cached($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Build request
        $request_data = $this->request_builder->build_search_request($keyword, $options);
        
        // Add resources
        $request_data['Resources'] = $this->get_search_resources();
        
        // Make request
        $response = $this->make_pa_api_request('/paapi5/searchitems', $request_data);
        
        // Parse response
        $result = $this->response_parser->parse_search_response($response);
        
        // Cache successful response
        if (!empty($result['products'])) {
            $this->set_cache($cache_key, $result, $this->get_cache_ttl('search'));
        }
        
        return $result;
    }

    /**
     * Get a single product by ASIN.
     *
     * @since    1.0.0
     * @param    string    $asin       Product ASIN.
     * @param    array     $options    Request options.
     * @return   array|null           Product data or null.
     * @throws   API_Exception        If the API request fails.
     */
    public function get_product(string $asin, array $options = array()): ?array {
        // Use get_multiple_products for single product
        $result = $this->get_multiple_products(array($asin), $options);
        
        if (isset($result[$asin])) {
            return $result[$asin];
        }
        
        return null;
    }

    /**
     * Get multiple products by ASINs.
     *
     * @since    1.0.0
     * @param    array    $asins      Array of ASINs (max 10).
     * @param    array    $options    Request options.
     * @return   array                Products data.
     * @throws   API_Exception        If the API request fails.
     */
    public function get_multiple_products(array $asins, array $options = array()): array {
        if (empty($asins)) {
            return array();
        }

        // PA-API limits to 10 ASINs per request
        if (count($asins) > 10) {
            throw new API_Exception('PA-API allows maximum 10 ASINs per request');
        }

        // Check cache for each ASIN
        $cached_products = array();
        $asins_to_fetch = array();
        
        foreach ($asins as $asin) {
            $cache_key = $this->generate_cache_key('product', array(
                'asin' => $asin,
                'marketplace' => $this->marketplace,
            ));
            
            $cached = $this->get_cached($cache_key);
            if ($cached !== false) {
                $cached_products[$asin] = $cached;
            } else {
                $asins_to_fetch[] = $asin;
            }
        }

        // Return if all products are cached
        if (empty($asins_to_fetch)) {
            return $cached_products;
        }

        // Build request
        $request_data = $this->request_builder->build_get_items_request($asins_to_fetch, $options);
        
        // Add resources
        $request_data['Resources'] = $this->get_product_resources($options);
        
        // Make request
        $response = $this->make_pa_api_request('/paapi5/getitems', $request_data);
        
        // Parse response
        $products = $this->response_parser->parse_items_response($response);
        
        // Cache individual products
        foreach ($products as $asin => $product) {
            if ($product !== null) {
                $cache_key = $this->generate_cache_key('product', array(
                    'asin' => $asin,
                    'marketplace' => $this->marketplace,
                ));
                $this->set_cache($cache_key, $product, $this->get_cache_ttl('product'));
            }
        }
        
        // Merge with cached products
        return array_merge($cached_products, $products);
    }

    /**
     * Get product variations.
     *
     * @since    1.0.0
     * @param    string    $asin       Parent ASIN.
     * @param    array     $options    Request options.
     * @return   array                 Variations data.
     * @throws   API_Exception        If the API request fails.
     */
    public function get_variations(string $asin, array $options = array()): array {
        // Check cache
        $cache_key = $this->generate_cache_key('variations', array(
            'asin' => $asin,
            'marketplace' => $this->marketplace,
        ));
        
        $cached = $this->get_cached($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Build request
        $request_data = $this->request_builder->build_variations_request($asin, $options);
        
        // Add resources
        $request_data['Resources'] = array(
            'VariationSummary.Price.HighestPrice',
            'VariationSummary.Price.LowestPrice',
            'VariationSummary.VariationDimension',
            'ItemInfo.Title',
            'Images.Primary.Medium',
        );
        
        // Make request
        $response = $this->make_pa_api_request('/paapi5/getvariations', $request_data);
        
        // Parse response
        $result = $this->response_parser->parse_variations_response($response);
        
        // Cache successful response
        if (!empty($result['variations'])) {
            $this->set_cache($cache_key, $result, $this->get_cache_ttl('variations'));
        }
        
        return $result;
    }

    /**
     * Get product offers.
     *
     * @since    1.0.0
     * @param    string    $asin       Product ASIN.
     * @param    array     $options    Request options.
     * @return   array                 Offers data.
     * @throws   API_Exception        If the API request fails.
     */
    public function get_offers(string $asin, array $options = array()): array {
        // Get product with offer resources
        $product = $this->get_product($asin, array_merge($options, array(
            'include_offers' => true,
        )));
        
        if (!$product) {
            return array(
                'summary' => array(
                    'lowest_price' => 0,
                    'total_offers' => 0,
                ),
                'offers' => array(),
            );
        }
        
        return array(
            'summary' => array(
                'lowest_price' => $product['price'] ?? 0,
                'total_offers' => $product['offers_count'] ?? 0,
            ),
            'offers' => $product['offers'] ?? array(),
        );
    }

    /**
     * Get product reviews summary.
     *
     * @since    1.0.0
     * @param    string    $asin       Product ASIN.
     * @param    array     $options    Request options.
     * @return   array                 Reviews summary.
     * @throws   API_Exception        If the API request fails.
     */
    public function get_reviews_summary(string $asin, array $options = array()): array {
        // Get product with review resources
        $product = $this->get_product($asin, array_merge($options, array(
            'include_reviews' => true,
        )));
        
        if (!$product) {
            return array(
                'rating' => 0,
                'total_reviews' => 0,
                'stars_breakdown' => array(),
            );
        }
        
        return array(
            'rating' => $product['rating'] ?? 0,
            'total_reviews' => $product['reviews_count'] ?? 0,
            'stars_breakdown' => $product['stars_breakdown'] ?? array(),
            'top_positive' => '',
            'top_critical' => '',
        );
    }

    /**
     * Get bestsellers in a category.
     *
     * @since    1.0.0
     * @param    string    $category    Category browse node ID.
     * @param    array     $options     Request options.
     * @return   array                  Bestseller products.
     * @throws   API_Exception         If the API request fails.
     */
    public function get_bestsellers(string $category = '', array $options = array()): array {
        // PA-API doesn't have direct bestsellers endpoint
        // Use search with sort by featured
        $search_options = array_merge($options, array(
            'sort' => 'Featured',
            'browse_node' => $category,
        ));
        
        return $this->search_products('', $search_options);
    }

    /**
     * Get new releases in a category.
     *
     * @since    1.0.0
     * @param    string    $category    Category browse node ID.
     * @param    array     $options     Request options.
     * @return   array                  New release products.
     * @throws   API_Exception         If the API request fails.
     */
    public function get_new_releases(string $category = '', array $options = array()): array {
        // Use search with sort by newest
        $search_options = array_merge($options, array(
            'sort' => 'NewestArrivals',
            'browse_node' => $category,
        ));
        
        return $this->search_products('', $search_options);
    }

    /**
     * Get available categories.
     *
     * @since    1.0.0
     * @param    array    $options    Request options.
     * @return   array                Categories list.
     * @throws   API_Exception        If the API request fails.
     */
    public function get_categories(array $options = array()): array {
        // Check cache
        $cache_key = $this->generate_cache_key('categories', array(
            'marketplace' => $this->marketplace,
        ));
        
        $cached = $this->get_cached($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Build request for root browse nodes
        $request_data = $this->request_builder->build_browse_nodes_request(
            $options['parent_id'] ?? '0',
            $options
        );
        
        // Make request
        $response = $this->make_pa_api_request('/paapi5/getbrowsenodes', $request_data);
        
        // Parse response
        $categories = $this->response_parser->parse_browse_nodes_response($response);
        
        // Cache for 24 hours
        $this->set_cache($cache_key, $categories, $this->get_cache_ttl('categories'));
        
        return $categories;
    }

    /**
     * Test API connection.
     *
     * @since    1.0.0
     * @return   array    Connection test results.
     */
    public function test_connection(): array {
        $start_time = microtime(true);
        
        try {
            // Try a simple search
            $result = $this->search_products('test', array(
                'per_page' => 1,
            ));
            
            $latency = microtime(true) - $start_time;
            
            return array(
                'success' => true,
                'message' => 'PA-API connection successful',
                'latency' => round($latency, 3),
                'credits_remaining' => $this->rate_limiter->get_remaining_quota(),
                'rate_limit' => array(
                    'requests_per_second' => 1,
                    'daily_limit' => 8640,
                ),
            );
            
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'message' => 'PA-API connection failed: ' . $e->getMessage(),
                'latency' => round(microtime(true) - $start_time, 3),
                'credits_remaining' => 0,
                'rate_limit' => array(),
            );
        }
    }

    /**
     * Get API quota information.
     *
     * @since    1.0.0
     * @return   array    Quota information.
     */
    public function get_quota_info(): array {
        $quota = $this->rate_limiter->get_quota_info();
        
        return array(
            'credits_used' => $quota['used_today'],
            'credits_remaining' => $quota['remaining_today'],
            'credits_limit' => $quota['daily_limit'],
            'reset_time' => $quota['reset_time'],
            'rate_limits' => array(
                'per_second' => 1,
                'burst_capacity' => 10,
            ),
        );
    }

    /**
     * Get supported marketplaces.
     *
     * @since    1.0.0
     * @return   array    Marketplaces array.
     */
    public function get_supported_marketplaces(): array {
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
            'CN' => 'China',
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
     * @return   array    Provider information.
     */
    public function get_provider_info(): array {
        return array(
            'name' => 'Amazon PA-API 5.0',
            'version' => $this->api_version,
            'capabilities' => array(
                'search',
                'product_details',
                'variations',
                'categories',
                'offers',
                'reviews_summary',
            ),
            'limitations' => array(
                'max_asins_per_request' => 10,
                'max_search_results' => 100,
                'rate_limit_per_second' => 1,
                'daily_request_limit' => 8640,
            ),
        );
    }

    /**
     * Execute PA-API request.
     *
     * @since    1.0.0
     * @param    string    $endpoint    API endpoint.
     * @param    array     $params      Request parameters.
     * @param    string    $method      HTTP method.
     * @param    array     $headers     Additional headers.
     * @return   array                  Response data.
     * @throws   API_Exception         If request fails.
     */
    protected function execute_request(string $endpoint, array $params, string $method, array $headers): array {
        // This method is called by parent's make_request()
        // We'll use our custom make_pa_api_request() instead
        throw new API_Exception('Use make_pa_api_request() for PA-API requests');
    }

    /**
     * Make PA-API request.
     *
     * @since    1.0.0
     * @param    string    $path         API path.
     * @param    array     $request_data Request data.
     * @return   array                   Response data.
     * @throws   API_Exception          If request fails.
     */
    private function make_pa_api_request(string $path, array $request_data): array {
        // Check rate limit
        if (!$this->rate_limiter->can_make_request()) {
            throw new Quota_Exception(
                'PA-API rate limit exceeded',
                $this->rate_limiter->get_retry_after()
            );
        }

        // Get host and target
        $host = $this->get_host($this->marketplace);
        $target = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.' . $this->get_operation_from_path($path);
        
        // Build full URL
        $url = 'https://' . $host . $path;
        
        // Prepare request body
        $request_body = json_encode($request_data);
        
        // Get signed headers
        $signed_headers = $this->auth->get_signed_headers(
            $method = 'POST',
            $url,
            $request_body,
            $host,
            $target
        );
        
        // Make HTTP request
        $response = $this->http_request($url, $request_body, $signed_headers);
        
        // Update rate limiter
        $this->rate_limiter->record_request();
        
        // Parse response
        return $this->handle_response($response);
    }

    /**
     * Make HTTP request.
     *
     * @since    1.0.0
     * @param    string    $url      Request URL.
     * @param    string    $body     Request body.
     * @param    array     $headers  Request headers.
     * @return   array               Response data.
     * @throws   API_Exception      If request fails.
     */
    private function http_request(string $url, string $body, array $headers): array {
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => $body,
            'timeout' => $this->timeout,
            'sslverify' => true,
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            throw new API_Exception(
                'PA-API request failed: ' . $response->get_error_message()
            );
        }
        
        return array(
            'code' => wp_remote_retrieve_response_code($response),
            'body' => wp_remote_retrieve_body($response),
            'headers' => wp_remote_retrieve_headers($response),
        );
    }

    /**
     * Handle API response.
     *
     * @since    1.0.0
     * @param    array    $response    HTTP response.
     * @return   array                 Parsed response data.
     * @throws   API_Exception        If response contains errors.
     */
    private function handle_response(array $response): array {
        $code = $response['code'];
        $body = $response['body'];
        
        // Try to decode JSON
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new API_Exception('Invalid JSON response from PA-API');
        }
        
        // Check for errors
        if ($code !== 200 || isset($data['Errors'])) {
            $this->handle_error_response($code, $data);
        }
        
        return $data;
    }

    /**
     * Handle error response.
     *
     * @since    1.0.0
     * @param    int      $code    HTTP status code.
     * @param    array    $data    Response data.
     * @throws   API_Exception      Always throws exception.
     */
    private function handle_error_response(int $code, array $data) {
        $errors = $data['Errors'] ?? array();
        
        if (empty($errors)) {
            throw new API_Exception('Unknown PA-API error', $code);
        }
        
        $error = $errors[0];
        $error_code = $error['Code'] ?? 'UnknownError';
        $error_message = $error['Message'] ?? 'Unknown error occurred';
        
        // Handle specific error codes
        switch ($error_code) {
            case 'TooManyRequests':
            case 'RequestThrottled':
                throw new Quota_Exception($error_message, 429);
                
            case 'InvalidAssociate':
            case 'UnauthorizedException':
                throw new Auth_Exception($error_message, 401);
                
            case 'ItemsNotFound':
                // This is not really an error, just no results
                return;
                
            default:
                throw new API_Exception($error_message, $code);
        }
    }

    /**
     * Get API host for marketplace.
     *
     * @since    1.0.0
     * @param    string    $marketplace    Marketplace code.
     * @return   string                    API host.
     */
    private function get_host(string $marketplace): string {
        return $this->api_hosts[$marketplace] ?? $this->api_hosts['US'];
    }

    /**
     * Get AWS region for marketplace.
     *
     * @since    1.0.0
     * @param    string    $marketplace    Marketplace code.
     * @return   string                    AWS region.
     */
    private function get_region(string $marketplace): string {
        return $this->regions[$marketplace] ?? $this->regions['US'];
    }

    /**
     * Get operation name from path.
     *
     * @since    1.0.0
     * @param    string    $path    API path.
     * @return   string             Operation name.
     */
    private function get_operation_from_path(string $path): string {
        $operations = array(
            '/paapi5/getitems' => 'GetItems',
            '/paapi5/searchitems' => 'SearchItems',
            '/paapi5/getvariations' => 'GetVariations',
            '/paapi5/getbrowsenodes' => 'GetBrowseNodes',
        );
        
        return $operations[$path] ?? 'Unknown';
    }

    /**
     * Get resources for product requests.
     *
     * @since    1.0.0
     * @param    array    $options    Request options.
     * @return   array                Resource list.
     */
    private function get_product_resources(array $options = array()): array {
        $resources = array(
            'ItemInfo.Title',
            'ItemInfo.Features',
            'ItemInfo.ProductInfo',
            'ItemInfo.TechnicalInfo',
            'Images.Primary.Large',
            'Images.Variants.Large',
            'Offers.Listings.Price',
            'Offers.Listings.Availability.Message',
            'Offers.Listings.Condition',
            'Offers.Listings.DeliveryInfo.IsPrimeEligible',
            'Offers.Summaries.LowestPrice',
            'CustomerReviews.StarRating',
            'CustomerReviews.Count',
            'BrowseNodeInfo.BrowseNodes',
            'BrowseNodeInfo.BrowseNodes.SalesRank',
            'ParentASIN',
        );
        
        // Add variation resources if needed
        if (!empty($options['include_variations'])) {
            $resources[] = 'VariationSummary.VariationDimension';
        }
        
        return $resources;
    }

    /**
     * Get resources for search requests.
     *
     * @since    1.0.0
     * @return   array    Resource list.
     */
    private function get_search_resources(): array {
        return array(
            'ItemInfo.Title',
            'Images.Primary.Medium',
            'Offers.Listings.Price',
            'Offers.Listings.DeliveryInfo.IsPrimeEligible',
            'CustomerReviews.StarRating',
            'CustomerReviews.Count',
        );
    }

    /**
     * Get cache TTL for request type.
     *
     * @since    1.0.0
     * @param    string    $type    Request type.
     * @return   int                TTL in seconds.
     */
    private function get_cache_ttl(string $type): int {
        $ttls = array(
            'product' => 3600,      // 1 hour
            'search' => 1800,       // 30 minutes
            'variations' => 7200,   // 2 hours
            'categories' => 86400,  // 24 hours
        );
        
        return $ttls[$type] ?? 3600;
    }

    /**
     * Validate API credentials.
     *
     * @since    1.0.0
     * @param    array    $credentials    Credentials to validate.
     * @return   bool                    Valid status.
     */
    protected function validate_credentials(array $credentials): bool {
        $required = array('api_key', 'api_secret', 'partner_tag');
        
        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                $this->set_last_error(new Auth_Exception(
                    "Missing required credential: $field"
                ));
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get endpoint URL.
     *
     * @since    1.0.0
     * @param    string    $endpoint    Endpoint path.
     * @param    array     $params      Query parameters.
     * @return   string                 Full endpoint URL.
     */
    protected function get_endpoint_url(string $endpoint, array $params = array()): string {
        $host = $this->get_host($this->marketplace);
        return 'https://' . $host . $endpoint;
    }
}
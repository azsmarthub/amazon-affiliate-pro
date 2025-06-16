<?php
/**
 * PA-API Authentication Handler
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/api/providers
 */

namespace AAPI\API\Providers;

/**
 * PA-API Authentication class.
 *
 * Implements AWS Signature Version 4 signing process for Amazon PA-API 5.0
 * following the official AWS signing protocol.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api/providers
 * @author     Your Name <email@example.com>
 */
class PA_API_Auth {

    /**
     * AWS access key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $access_key    AWS access key ID.
     */
    private $access_key;

    /**
     * AWS secret key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $secret_key    AWS secret access key.
     */
    private $secret_key;

    /**
     * AWS region.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $region    AWS region.
     */
    private $region;

    /**
     * Service name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $service    AWS service name.
     */
    private $service = 'ProductAdvertisingAPI';

    /**
     * Algorithm used for signing.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $algorithm    Signing algorithm.
     */
    private $algorithm = 'AWS4-HMAC-SHA256';

    /**
     * Signed headers list.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $signed_headers    Headers to include in signature.
     */
    private $signed_headers = array(
        'content-encoding',
        'content-type',
        'host',
        'x-amz-date',
        'x-amz-target',
    );

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string    $access_key    AWS access key.
     * @param    string    $secret_key    AWS secret key.
     * @param    string    $region        AWS region.
     */
    public function __construct(string $access_key, string $secret_key, string $region) {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->region = $region;
    }

    /**
     * Get signed headers for request.
     *
     * @since    1.0.0
     * @param    string    $method         HTTP method.
     * @param    string    $url            Request URL.
     * @param    string    $payload        Request payload.
     * @param    string    $host           API host.
     * @param    string    $target         X-Amz-Target header value.
     * @return   array                     Signed headers.
     */
    public function get_signed_headers(
        string $method,
        string $url,
        string $payload,
        string $host,
        string $target
    ): array {
        // Get current timestamp
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        // Prepare headers
        $headers = array(
            'content-encoding' => 'amz-1.0',
            'content-type' => 'application/json; charset=utf-8',
            'host' => $host,
            'x-amz-date' => $timestamp,
            'x-amz-target' => $target,
        );
        
        // Create canonical request
        $canonical_request = $this->create_canonical_request(
            $method,
            $url,
            $headers,
            $payload
        );
        
        // Create string to sign
        $string_to_sign = $this->create_string_to_sign(
            $timestamp,
            $date,
            $canonical_request
        );
        
        // Calculate signature
        $signature = $this->calculate_signature(
            $date,
            $string_to_sign
        );
        
        // Create authorization header
        $authorization = $this->create_authorization_header(
            $date,
            $signature
        );
        
        // Add authorization to headers
        $headers['Authorization'] = $authorization;
        
        // Format headers for HTTP request
        $formatted_headers = array();
        foreach ($headers as $key => $value) {
            $formatted_headers[$key] = $value;
        }
        
        return $formatted_headers;
    }

    /**
     * Create canonical request.
     *
     * @since    1.0.0
     * @param    string    $method       HTTP method.
     * @param    string    $url          Request URL.
     * @param    array     $headers      Request headers.
     * @param    string    $payload      Request payload.
     * @return   string                  Canonical request.
     */
    private function create_canonical_request(
        string $method,
        string $url,
        array $headers,
        string $payload
    ): string {
        // Parse URL
        $parsed_url = parse_url($url);
        $path = $parsed_url['path'] ?? '/';
        $query = $parsed_url['query'] ?? '';
        
        // Canonical URI (URL-encoded)
        $canonical_uri = $this->encode_uri_path($path);
        
        // Canonical query string
        $canonical_query = $this->create_canonical_query($query);
        
        // Canonical headers
        $canonical_headers = $this->create_canonical_headers($headers);
        
        // Signed headers
        $signed_headers_string = implode(';', array_keys($canonical_headers));
        
        // Hashed payload
        $hashed_payload = hash('sha256', $payload);
        
        // Build canonical request
        $canonical_request = implode("\n", array(
            $method,
            $canonical_uri,
            $canonical_query,
            $this->format_canonical_headers($canonical_headers),
            '',
            $signed_headers_string,
            $hashed_payload,
        ));
        
        return $canonical_request;
    }

    /**
     * Encode URI path.
     *
     * @since    1.0.0
     * @param    string    $path    URI path.
     * @return   string             Encoded path.
     */
    private function encode_uri_path(string $path): string {
        if ($path === '/') {
            return '/';
        }
        
        $segments = explode('/', $path);
        $encoded_segments = array();
        
        foreach ($segments as $segment) {
            if ($segment !== '') {
                $encoded_segments[] = rawurlencode($segment);
            }
        }
        
        return '/' . implode('/', $encoded_segments);
    }

    /**
     * Create canonical query string.
     *
     * @since    1.0.0
     * @param    string    $query    Query string.
     * @return   string              Canonical query string.
     */
    private function create_canonical_query(string $query): string {
        if (empty($query)) {
            return '';
        }
        
        parse_str($query, $params);
        ksort($params);
        
        $canonical_params = array();
        foreach ($params as $key => $value) {
            $canonical_params[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
        
        return implode('&', $canonical_params);
    }

    /**
     * Create canonical headers.
     *
     * @since    1.0.0
     * @param    array    $headers    Request headers.
     * @return   array                Canonical headers.
     */
    private function create_canonical_headers(array $headers): array {
        $canonical = array();
        
        foreach ($headers as $key => $value) {
            $key = strtolower(trim($key));
            $value = trim($value);
            
            // Only include signed headers
            if (in_array($key, $this->signed_headers)) {
                $canonical[$key] = $value;
            }
        }
        
        ksort($canonical);
        
        return $canonical;
    }

    /**
     * Format canonical headers.
     *
     * @since    1.0.0
     * @param    array    $headers    Canonical headers.
     * @return   string               Formatted headers.
     */
    private function format_canonical_headers(array $headers): string {
        $formatted = array();
        
        foreach ($headers as $key => $value) {
            $formatted[] = $key . ':' . $value;
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Create string to sign.
     *
     * @since    1.0.0
     * @param    string    $timestamp           Request timestamp.
     * @param    string    $date                Request date.
     * @param    string    $canonical_request   Canonical request.
     * @return   string                         String to sign.
     */
    private function create_string_to_sign(
        string $timestamp,
        string $date,
        string $canonical_request
    ): string {
        $credential_scope = $this->create_credential_scope($date);
        $hashed_canonical = hash('sha256', $canonical_request);
        
        return implode("\n", array(
            $this->algorithm,
            $timestamp,
            $credential_scope,
            $hashed_canonical,
        ));
    }

    /**
     * Create credential scope.
     *
     * @since    1.0.0
     * @param    string    $date    Request date.
     * @return   string             Credential scope.
     */
    private function create_credential_scope(string $date): string {
        return implode('/', array(
            $date,
            $this->region,
            $this->service,
            'aws4_request',
        ));
    }

    /**
     * Calculate signature.
     *
     * @since    1.0.0
     * @param    string    $date              Request date.
     * @param    string    $string_to_sign    String to sign.
     * @return   string                       Signature.
     */
    private function calculate_signature(
        string $date,
        string $string_to_sign
    ): string {
        // Create signing key
        $signing_key = $this->create_signing_key($date);
        
        // Calculate signature
        $signature = hash_hmac('sha256', $string_to_sign, $signing_key);
        
        return $signature;
    }

    /**
     * Create signing key.
     *
     * @since    1.0.0
     * @param    string    $date    Request date.
     * @return   string             Signing key.
     */
    private function create_signing_key(string $date): string {
        $k_secret = 'AWS4' . $this->secret_key;
        $k_date = hash_hmac('sha256', $date, $k_secret, true);
        $k_region = hash_hmac('sha256', $this->region, $k_date, true);
        $k_service = hash_hmac('sha256', $this->service, $k_region, true);
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        
        return $k_signing;
    }

    /**
     * Create authorization header.
     *
     * @since    1.0.0
     * @param    string    $date         Request date.
     * @param    string    $signature    Calculated signature.
     * @return   string                  Authorization header value.
     */
    private function create_authorization_header(
        string $date,
        string $signature
    ): string {
        $credential = $this->access_key . '/' . $this->create_credential_scope($date);
        $signed_headers_string = implode(';', $this->signed_headers);
        
        return sprintf(
            '%s Credential=%s, SignedHeaders=%s, Signature=%s',
            $this->algorithm,
            $credential,
            $signed_headers_string,
            $signature
        );
    }

    /**
     * Validate timestamp freshness.
     *
     * @since    1.0.0
     * @param    string    $timestamp    Timestamp to validate.
     * @return   bool                    Valid status.
     */
    public function validate_timestamp(string $timestamp): bool {
        $request_time = strtotime($timestamp);
        $current_time = time();
        
        // AWS allows 15 minutes time difference
        $time_difference = abs($current_time - $request_time);
        
        return $time_difference <= 900; // 15 minutes
    }

    /**
     * Generate AWS timestamp.
     *
     * @since    1.0.0
     * @return   string    AWS formatted timestamp.
     */
    public function generate_timestamp(): string {
        return gmdate('Ymd\THis\Z');
    }

    /**
     * Generate AWS date.
     *
     * @since    1.0.0
     * @return   string    AWS formatted date.
     */
    public function generate_date(): string {
        return gmdate('Ymd');
    }

    /**
     * Test authentication.
     *
     * @since    1.0.0
     * @return   bool    Test result.
     */
    public function test_authentication(): bool {
        try {
            // Generate test headers
            $test_headers = $this->get_signed_headers(
                'POST',
                'https://webservices.amazon.com/paapi5/searchitems',
                '{"Keywords":"test","PartnerTag":"test-20","PartnerType":"Associates","Marketplace":"www.amazon.com"}',
                'webservices.amazon.com',
                'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems'
            );
            
            // Check if authorization header was generated
            return !empty($test_headers['Authorization']);
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get signing debug information.
     *
     * @since    1.0.0
     * @param    string    $method       HTTP method.
     * @param    string    $url          Request URL.
     * @param    string    $payload      Request payload.
     * @param    string    $host         API host.
     * @param    string    $target       X-Amz-Target header.
     * @return   array                   Debug information.
     */
    public function get_debug_info(
        string $method,
        string $url,
        string $payload,
        string $host,
        string $target
    ): array {
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        $headers = array(
            'content-encoding' => 'amz-1.0',
            'content-type' => 'application/json; charset=utf-8',
            'host' => $host,
            'x-amz-date' => $timestamp,
            'x-amz-target' => $target,
        );
        
        $canonical_request = $this->create_canonical_request(
            $method,
            $url,
            $headers,
            $payload
        );
        
        $string_to_sign = $this->create_string_to_sign(
            $timestamp,
            $date,
            $canonical_request
        );
        
        return array(
            'timestamp' => $timestamp,
            'date' => $date,
            'canonical_request' => $canonical_request,
            'string_to_sign' => $string_to_sign,
            'hashed_payload' => hash('sha256', $payload),
            'credential_scope' => $this->create_credential_scope($date),
        );
    }
}
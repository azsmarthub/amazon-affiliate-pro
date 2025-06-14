# Amazon Affiliate Plugin - Complete Project Specification & Development Roadmap

## 1. Executive Summary

This specification outlines the development of a comprehensive WordPress plugin for Amazon affiliate marketing, incorporating advanced features from industry leaders (AAWP, Content Egg Pro) while maintaining a modular, scalable architecture.

## 2. Feature Set Overview

### 2.1 Core Features (Must Have)

#### A. API Integration System
- **Dual API Support**: Amazon PA-API 5.0 + Third-party Scraper APIs
- **Smart Fallback**: Automatic API switching on failure
- **API Quota Management**: Real-time tracking and alerts
- **Multi-region Support**: 15+ Amazon marketplaces
- **Rate Limiting**: Respect API limits with queue system

#### B. Product Management
- **Import Methods**:
  - Single product by ASIN
  - Bulk import via CSV
  - Keyword-based search
  - Category browsing
  - Bestseller lists auto-import
  - New releases tracking
- **Product Data**:
  - Real-time pricing
  - Stock availability
  - Reviews & ratings
  - Product variations
  - Image galleries
  - Feature bullets
  - Technical specifications

#### C. Display Options
- **Shortcodes**: `[aapi_product]`, `[aapi_grid]`, `[aapi_table]`, `[aapi_list]`
- **Gutenberg Blocks**: Full block editor support
- **Widgets**: Sidebar, footer compatible
- **Templates**: 10+ pre-designed templates
- **Custom CSS**: Full styling control

#### D. Monetization Features
- **Link Management**:
  - Automatic affiliate tag insertion
  - Link cloaking (/go/product-name)
  - NoFollow attributes
  - Link expiry management
- **Geo-targeting**: Auto-redirect to local Amazon store
- **A/B Testing**: Test different layouts/CTAs

### 2.2 Advanced Features (Inspired by AAWP & Content Egg Pro)

#### E. Analytics & Tracking
- **Click Tracking**: 
  - Per-product analytics
  - Heatmap visualization
  - User journey tracking
- **Conversion Tracking**:
  - GA4 custom events
  - UTM parameter support
  - Revenue estimation
- **Performance Reports**:
  - Daily/weekly/monthly views
  - Top performing products
  - API usage statistics

#### F. Content Automation
- **Auto-update System**:
  - Price changes (hourly/daily)
  - Stock status
  - Review counts
  - Product availability
- **Price History Charts**: 30/60/90 day trends
- **Price Drop Alerts**: Email notifications
- **Comparison Engine**: Side-by-side product comparison

#### G. SEO & Schema
- **Schema.org Markup**:
  - Product schema
  - Review schema
  - Offer schema
  - AggregateRating
- **Open Graph Tags**: Social media optimization
- **JSON-LD**: Structured data for rich snippets

#### H. User Experience
- **Search & Filter**:
  - AJAX-powered search
  - Multi-criteria filtering
  - Sort by price/rating/date
- **Wishlist Feature**: Save products for later
- **Price Alerts**: User subscription system
- **Quick View**: Modal product preview

### 2.3 Premium Features (Future Expansion)

#### I. AI Integration
- **Content Generation**: Product descriptions, reviews
- **Smart Recommendations**: AI-powered product suggestions
- **Sentiment Analysis**: Review summarization

#### J. Multi-vendor Support
- **Additional Networks**: eBay, Walmart, BestBuy
- **Price Comparison**: Cross-platform pricing
- **Unified Dashboard**: Single interface for all networks

## 3. Technical Architecture

### 3.1 Plugin Structure
```
amazon-affiliate-pro/
├── amazon-affiliate-pro.php         # Main plugin file
├── uninstall.php                    # Cleanup on uninstall
├── composer.json                    # PHP dependencies
├── package.json                     # JS dependencies
│
├── includes/                        # Core PHP classes
│   ├── class-plugin.php            # Main plugin class
│   ├── class-activator.php         # Activation hooks
│   ├── class-deactivator.php       # Deactivation hooks
│   ├── class-i18n.php              # Internationalization
│   ├── class-loader.php            # Hook loader
│   │
│   ├── admin/                      # Admin functionality
│   │   ├── class-admin.php         # Admin main class
│   │   ├── class-settings.php      # Settings management
│   │   ├── class-product-import.php # Import interface
│   │   ├── class-reports.php       # Analytics reports
│   │   └── class-tools.php         # Diagnostic tools
│   │
│   ├── api/                        # API integrations
│   │   ├── interface-api.php       # API interface
│   │   ├── abstract-api.php        # Base API class
│   │   ├── class-api-manager.php   # API orchestrator
│   │   ├── class-paapi.php         # Amazon PA-API
│   │   ├── class-rainforest.php    # RainforestAPI
│   │   ├── class-serpapi.php       # SerpApi
│   │   └── class-dataforseo.php    # DataForSEO
│   │
│   ├── frontend/                   # Frontend functionality
│   │   ├── class-frontend.php      # Frontend main
│   │   ├── class-shortcodes.php    # Shortcode handler
│   │   ├── class-widgets.php       # Widget classes
│   │   ├── class-blocks.php        # Gutenberg blocks
│   │   └── class-ajax.php          # AJAX handlers
│   │
│   ├── models/                     # Data models
│   │   ├── class-product.php       # Product model
│   │   ├── class-click.php         # Click tracking
│   │   ├── class-api-log.php       # API logging
│   │   └── class-cache.php         # Caching layer
│   │
│   ├── services/                   # Business logic
│   │   ├── class-tracking.php      # Click tracking
│   │   ├── class-cron.php          # Scheduled tasks
│   │   ├── class-geo.php           # Geo-targeting
│   │   ├── class-schema.php        # Schema markup
│   │   └── class-analytics.php     # Analytics integration
│   │
│   └── cli/                        # WP-CLI commands
│       └── class-cli-commands.php   # CLI implementation
│
├── templates/                      # Display templates
│   ├── products/
│   │   ├── single-default.php
│   │   ├── single-compact.php
│   │   ├── single-detailed.php
│   │   ├── grid-default.php
│   │   ├── grid-masonry.php
│   │   ├── list-default.php
│   │   └── table-comparison.php
│   │
│   ├── widgets/
│   │   ├── bestsellers.php
│   │   ├── deals.php
│   │   └── recently-viewed.php
│   │
│   └── emails/
│       ├── price-alert.php
│       └── admin-notification.php
│
├── assets/
│   ├── css/
│   │   ├── admin/
│   │   │   ├── admin.scss
│   │   │   └── reports.scss
│   │   └── frontend/
│   │       ├── products.scss
│   │       ├── widgets.scss
│   │       └── dark-mode.scss
│   │
│   ├── js/
│   │   ├── admin/
│   │   │   ├── admin.js
│   │   │   ├── import.js
│   │   │   └── reports.js
│   │   └── frontend/
│   │       ├── products.js
│   │       ├── tracking.js
│   │       └── filters.js
│   │
│   └── images/
│       ├── placeholder.jpg
│       └── icons/
│
├── languages/                      # Translation files
├── logs/                          # Log files (gitignored)
└── tests/                         # PHPUnit tests
    ├── test-api.php
    ├── test-product.php
    └── test-tracking.php
```

### 3.2 Database Schema

```sql
-- Products table
CREATE TABLE {prefix}_aapi_products (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id bigint(20) UNSIGNED NOT NULL,
    asin varchar(20) NOT NULL,
    marketplace varchar(5) DEFAULT 'US',
    title text,
    price decimal(10,2),
    currency varchar(3),
    sale_price decimal(10,2),
    availability varchar(50),
    rating decimal(2,1),
    review_count int(11),
    prime_eligible tinyint(1) DEFAULT 0,
    image_url text,
    gallery_images longtext,
    features longtext,
    variations longtext,
    last_updated datetime DEFAULT CURRENT_TIMESTAMP,
    update_status varchar(20) DEFAULT 'success',
    PRIMARY KEY (id),
    UNIQUE KEY asin_market (asin, marketplace),
    KEY post_id (post_id),
    KEY last_updated (last_updated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API logs table
CREATE TABLE {prefix}_aapi_api_logs (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    api_provider varchar(50) NOT NULL,
    endpoint varchar(255),
    request_type varchar(20),
    request_data longtext,
    response_code int(3),
    response_message text,
    credits_used int(11) DEFAULT 1,
    execution_time float,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY api_provider (api_provider),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Click tracking table
CREATE TABLE {prefix}_aapi_clicks (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED,
    ip_address varchar(45),
    user_agent text,
    referrer text,
    click_position varchar(50),
    device_type varchar(20),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id (product_id),
    KEY created_at (created_at),
    KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Price history table
CREATE TABLE {prefix}_aapi_price_history (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id bigint(20) UNSIGNED NOT NULL,
    price decimal(10,2) NOT NULL,
    currency varchar(3),
    recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY product_id_date (product_id, recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User alerts table
CREATE TABLE {prefix}_aapi_alerts (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_email varchar(255) NOT NULL,
    product_id bigint(20) UNSIGNED NOT NULL,
    alert_type varchar(20) DEFAULT 'price_drop',
    target_price decimal(10,2),
    status varchar(20) DEFAULT 'active',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_email (user_email),
    KEY product_status (product_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.3 API Interface Design

```php
namespace AAPI\API;

interface API_Provider {
    // Search methods
    public function search_products(string $keyword, array $options = []): array;
    public function get_product(string $asin): ?array;
    public function get_multiple_products(array $asins): array;
    public function get_variations(string $asin): array;
    
    // Category methods
    public function get_categories(): array;
    public function get_bestsellers(string $category, int $limit = 10): array;
    public function get_new_releases(string $category, int $limit = 10): array;
    
    // API management
    public function test_connection(): bool;
    public function get_quota_info(): array;
    public function get_supported_marketplaces(): array;
    
    // Error handling
    public function get_last_error(): ?string;
    public function get_error_code(): ?int;
}

abstract class Base_API implements API_Provider {
    protected $api_key;
    protected $api_secret;
    protected $marketplace;
    protected $last_error;
    protected $error_code;
    protected $logger;
    
    // Implement common functionality
    abstract protected function make_request(string $endpoint, array $params = []);
    abstract protected function parse_response($response);
    
    // Rate limiting
    protected function check_rate_limit(): bool;
    protected function update_rate_limit(): void;
    
    // Caching
    protected function get_cached(string $key);
    protected function set_cache(string $key, $data, int $expiry = 3600);
}
```

## 4. Development Roadmap

### Phase 1: Foundation (Week 1-2)
1. **Project Setup**
   - Initialize plugin structure
   - Set up autoloader
   - Create activation/deactivation hooks
   - Database tables creation

2. **Core Architecture**
   - Implement base classes
   - Set up dependency injection
   - Create settings framework
   - Build admin menu structure

### Phase 2: API Integration (Week 3-4)
1. **API Framework**
   - Create API interface
   - Implement base API class
   - Build API manager

2. **Amazon PA-API**
   - Implement authentication
   - Create product fetch methods
   - Handle rate limiting
   - Error handling

3. **Scraper API**
   - Implement one scraper API
   - Create fallback mechanism
   - Test switching logic

### Phase 3: Product Management (Week 5-6)
1. **Custom Post Type**
   - Create amazon_product CPT
   - Add meta boxes
   - Implement save handlers

2. **Import System**
   - Single product import
   - Bulk import interface
   - Search functionality
   - Preview system

### Phase 4: Display System (Week 7-8)
1. **Templates**
   - Create base templates
   - Implement template loader
   - Add customization hooks

2. **Shortcodes**
   - Register shortcodes
   - Create shortcode builder
   - Add attributes handling

3. **Gutenberg Blocks**
   - Create block scripts
   - Register blocks
   - Add block controls

### Phase 5: Tracking & Analytics (Week 9-10)
1. **Click Tracking**
   - Implement tracking system
   - Create redirect handler
   - Build analytics dashboard

2. **Reporting**
   - Create report interfaces
   - Implement data aggregation
   - Add export functionality

### Phase 6: Automation (Week 11-12)
1. **Cron System**
   - Set up WP-Cron jobs
   - Implement update queue
   - Add failure recovery

2. **Price Monitoring**
   - Create price history tracking
   - Implement alert system
   - Build notification system

### Phase 7: Advanced Features (Week 13-14)
1. **Geo-targeting**
   - Implement IP detection
   - Create redirect logic
   - Add manual override

2. **Schema Markup**
   - Implement JSON-LD
   - Add Open Graph tags
   - Create rich snippets

### Phase 8: Testing & Optimization (Week 15-16)
1. **Testing**
   - Unit tests
   - Integration tests
   - Performance testing

2. **Optimization**
   - Code refactoring
   - Database optimization
   - Caching implementation

## 5. Coding Standards & Conventions

### 5.1 PHP Standards
- **Version**: PHP 7.4+ compatible
- **Namespace**: `AAPI\` for all classes
- **Coding Standard**: WordPress Coding Standards (WPCS)
- **Documentation**: PHPDoc for all methods

### 5.2 Naming Conventions
```php
// Classes: PascalCase với prefix
class AAPI_Product_Manager {}

// Methods: snake_case
public function get_product_by_asin($asin) {}

// Properties: snake_case với visibility
private $api_credentials;
protected $cache_duration;
public $display_options;

// Constants: UPPERCASE với prefix
define('AAPI_VERSION', '1.0.0');
const API_TIMEOUT = 30;

// Hooks: prefix với plugin slug
add_action('aapi_before_product_save', $callback);
add_filter('aapi_product_data', $callback);

// Database: prefix với aapi_
$wpdb->prefix . 'aapi_products'

// Options: prefix với aapi_
get_option('aapi_settings');
update_option('aapi_api_credentials', $data);

// Transients: prefix với _aapi_
set_transient('_aapi_product_' . $asin, $data);
```

### 5.3 File Organization
- One class per file
- File names match class names (lowercase, hyphens)
- Logical grouping in subdirectories
- Interfaces in separate files

### 5.4 JavaScript Standards
- **Version**: ES6+
- **Build Tool**: Webpack
- **Linting**: ESLint với WordPress config
- **Framework**: Vanilla JS cho frontend, React cho blocks

### 5.5 CSS Standards
- **Preprocessor**: SASS
- **Methodology**: BEM
- **Prefix**: `.aapi-` cho all classes
- **Variables**: Sử dụng CSS custom properties

## 6. Security Considerations

### 6.1 Data Validation
- Sanitize all inputs
- Validate AJAX nonces
- Escape output
- Prepared statements cho database

### 6.2 API Security
- Encrypt API credentials
- Implement request signing
- Rate limit API calls
- Log suspicious activity

### 6.3 User Permissions
- Capability checks
- Role-based access
- Admin-only features
- User data protection

## 7. Performance Optimization

### 7.1 Caching Strategy
- **Object Cache**: Transients cho API responses
- **Page Cache**: Compatible với popular plugins
- **Database Cache**: Query optimization
- **CDN**: Asset delivery

### 7.2 Lazy Loading
- Images lazy load
- Deferred script loading
- Conditional asset loading
- AJAX pagination

### 7.3 Database Optimization
- Indexed columns
- Efficient queries
- Batch operations
- Regular cleanup

## 8. Extensibility

### 8.1 Hooks System
```php
// Action hooks
do_action('aapi_before_product_import', $asin);
do_action('aapi_after_product_save', $product_id, $data);
do_action('aapi_before_display', $product);

// Filter hooks
apply_filters('aapi_product_data', $data, $asin);
apply_filters('aapi_template_path', $template, $type);
apply_filters('aapi_api_providers', $providers);
```

### 8.2 Template System
- Override templates trong theme
- Custom template hooks
- Template parts system
- Dynamic template selection

### 8.3 API Extensions
- Custom API provider interface
- Provider registration system
- Custom field mapping
- Data transformation hooks

## 9. Documentation Plan

### 9.1 User Documentation
- Installation guide
- Quick start tutorial
- Feature documentation
- Video tutorials
- FAQ section

### 9.2 Developer Documentation
- API reference
- Hook documentation
- Code examples
- Extension guide
- Contributing guidelines

### 9.3 Inline Documentation
- PHPDoc cho all methods
- Inline comments cho complex logic
- README files trong each directory
- Change log maintenance

## 10. Testing Strategy

### 10.1 Unit Tests
- PHPUnit cho PHP code
- Jest cho JavaScript
- 80% code coverage target
- Automated testing với GitHub Actions

### 10.2 Integration Tests
- API integration tests
- Database operation tests
- WordPress integration tests
- Cross-browser testing

### 10.3 Performance Tests
- Load testing
- API response time
- Database query optimization
- Memory usage profiling

## 11. Deployment & Maintenance

### 11.1 Version Control
- Git flow branching
- Semantic versioning
- Tagged releases
- Changelog generation

### 11.2 Build Process
- Composer cho PHP deps
- NPM cho JS deps
- Webpack build
- Minification
- Distribution package

### 11.3 Update System
- WordPress update API
- Migration scripts
- Backward compatibility
- Feature flags

## 12. Monetization Strategy

### 12.1 Freemium Model
- **Free Version**: Basic features, 1 API, 100 products
- **Pro Version**: All features, unlimited APIs/products
- **Agency Version**: Multi-site license

### 12.2 Pricing Tiers
- **Starter**: $49/year (1 site)
- **Professional**: $99/year (3 sites)  
- **Agency**: $249/year (unlimited sites)
- **Lifetime**: $499 (unlimited sites)

### 12.3 Additional Revenue
- Custom API development
- Premium templates
- Priority support
- Custom features

## 13. Success Metrics

### 13.1 Technical KPIs
- Page load time < 3s
- API response time < 500ms
- 99.9% uptime
- Zero critical bugs

### 13.2 Business KPIs
- 10,000+ active installs (Year 1)
- 5% free-to-paid conversion
- < 2% refund rate
- 4.5+ rating trên WordPress.org

### 13.3 User Satisfaction
- NPS score > 50
- Support response < 24h
- Feature request implementation
- Community engagement

---

## Appendix A: Sample Code Structure

### A.1 Main Plugin File
```php
<?php
/**
 * Plugin Name: Amazon Affiliate Pro Integration
 * Plugin URI: https://example.com/aapi
 * Description: Advanced Amazon affiliate integration with dual API support
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: aapi
 * Domain Path: /languages
 */

namespace AAPI;

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('AAPI_VERSION', '1.0.0');
define('AAPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAPI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once AAPI_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize plugin
add_action('plugins_loaded', function() {
    Plugin::get_instance();
});

// Activation/Deactivation hooks
register_activation_hook(__FILE__, [Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Deactivator::class, 'deactivate']);
```

### A.2 API Manager Example
```php
namespace AAPI\API;

class API_Manager {
    private $providers = [];
    private $active_provider;
    
    public function register_provider(string $name, API_Provider $provider) {
        $this->providers[$name] = $provider;
    }
    
    public function get_product(string $asin) {
        try {
            // Try primary API
            $product = $this->active_provider->get_product($asin);
            
            if ($product) {
                $this->log_success();
                return $product;
            }
        } catch (\Exception $e) {
            $this->log_error($e);
        }
        
        // Fallback to secondary API
        return $this->fallback_get_product($asin);
    }
    
    private function fallback_get_product(string $asin) {
        foreach ($this->providers as $name => $provider) {
            if ($provider === $this->active_provider) {
                continue;
            }
            
            try {
                $product = $provider->get_product($asin);
                if ($product) {
                    $this->log_fallback_success($name);
                    return $product;
                }
            } catch (\Exception $e) {
                $this->log_error($e);
            }
        }
        
        return null;
    }
}
```

## Appendix B: Development Checklist

### B.1 Phase 1 Checklist
- [ ] Plugin structure created
- [ ] Autoloader implemented
- [ ] Base classes defined
- [ ] Database tables installed
- [ ] Admin menu registered
- [ ] Settings page created
- [ ] Uninstall cleanup ready

### B.2 Phase 2 Checklist  
- [ ] API interface defined
- [ ] Base API class implemented
- [ ] PA-API integration working
- [ ] One scraper API integrated
- [ ] Fallback mechanism tested
- [ ] API credentials encrypted
- [ ] Rate limiting active

### B.3 Phase 3 Checklist
- [ ] CPT registered
- [ ] Meta boxes added
- [ ] Import UI created
- [ ] Search functionality
- [ ] Bulk import working
- [ ] Preview system ready
- [ ] Validation complete

*[Các phase còn lại tương tự...]*

---

*Lưu ý: Document này được thiết kế để dễ dàng chia sẻ giữa các chat sessions và AI models. Mỗi section có thể được implement độc lập mà không mất context tổng thể.*
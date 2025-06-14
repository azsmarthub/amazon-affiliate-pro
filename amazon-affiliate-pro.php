<?php
/**
 * Plugin Name: Amazon Affiliate Pro Integration
 * Plugin URI: https://example.com/amazon-affiliate-pro
 * Description: Advanced Amazon affiliate integration with dual API support, tracking, and analytics
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aapi
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

namespace AAPI;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin constants
 */
define('AAPI_VERSION', '1.0.0');
define('AAPI_PLUGIN_NAME', 'amazon-affiliate-pro');
define('AAPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAPI_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AAPI_PLUGIN_FILE', __FILE__);

// Database version
define('AAPI_DB_VERSION', '1.0.0');

// API constants
define('AAPI_API_TIMEOUT', 30);
define('AAPI_CACHE_DURATION', 3600); // 1 hour default

/**
 * Autoloader registration
 */
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'AAPI\\';
    
    // Base directory for the namespace prefix
    $base_dir = AAPI_PLUGIN_DIR . 'includes/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $class_path = str_replace('\\', '/', $relative_class);
    
    // Convert class name to file name
    $class_parts = explode('/', $class_path);
    $class_file = 'class-' . strtolower(str_replace('_', '-', end($class_parts))) . '.php';
    
    // Build the file path
    array_pop($class_parts);
    $file_path = $base_dir;
    
    if (!empty($class_parts)) {
        $file_path .= strtolower(implode('/', $class_parts)) . '/';
    }
    
    $file = $file_path . $class_file;
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-activator.php
 */
function activate_amazon_affiliate_pro() {
    require_once AAPI_PLUGIN_DIR . 'includes/class-activator.php';
    Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-deactivator.php
 */
function deactivate_amazon_affiliate_pro() {
    require_once AAPI_PLUGIN_DIR . 'includes/class-deactivator.php';
    Deactivator::deactivate();
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\activate_amazon_affiliate_pro');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivate_amazon_affiliate_pro');

/**
 * Begins execution of the plugin.
 */
function run_amazon_affiliate_pro() {
    require_once AAPI_PLUGIN_DIR . 'includes/class-plugin.php';
    
    $plugin = new Plugin();
    $plugin->run();
}

// Initialize the plugin
add_action('plugins_loaded', __NAMESPACE__ . '\run_amazon_affiliate_pro', 10);
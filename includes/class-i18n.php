<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes
 */

namespace AAPI;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes
 * @author     Your Name <email@example.com>
 */
class I18n {

    /**
     * The text domain for translation.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $domain    The text domain for translation.
     */
    private $domain;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->domain = 'aapi';
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Set the domain for translations.
     *
     * @since    1.0.0
     * @param    string    $domain    The domain for translations.
     */
    public function set_domain($domain) {
        $this->domain = $domain;
    }

    /**
     * Get the domain for translations.
     *
     * @since    1.0.0
     * @return   string    The domain for translations.
     */
    public function get_domain() {
        return $this->domain;
    }
}
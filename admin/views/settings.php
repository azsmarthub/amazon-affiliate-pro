<?php
/**
 * Admin settings view
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Initialize settings page
$settings_page = new \AAPI\Admin\Settings_Page();
$settings_page->render();
?>
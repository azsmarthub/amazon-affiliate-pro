<?php
/**
 * Admin settings page functionality
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/admin
 */

namespace AAPI\Admin;

use AAPI\Core\Settings;

/**
 * Admin settings page class.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/admin
 * @author     Your Name <email@example.com>
 */
class Settings_Page {

    /**
     * Settings instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Settings    $settings    Settings instance.
     */
    private $settings;

    /**
     * Current tab.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $current_tab    Current tab.
     */
    private $current_tab;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->settings = new Settings();
    }

    /**
     * Initialize settings.
     *
     * @since    1.0.0
     */
    public function init_settings() {
        // Register settings
        $this->register_settings();
        
        // Add settings sections
        $this->add_settings_sections();
        
        // Add settings fields
        $this->add_settings_fields();
    }

    /**
     * Register settings.
     *
     * @since    1.0.0
     */
    private function register_settings() {
        $groups = $this->settings->get_groups();
        
        foreach ($groups as $group_id => $group) {
            register_setting(
                'aapi_' . $group_id . '_settings_group',
                'aapi_' . $group_id . '_settings',
                array($this, 'sanitize_settings')
            );
        }
    }

    /**
     * Add settings sections.
     *
     * @since    1.0.0
     */
    private function add_settings_sections() {
        $groups = $this->settings->get_groups();
        
        foreach ($groups as $group_id => $group) {
            add_settings_section(
                'aapi_' . $group_id . '_section',
                '',
                array($this, 'render_section_description'),
                'aapi_' . $group_id . '_settings'
            );
        }
    }

    /**
     * Add settings fields.
     *
     * @since    1.0.0
     */
    private function add_settings_fields() {
        $groups = $this->settings->get_groups();
        
        foreach ($groups as $group_id => $group) {
            $fields = $this->settings->get_fields($group_id);
            
            foreach ($fields as $field_id => $field) {
                add_settings_field(
                    'aapi_' . $group_id . '_' . $field_id,
                    $field['title'],
                    array($this, 'render_field'),
                    'aapi_' . $group_id . '_settings',
                    'aapi_' . $group_id . '_section',
                    array(
                        'group' => $group_id,
                        'field_id' => $field_id,
                        'field' => $field,
                    )
                );
            }
        }
    }

    /**
     * Render settings page.
     *
     * @since    1.0.0
     */
    public function render() {
        $this->current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php $this->render_tabs(); ?>
            
            <form method="post" action="options.php" class="aapi-settings-form">
                <?php
                settings_fields('aapi_' . $this->current_tab . '_settings_group');
                do_settings_sections('aapi_' . $this->current_tab . '_settings');
                
                // Add delete data on uninstall option
                if ($this->current_tab === 'general') {
                    $this->render_uninstall_option();
                }
                
                submit_button();
                ?>
            </form>
            
            <?php if ($this->current_tab === 'api') : ?>
                <div class="aapi-api-test-section">
                    <h3><?php _e('Test API Connection', 'aapi'); ?></h3>
                    <p><?php _e('Test your API credentials to ensure they are working correctly.', 'aapi'); ?></p>
                    <button type="button" class="button button-secondary" id="aapi-test-api">
                        <?php _e('Test Connection', 'aapi'); ?>
                    </button>
                    <span class="spinner"></span>
                    <div id="aapi-test-results"></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render settings tabs.
     *
     * @since    1.0.0
     */
    private function render_tabs() {
        $tabs = array(
            'general' => __('General', 'aapi'),
            'api' => __('API Settings', 'aapi'),
            'display' => __('Display', 'aapi'),
            'tracking' => __('Tracking', 'aapi'),
        );
        
        $tabs = apply_filters('aapi_settings_tabs', $tabs);
        ?>
        <nav class="nav-tab-wrapper">
            <?php
            foreach ($tabs as $tab_id => $tab_name) {
                $active = $this->current_tab === $tab_id ? ' nav-tab-active' : '';
                printf(
                    '<a href="%s" class="nav-tab%s">%s</a>',
                    esc_url(add_query_arg('tab', $tab_id, remove_query_arg('settings-updated'))),
                    esc_attr($active),
                    esc_html($tab_name)
                );
            }
            ?>
        </nav>
        <?php
    }

    /**
     * Render section description.
     *
     * @since    1.0.0
     * @param    array    $args    Section arguments.
     */
    public function render_section_description($args) {
        $group_id = str_replace(array('aapi_', '_section'), '', $args['id']);
        $groups = $this->settings->get_groups();
        
        if (isset($groups[$group_id]['description'])) {
            echo '<p>' . esc_html($groups[$group_id]['description']) . '</p>';
        }
    }

    /**
     * Render field.
     *
     * @since    1.0.0
     * @param    array    $args    Field arguments.
     */
    public function render_field($args) {
        $group = $args['group'];
        $field_id = $args['field_id'];
        $field = $args['field'];
        
        $value = $this->settings->get($group, $field_id);
        $name = 'aapi_' . $group . '_settings[' . $field_id . ']';
        $id = 'aapi_' . $group . '_' . $field_id;
        
        // Check condition
        if (isset($field['condition']) && !$this->check_condition($field['condition'], $group)) {
            echo '<div style="display:none;">';
        }
        
        switch ($field['type']) {
            case 'text':
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value)
                );
                break;
                
            case 'password':
                printf(
                    '<input type="password" id="%s" name="%s" value="%s" class="regular-text" />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value)
                );
                break;
                
            case 'number':
                $min = isset($field['min']) ? 'min="' . esc_attr($field['min']) . '"' : '';
                $max = isset($field['max']) ? 'max="' . esc_attr($field['max']) . '"' : '';
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" class="small-text" %s %s />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value),
                    $min,
                    $max
                );
                break;
                
            case 'textarea':
                printf(
                    '<textarea id="%s" name="%s" rows="5" class="large-text">%s</textarea>',
                    esc_attr($id),
                    esc_attr($name),
                    esc_textarea($value)
                );
                break;
                
            case 'select':
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
                foreach ($field['options'] as $option_value => $option_label) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        selected($value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                echo '</select>';
                break;
                
            case 'checkbox':
                printf(
                    '<label><input type="checkbox" id="%s" name="%s" value="1" %s /> %s</label>',
                    esc_attr($id),
                    esc_attr($name),
                    checked($value, true, false),
                    isset($field['label']) ? esc_html($field['label']) : ''
                );
                break;
                
            case 'radio':
                foreach ($field['options'] as $option_value => $option_label) {
                    printf(
                        '<label><input type="radio" name="%s" value="%s" %s /> %s</label><br>',
                        esc_attr($name),
                        esc_attr($option_value),
                        checked($value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                break;
                
            case 'color':
                printf(
                    '<input type="text" id="%s" name="%s" value="%s" class="aapi-color-picker" />',
                    esc_attr($id),
                    esc_attr($name),
                    esc_attr($value)
                );
                break;
        }
        
        // Add description
        if (isset($field['description'])) {
            echo '<p class="description">' . esc_html($field['description']) . '</p>';
        }
        
        // Close condition div
        if (isset($field['condition']) && !$this->check_condition($field['condition'], $group)) {
            echo '</div>';
        }
    }

    /**
     * Check field condition.
     *
     * @since    1.0.0
     * @param    array     $condition    Condition array.
     * @param    string    $group        Settings group.
     * @return   bool                    True if condition met, false otherwise.
     */
    private function check_condition($condition, $group) {
        if (!is_array($condition) || count($condition) < 3) {
            return true;
        }
        
        list($field, $operator, $value) = $condition;
        
        $field_value = $this->settings->get($group, $field);
        
        switch ($operator) {
            case '==':
                return $field_value == $value;
            case '!=':
                return $field_value != $value;
            case '>':
                return $field_value > $value;
            case '<':
                return $field_value < $value;
            case '>=':
                return $field_value >= $value;
            case '<=':
                return $field_value <= $value;
            default:
                return true;
        }
    }

    /**
     * Sanitize settings.
     *
     * @since    1.0.0
     * @param    array    $input    Input values.
     * @return   array              Sanitized values.
     */
    public function sanitize_settings($input) {
        if (!is_array($input)) {
            return array();
        }
        
        // Get the group from the option name
        $option_name = isset($_POST['option_page']) ? sanitize_text_field($_POST['option_page']) : '';
        $group = str_replace(array('aapi_', '_settings_group'), '', $option_name);
        
        $fields = $this->settings->get_fields($group);
        $sanitized = array();
        
        foreach ($fields as $field_id => $field) {
            if (!isset($input[$field_id])) {
                // Handle unchecked checkboxes
                if ($field['type'] === 'checkbox') {
                    $sanitized[$field_id] = false;
                }
                continue;
            }
            
            $value = $input[$field_id];
            
            // Apply field-specific sanitization
            if (isset($field['sanitize']) && is_callable($field['sanitize'])) {
                $value = call_user_func($field['sanitize'], $value);
            } else {
                // Default sanitization based on type
                switch ($field['type']) {
                    case 'text':
                    case 'password':
                    case 'select':
                    case 'radio':
                        $value = sanitize_text_field($value);
                        break;
                    case 'textarea':
                        $value = sanitize_textarea_field($value);
                        break;
                    case 'number':
                        $value = absint($value);
                        break;
                    case 'checkbox':
                        $value = !empty($value);
                        break;
                    case 'color':
                        $value = sanitize_hex_color($value);
                        break;
                }
            }
            
            // Encrypt if needed
            if (isset($field['encrypted']) && $field['encrypted'] && !empty($value)) {
                $value = $this->encrypt_value($value);
            }
            
            $sanitized[$field_id] = $value;
        }
        
        return $sanitized;
    }

    /**
     * Render uninstall option.
     *
     * @since    1.0.0
     */
    private function render_uninstall_option() {
        $delete_data = get_option('aapi_delete_data_on_uninstall', false);
        ?>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Uninstall Settings', 'aapi'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="aapi_delete_data_on_uninstall" value="1" <?php checked($delete_data, true); ?> />
                            <?php _e('Delete all plugin data when uninstalling', 'aapi'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Warning: This will permanently delete all products, settings, and data when the plugin is uninstalled.', 'aapi'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * AJAX test API connection.
     *
     * @since    1.0.0
     */
    public function ajax_test_api() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aapi_admin_nonce')) {
            wp_die(__('Security check failed', 'aapi'));
        }
        
        // Check capability
        if (!current_user_can('aapi_manage_api_keys')) {
            wp_die(__('Insufficient permissions', 'aapi'));
        }
        
        $api_type = $this->settings->get('api', 'primary_api', 'paapi');
        $results = array();
        
        try {
            // Get API manager
            $api_manager = new \AAPI\API\API_Manager();
            
            // Test connection
            $test_result = $api_manager->test_connection($api_type);
            
            if ($test_result['success']) {
                $results['success'] = true;
                $results['message'] = __('API connection successful!', 'aapi');
                $results['details'] = $test_result['details'];
            } else {
                $results['success'] = false;
                $results['message'] = __('API connection failed', 'aapi');
                $results['error'] = $test_result['error'];
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['message'] = __('API test error', 'aapi');
            $results['error'] = $e->getMessage();
        }
        
        wp_send_json($results);
    }

    /**
     * Encrypt a value.
     *
     * @since    1.0.0
     * @param    string    $value    Value to encrypt.
     * @return   string              Encrypted value.
     */
    private function encrypt_value($value) {
        if (!defined('LOGGED_IN_KEY') || empty(LOGGED_IN_KEY)) {
            return $value;
        }
        
        $key = substr(hash('sha256', LOGGED_IN_KEY), 0, 32);
        $iv = substr(hash('sha256', LOGGED_IN_SALT), 0, 16);
        
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($encrypted);
    }
}